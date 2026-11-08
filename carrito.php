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

// Creamos el carrito si todavía no existe.
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$mensaje = "";

// Relacionamos los productos con sus fotografías.
$imagenesProductos = [
    1 => 'nike-metcon-9.jpg',
    2 => 'nike-metcon-10.jpg',
    3 => 'reebok-nano-x.jpg',
    6 => 'adidas-dropset-4.jpg',
    7 => 'under-armour-tribase-reign-6.jpg',
    8 => 'reebok-nanoflex-3.jpg',
    10 => 'velites-hybrid-1.jpg'
];

// Procesamos las acciones enviadas desde los formularios.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_variante = filter_input(
        INPUT_POST,
        'id_variante',
        FILTER_VALIDATE_INT
    );

    $accion = $_POST['accion'] ?? 'agregar';

    if (
        $id_variante === false ||
        $id_variante === null ||
        $id_variante <= 0
    ) {

        $mensaje = "Debes seleccionar una variante válida.";

    } else {

        // Consultamos la variante y su stock.
        $consulta = $conexion->prepare(
            "SELECT
                v.id_variante,
                v.stock,
                p.precio
             FROM variantes v
             INNER JOIN productos p
                ON v.id_producto = p.id_producto
             WHERE v.id_variante = :id_variante"
        );

        $consulta->execute([
            ':id_variante' => $id_variante
        ]);

        $variante = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$variante) {

            $mensaje = "La variante seleccionada no existe.";

        } else {

            $cantidadActual = $_SESSION['carrito'][$id_variante] ?? 0;

            switch ($accion) {

                case 'agregar':

                    if ($cantidadActual < $variante['stock']) {

                        $_SESSION['carrito'][$id_variante] =
                            $cantidadActual + 1;

                        $mensaje = "Producto añadido al carrito.";

                    } else {

                        $mensaje = "No hay más unidades disponibles.";
                    }

                    break;

                case 'aumentar':

                    if (
                        $cantidadActual > 0 &&
                        $cantidadActual < $variante['stock']
                    ) {

                        $_SESSION['carrito'][$id_variante]++;

                        $mensaje = "Cantidad actualizada.";

                    } else {

                        $mensaje = "No puedes añadir más unidades.";
                    }

                    break;

                case 'disminuir':

                    if ($cantidadActual > 1) {

                        $_SESSION['carrito'][$id_variante]--;

                        $mensaje = "Cantidad actualizada.";

                    } else {

                        $mensaje = "La cantidad mínima es 1.";
                    }

                    break;

                case 'eliminar':

                    unset($_SESSION['carrito'][$id_variante]);

                    $mensaje = "Producto eliminado del carrito.";

                    break;
            }
        }
    }
}

// Recuperamos los productos guardados en el carrito.
$productosCarrito = [];
$total = 0;

$consultaProducto = $conexion->prepare(
    "SELECT
        v.id_variante,
        v.id_producto,
        v.talla,
        v.color,
        p.marca,
        p.modelo,
        p.precio
     FROM variantes v
     INNER JOIN productos p
        ON v.id_producto = p.id_producto
     WHERE v.id_variante = :id_variante"
);

foreach ($_SESSION['carrito'] as $id_variante => $cantidad) {

    $consultaProducto->execute([
        ':id_variante' => $id_variante
    ]);

    $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);

    if ($producto) {

        $producto['cantidad'] = $cantidad;
        $producto['subtotal'] = $producto['precio'] * $cantidad;

        // Buscamos la fotografía correspondiente.
        $idProducto = (int) $producto['id_producto'];

        $nombreImagen = $imagenesProductos[$idProducto] ?? null;

        $rutaImagen = $nombreImagen
            ? 'img/productos/' . $nombreImagen
            : null;

        $producto['imagen'] = (
            $rutaImagen !== null &&
            is_file(__DIR__ . '/' . $rutaImagen)
        ) ? $rutaImagen : null;

        $total += $producto['subtotal'];

        $productosCarrito[] = $producto;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi carrito | FITSTEP</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        /* Diseño del carrito FITSTEP */

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

        .cabecera-carrito {
            background: #202020;
            color: white;
            text-align: center;
            padding: 55px 20px;
        }

        .cabecera-carrito h1 {
            color: white;
            font-size: 38px;
            margin: 0 0 12px;
        }

        .cabecera-carrito p {
            color: #ddd;
            margin: 0;
        }

        .contenedor-carrito {
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .enlace-volver {
            display: inline-block;
            margin-bottom: 25px;
            color: #333;
            font-weight: 600;
            text-decoration: none;
        }

        .enlace-volver:hover {
            text-decoration: underline;
        }

        .producto-carrito {
            display: flex;
            align-items: center;
            gap: 25px;
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .imagen-carrito {
            width: 180px;
            min-width: 180px;
            height: 160px;
            background: #f5f5f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .imagen-carrito img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            box-sizing: border-box;
        }

        .sin-imagen {
            color: #777;
            font-size: 13px;
            text-align: center;
        }

        .informacion-carrito {
            flex: 1;
        }

        .informacion-carrito h2 {
            font-size: 23px;
            color: #151515;
            margin: 0 0 12px;
        }

        .detalles-carrito {
            color: #666;
            font-size: 14px;
            margin: 6px 0;
        }

        .precio-unitario {
            font-size: 18px;
            font-weight: 700;
            margin: 15px 0;
        }

        .controles-cantidad {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .cantidad-actual {
            font-size: 16px;
            font-weight: bold;
            min-width: 25px;
            text-align: center;
        }

        .boton-cantidad {
            background: #eeeeee;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 9px 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .boton-cantidad:hover {
            background: #dddddd;
        }

        .boton-eliminar {
            background: transparent;
            border: 1px solid #c62828;
            color: #c62828;
            border-radius: 6px;
            padding: 9px 14px;
            cursor: pointer;
        }

        .boton-eliminar:hover {
            background: #fff0f0;
        }

        .subtotal-producto {
            font-size: 20px;
            font-weight: 800;
            color: #151515;
            white-space: nowrap;
        }

        .resumen-carrito {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .resumen-carrito h2 {
            font-size: 25px;
            margin-top: 0;
        }

        .linea-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            border-top: 1px solid #eee;
        }

        .linea-total strong {
            font-size: 28px;
            color: #151515;
        }

        .boton-finalizar {
            display: inline-block;
            background: #b8f238;
            color: #111;
            text-decoration: none;
            font-weight: 800;
            padding: 15px 25px;
            border-radius: 7px;
            text-align: center;
        }

        .boton-finalizar:hover {
            background: #a4dc27;
        }

        .carrito-vacio {
            background: white;
            padding: 45px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .carrito-vacio h2 {
            margin-top: 0;
        }

        .mensaje-carrito {
            background: #eaf8d3;
            border-left: 4px solid #86b928;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .pie-tienda {
            background: #151515;
            color: #aaa;
            text-align: center;
            padding: 30px 20px;
            margin-top: 60px;
            font-size: 14px;
        }

        @media (max-width: 800px) {
            .producto-carrito {
                flex-direction: column;
                align-items: flex-start;
            }

            .imagen-carrito {
                width: 100%;
                min-width: 0;
                height: 220px;
            }

            .subtotal-producto {
                align-self: flex-end;
            }
        }

        @media (max-width: 650px) {
            .cabecera-contenido {
                flex-direction: column;
                align-items: flex-start;
            }

            .cabecera-carrito h1 {
                font-size: 30px;
            }

            .contenedor-carrito {
                padding: 0 15px;
            }

            .resumen-carrito {
                padding: 22px;
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

    <section class="cabecera-carrito">

        <h1>Mi carrito de compra</h1>

        <p>Revisa tus zapatillas antes de finalizar el pedido.</p>

    </section>

    <!-- CONTENIDO -->

    <main class="contenedor-carrito">

        <a href="productos.php" class="enlace-volver">
            ← Seguir comprando
        </a>

        <?php if ($mensaje !== ''): ?>

            <div class="mensaje-carrito">
                <?= escapar($mensaje) ?>
            </div>

        <?php endif; ?>

        <?php if (empty($productosCarrito)): ?>

            <div class="carrito-vacio">

                <h2>Tu carrito está vacío</h2>

                <p>
                    Todavía no has añadido ninguna zapatilla.
                </p>

                <a href="productos.php" class="boton-finalizar">
                    Explorar zapatillas
                </a>

            </div>

        <?php else: ?>

            <?php foreach ($productosCarrito as $producto): ?>

                <article class="producto-carrito">

                    <!-- Fotografía -->

                    <div class="imagen-carrito">

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

                    <!-- Información -->

                    <div class="informacion-carrito">

                        <h2>
                            <?= escapar($producto['marca']) ?>
                            <?= escapar($producto['modelo']) ?>
                        </h2>

                        <p class="detalles-carrito">
                            <strong>Talla:</strong>
                            <?= escapar($producto['talla']) ?>
                        </p>

                        <p class="detalles-carrito">
                            <strong>Color:</strong>
                            <?= escapar($producto['color']) ?>
                        </p>

                        <p class="precio-unitario">
                            <?= number_format(
                                (float) $producto['precio'],
                                2,
                                ',',
                                '.'
                            ) ?> € / unidad
                        </p>

                        <!-- Modificar cantidades -->

                        <form
                            action="carrito.php"
                            method="POST"
                            class="controles-cantidad"
                        >

                            <input
                                type="hidden"
                                name="id_variante"
                                value="<?= (int) $producto['id_variante'] ?>"
                            >

                            <button
                                type="submit"
                                name="accion"
                                value="disminuir"
                                class="boton-cantidad"
                                aria-label="Disminuir cantidad"
                            >
                                −
                            </button>

                            <span class="cantidad-actual">
                                <?= (int) $producto['cantidad'] ?>
                            </span>

                            <button
                                type="submit"
                                name="accion"
                                value="aumentar"
                                class="boton-cantidad"
                                aria-label="Aumentar cantidad"
                            >
                                +
                            </button>

                            <button
                                type="submit"
                                name="accion"
                                value="eliminar"
                                class="boton-eliminar"
                                onclick="return confirm('¿Eliminar esta zapatilla del carrito?');"
                            >
                                Eliminar
                            </button>

                        </form>

                    </div>

                    <!-- Subtotal -->

                    <div class="subtotal-producto">
                        <?= number_format(
                            (float) $producto['subtotal'],
                            2,
                            ',',
                            '.'
                        ) ?> €
                    </div>

                </article>

            <?php endforeach; ?>

            <!-- RESUMEN DE COMPRA -->

            <section class="resumen-carrito">

                <h2>Resumen del pedido</h2>

                <div class="linea-total">

                    <span>Total</span>

                    <strong>
                        <?= number_format($total, 2, ',', '.') ?> €
                    </strong>

                </div>

                <?php if (
                    isset($_SESSION['id_usuario']) &&
                    ($_SESSION['rol'] ?? '') === 'cliente'
                ): ?>

                    <a
                        href="confirmar_pedido.php"
                        class="boton-finalizar"
                    >
                        Finalizar compra
                    </a>

                <?php else: ?>

                    <a href="login.php" class="boton-finalizar">
                        Inicia sesión como cliente para comprar
                    </a>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    </main>

    <!-- PIE DE PÁGINA -->

    <footer class="pie-tienda">
        FITSTEP · Tienda de zapatillas para entrenamiento funcional
    </footer>

</body>
</html>