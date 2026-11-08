<?php
session_start();

require_once 'conexion.php';

// Función para mostrar texto de forma segura.
function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

$errores = [];
$idPedidoConfirmado = null;

// Comprobamos que el usuario haya iniciado sesión.
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

// Solo los clientes pueden realizar compras.
if (($_SESSION['rol'] ?? '') !== 'cliente') {
    header("Location: productos.php");
    exit;
}

// Recuperamos el carrito.
$carrito = $_SESSION['carrito'] ?? [];

if (empty($carrito)) {
    header("Location: carrito.php");
    exit;
}

// Fotografías de los productos.
$imagenesProductos = [
    1 => 'nike-metcon-9.jpg',
    2 => 'nike-metcon-10.jpg',
    3 => 'reebok-nano-x.jpg',
    6 => 'adidas-dropset-4.jpg',
    7 => 'under-armour-tribase-reign-6.jpg',
    8 => 'reebok-nanoflex-3.jpg',
    10 => 'velites-hybrid-1.jpg'
];

// Recuperamos la dirección del cliente.
$consultaUsuario = $conexion->prepare(
    "SELECT nombre, direccion
     FROM usuarios
     WHERE id_usuario = :id_usuario
       AND rol = 'cliente'"
);

$consultaUsuario->execute([
    ':id_usuario' => (int) $_SESSION['id_usuario']
]);

$usuario = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: login.php");
    exit;
}

$direccion = $usuario['direccion'] ?? '';

// Token CSRF del formulario de compra.
if (empty($_SESSION['csrf_confirmar_pedido'])) {
    $_SESSION['csrf_confirmar_pedido'] = bin2hex(random_bytes(32));
}

// Recuperamos los productos del carrito.
$productosCarrito = [];
$totalCarrito = 0;

$consultaVariante = $conexion->prepare(
    "SELECT
        v.id_variante,
        v.id_producto,
        v.talla,
        v.color,
        v.stock,
        p.marca,
        p.modelo,
        p.precio
     FROM variantes v
     INNER JOIN productos p
        ON v.id_producto = p.id_producto
     WHERE v.id_variante = :id_variante"
);

foreach ($carrito as $idVariante => $cantidad) {

    $idVariante = (int) $idVariante;
    $cantidad = (int) $cantidad;

    if ($idVariante <= 0 || $cantidad <= 0) {
        $errores[] = "El carrito contiene un producto no válido.";
        continue;
    }

    $consultaVariante->execute([
        ':id_variante' => $idVariante
    ]);

    $producto = $consultaVariante->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        $errores[] = "Uno de los productos del carrito ya no existe.";
        continue;
    }

    if ($cantidad > (int) $producto['stock']) {
        $errores[] =
            "No hay suficiente stock de " .
            $producto['marca'] . " " .
            $producto['modelo'] . ".";
    }

    $subtotal = (float) $producto['precio'] * $cantidad;

    $producto['cantidad'] = $cantidad;
    $producto['subtotal'] = $subtotal;

    // Asociamos la fotografía del producto.
    $idProducto = (int) $producto['id_producto'];

    $nombreImagen = $imagenesProductos[$idProducto] ?? null;

    $rutaImagen = $nombreImagen
        ? 'img/productos/' . $nombreImagen
        : null;

    $producto['imagen'] = (
        $rutaImagen !== null &&
        is_file(__DIR__ . '/' . $rutaImagen)
    ) ? $rutaImagen : null;

    $productosCarrito[] = $producto;

    $totalCarrito += $subtotal;
}

// Procesamos la confirmación del pedido.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (
        !is_string($tokenRecibido) ||
        !hash_equals(
            $_SESSION['csrf_confirmar_pedido'],
            $tokenRecibido
        )
    ) {
        $errores[] = "La solicitud no es válida. Recarga la página.";
    }

    $direccion = trim($_POST['direccion_envio'] ?? '');

    if ($direccion === '') {
        $errores[] = "Debes indicar una dirección de envío.";
    }

    if (mb_strlen($direccion) > 255) {
        $errores[] = "La dirección no puede superar los 255 caracteres.";
    }

    if (empty($errores)) {

        try {

            // Iniciamos una transacción.
            $conexion->beginTransaction();

            $detalles = [];
            $totalPedidoCentimos = 0;

            // Bloqueamos las variantes mientras comprobamos su stock.
            $comprobarStock = $conexion->prepare(
                "SELECT
                    v.id_variante,
                    v.stock,
                    p.precio
                 FROM variantes v
                 INNER JOIN productos p
                    ON v.id_producto = p.id_producto
                 WHERE v.id_variante = :id_variante
                 FOR UPDATE"
            );

            // Ordenamos los identificadores de las variantes.
            $idsVariantes = array_keys($carrito);
            sort($idsVariantes, SORT_NUMERIC);

            foreach ($idsVariantes as $idVariante) {

                $idVariante = (int) $idVariante;
                $cantidad = (int) $carrito[$idVariante];

                if ($idVariante <= 0 || $cantidad <= 0) {
                    throw new Exception(
                        "El carrito contiene cantidades no válidas."
                    );
                }

                $comprobarStock->execute([
                    ':id_variante' => $idVariante
                ]);

                $variante = $comprobarStock->fetch(PDO::FETCH_ASSOC);

                $comprobarStock->closeCursor();

                if (!$variante) {
                    throw new Exception(
                        "Uno de los productos ya no está disponible."
                    );
                }

                if ($cantidad > (int) $variante['stock']) {
                    throw new Exception(
                        "No hay suficiente stock para completar la compra."
                    );
                }

                // Calculamos los importes utilizando céntimos.
                $precioCentimos = (int) round(
                    (float) $variante['precio'] * 100
                );

                $totalPedidoCentimos += $precioCentimos * $cantidad;

                $detalles[] = [
                    'id_variante' => $idVariante,
                    'cantidad' => $cantidad,
                    'precio_centimos' => $precioCentimos
                ];
            }

            $totalPedido = number_format(
                $totalPedidoCentimos / 100,
                2,
                '.',
                ''
            );

            // Creamos el pedido.
            $insertarPedido = $conexion->prepare(
                "INSERT INTO pedidos
                    (id_usuario, estado, total, direccion_envio)
                 VALUES
                    (:id_usuario, :estado, :total, :direccion_envio)"
            );

            $insertarPedido->execute([
                ':id_usuario' => (int) $_SESSION['id_usuario'],
                ':estado' => 'confirmado',
                ':total' => $totalPedido,
                ':direccion_envio' => $direccion
            ]);

            // Recuperamos el ID del pedido.
            $idPedidoConfirmado = (int) $conexion->lastInsertId();

            // Preparamos las consultas del detalle y del stock.
            $insertarDetalle = $conexion->prepare(
                "INSERT INTO detalle_pedido
                    (id_pedido, id_variante, cantidad, precio_unitario)
                 VALUES
                    (:id_pedido, :id_variante, :cantidad, :precio_unitario)"
            );

            $actualizarStock = $conexion->prepare(
                "UPDATE variantes
                 SET stock = stock - :cantidad
                 WHERE id_variante = :id_variante
                   AND stock >= :cantidad"
            );

            foreach ($detalles as $detalle) {

                $precioUnitario = number_format(
                    $detalle['precio_centimos'] / 100,
                    2,
                    '.',
                    ''
                );

                // Guardamos los productos comprados.
                $insertarDetalle->execute([
                    ':id_pedido' => $idPedidoConfirmado,
                    ':id_variante' => $detalle['id_variante'],
                    ':cantidad' => $detalle['cantidad'],
                    ':precio_unitario' => $precioUnitario
                ]);

                // Descontamos las unidades vendidas.
                $actualizarStock->execute([
                    ':cantidad' => $detalle['cantidad'],
                    ':id_variante' => $detalle['id_variante']
                ]);

                if ($actualizarStock->rowCount() !== 1) {
                    throw new Exception(
                        "No se ha podido actualizar el stock."
                    );
                }
            }

            // Confirmamos todas las operaciones.
            $conexion->commit();

            // Vaciamos el carrito tras guardar el pedido.
            unset($_SESSION['carrito']);
            unset($_SESSION['csrf_confirmar_pedido']);

            // Guardamos los datos para la página de confirmación.
            $_SESSION['ultimo_pedido_confirmado'] = [
                'id_pedido' => $idPedidoConfirmado,
                'total' => $totalPedido
            ];

            header("Location: pedido_confirmado.php");
            exit;

        } catch (Throwable $e) {

            // Si ocurre un error, deshacemos la transacción.
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            $errores[] =
                $e instanceof PDOException
                ? "No se ha podido registrar el pedido. Inténtalo de nuevo."
                : $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Confirmar pedido | FITSTEP</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        /* CABECERA FITSTEP */

        .cabecera-tienda {
            background: #151515;
            color: white;
            padding: 22px 5%;
        }

        .cabecera-contenido {
            max-width: 1250px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo-tienda {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 1px;
            color: white;
            text-decoration: none;
        }

        .logo-tienda span {
            color: #b8f238;
        }

        .navegacion-tienda {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .navegacion-tienda a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .navegacion-tienda a:hover {
            color: #b8f238;
        }

        /* TÍTULO */

        .cabecera-pedido {
            background: #202020;
            color: white;
            text-align: center;
            padding: 55px 20px;
        }

        .cabecera-pedido h1 {
            color: white;
            font-size: 38px;
            margin: 0 0 12px;
        }

        .cabecera-pedido p {
            color: #ddd;
            margin: 0;
        }

        /* CONTENEDOR */

        .contenedor-pedido {
            max-width: 1150px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .enlace-volver {
            display: inline-block;
            margin-bottom: 25px;
            color: #333;
            text-decoration: none;
            font-weight: 600;
        }

        .enlace-volver:hover {
            text-decoration: underline;
        }

        .titulo-seccion {
            font-size: 26px;
            margin-bottom: 25px;
        }

        /* DISTRIBUCIÓN */

        .distribucion-pedido {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(300px, 1fr);
            gap: 30px;
            align-items: start;
        }

        /* PRODUCTOS */

        .tarjeta-producto {
            display: flex;
            gap: 22px;
            align-items: center;
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 18px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .imagen-producto {
            width: 145px;
            height: 135px;
            flex-shrink: 0;
            background: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .imagen-producto img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            box-sizing: border-box;
        }

        .sin-imagen {
            color: #777;
            text-align: center;
            font-size: 13px;
            padding: 10px;
        }

        .informacion-producto {
            flex: 1;
        }

        .informacion-producto h3 {
            color: #151515;
            font-size: 20px;
            margin: 0 0 12px;
        }

        .detalle-producto {
            color: #666;
            font-size: 14px;
            margin: 6px 0;
        }

        .subtotal-producto {
            color: #151515;
            font-size: 20px;
            font-weight: 800;
            margin-top: 15px;
        }

        /* RESUMEN Y ENVÍO */

        .tarjeta-resumen {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .tarjeta-resumen h2 {
            color: #151515;
            font-size: 24px;
            margin: 0 0 25px;
        }

        .linea-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 22px 0;
            margin-bottom: 28px;
        }

        .linea-total span {
            color: #555;
            font-weight: 600;
        }

        .linea-total strong {
            color: #151515;
            font-size: 28px;
            white-space: nowrap;
        }

        .nombre-cliente {
            color: #555;
            margin-bottom: 22px;
            line-height: 1.6;
        }

        .grupo-campo {
            margin-bottom: 24px;
        }

        .grupo-campo label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 9px;
        }

        .grupo-campo input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .grupo-campo input:focus {
            outline: none;
            border-color: #86b928;
            box-shadow: 0 0 0 3px rgba(184, 242, 56, 0.2);
        }

        .boton-confirmar {
            display: block;
            width: 100%;
            background: #b8f238;
            color: #111;
            border: none;
            border-radius: 7px;
            padding: 16px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
        }

        .boton-confirmar:hover {
            background: #a4dc27;
        }

        .texto-seguridad {
            color: #777;
            font-size: 12px;
            text-align: center;
            margin-top: 15px;
            line-height: 1.6;
        }

        /* ERRORES */

        .mensaje-error {
            background: #fff0f0;
            color: #a51d2d;
            border-left: 4px solid #a51d2d;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        /* PIE DE PÁGINA */

        .pie-tienda {
            background: #151515;
            color: #aaa;
            text-align: center;
            padding: 30px 20px;
            margin-top: 60px;
            font-size: 14px;
        }

        /* DISEÑO ADAPTABLE */

        @media (max-width: 900px) {

            .distribucion-pedido {
                grid-template-columns: 1fr;
            }

            .tarjeta-resumen {
                max-width: 600px;
            }
        }

        @media (max-width: 650px) {

            .cabecera-contenido {
                flex-direction: column;
                align-items: flex-start;
            }

            .cabecera-pedido h1 {
                font-size: 30px;
            }

            .contenedor-pedido {
                padding: 0 15px;
            }

            .tarjeta-producto {
                flex-direction: column;
                align-items: flex-start;
            }

            .imagen-producto {
                width: 100%;
                height: 200px;
            }

            .tarjeta-resumen {
                padding: 22px;
            }

            .linea-total strong {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

    <!-- CABECERA -->

    <header class="cabecera-tienda">

        <div class="cabecera-contenido">

            <a href="productos.php" class="logo-tienda">
                FIT<span>STEP</span>
            </a>

            <nav class="navegacion-tienda">

                <a href="productos.php">Catálogo</a>

                <a href="carrito.php">Mi carrito</a>

                <?php if (isset($_SESSION['id_usuario'])): ?>

                    <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>

                        <a href="admin.php">
                            Administración
                        </a>

                    <?php endif; ?>

                    <a href="logout.php">Cerrar sesión</a>

                <?php else: ?>

                    <a href="login.php">Iniciar sesión</a>

                    <a href="registro.php">Registrarse</a>

                <?php endif; ?>

            </nav>

        </div>

    </header>

    <!-- TÍTULO -->

    <section class="cabecera-pedido">

        <h1>Finalizar compra</h1>

        <p>
            Revisa tu pedido y confirma tus datos de envío.
        </p>

    </section>

    <!-- CONTENIDO -->

    <main class="contenedor-pedido">

        <a href="carrito.php" class="enlace-volver">
            ← Volver al carrito
        </a>

        <?php if (!empty($errores)): ?>

            <?php foreach ($errores as $error): ?>

                <div class="mensaje-error" role="alert">
                    <?= escapar($error) ?>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

        <div class="distribucion-pedido">

            <!-- PRODUCTOS -->

            <section>

                <h2 class="titulo-seccion">
                    Productos de tu pedido
                </h2>

                <?php foreach ($productosCarrito as $producto): ?>

                    <article class="tarjeta-producto">

                        <div class="imagen-producto">

                            <?php if ($producto['imagen'] !== null): ?>

                                <img
                                    src="<?= escapar($producto['imagen']) ?>"
                                    alt="<?= escapar(
                                        $producto['marca'] . ' ' .
                                        $producto['modelo']
                                    ) ?>"
                                >

                            <?php else: ?>

                                <span class="sin-imagen">
                                    Imagen no disponible
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="informacion-producto">

                            <h3>
                                <?= escapar($producto['marca']) ?>
                                <?= escapar($producto['modelo']) ?>
                            </h3>

                            <p class="detalle-producto">
                                <strong>Talla:</strong>
                                <?= escapar($producto['talla']) ?>
                            </p>

                            <p class="detalle-producto">
                                <strong>Color:</strong>
                                <?= escapar($producto['color']) ?>
                            </p>

                            <p class="detalle-producto">
                                <strong>Cantidad:</strong>
                                <?= (int) $producto['cantidad'] ?>
                            </p>

                            <p class="subtotal-producto">
                                <?= number_format(
                                    (float) $producto['subtotal'],
                                    2,
                                    ',',
                                    '.'
                                ) ?> €
                            </p>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

            <!-- RESUMEN Y DIRECCIÓN -->

            <section class="tarjeta-resumen">

                <h2>Resumen del pedido</h2>

                <div class="linea-total">

                    <span>Total</span>

                    <strong>
                        <?= number_format(
                            $totalCarrito,
                            2,
                            ',',
                            '.'
                        ) ?> €
                    </strong>

                </div>

                <h2>Datos de envío</h2>

                <p class="nombre-cliente">
                    Cliente:
                    <strong>
                        <?= escapar($usuario['nombre']) ?>
                    </strong>
                </p>

                <?php if (empty($errores)): ?>

                    <form
                        action="confirmar_pedido.php"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= escapar(
                                $_SESSION['csrf_confirmar_pedido']
                            ) ?>"
                        >

                        <div class="grupo-campo">

                            <label for="direccion_envio">
                                Dirección de envío
                            </label>

                            <input
                                type="text"
                                id="direccion_envio"
                                name="direccion_envio"
                                maxlength="255"
                                placeholder="Introduce tu dirección completa"
                                value="<?= escapar($direccion) ?>"
                                autocomplete="street-address"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="boton-confirmar"
                            onclick="return confirm('¿Confirmas que quieres realizar esta compra?');"
                        >
                            Confirmar compra
                        </button>

                        
                    </form>

                <?php else: ?>

                    <p class="texto-seguridad">
                        Revisa los errores indicados antes de continuar.
                    </p>

                <?php endif; ?>

            </section>

        </div>

    </main>

    <!-- PIE DE PÁGINA -->

    <footer class="pie-tienda">
        FITSTEP · Tienda de zapatillas para entrenamiento funcional
    </footer>

</body>
</html>