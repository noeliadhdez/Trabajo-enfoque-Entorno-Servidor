<?php
session_start();

// Solo los administradores pueden acceder.
if (
    !isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';

// Función para mostrar los datos de forma segura.
function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Recuperamos el ID del producto desde la URL.
$id_producto = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id_producto || $id_producto <= 0) {
    header("Location: admin_productos.php");
    exit;
}

// Buscamos el producto.
$consultaProducto = $conexion->prepare(
    "SELECT id_producto, marca, modelo, precio
     FROM productos
     WHERE id_producto = :id_producto"
);

$consultaProducto->execute([
    ':id_producto' => $id_producto
]);

$producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header("Location: admin_productos.php");
    exit;
}

// Generamos el token CSRF.
if (empty($_SESSION['csrf_variantes'])) {
    $_SESSION['csrf_variantes'] = bin2hex(random_bytes(32));
}

$errores = [];
$mensaje = $_SESSION['mensaje_variantes'] ?? '';
unset($_SESSION['mensaje_variantes']);

// Valores iniciales del formulario.
$talla = '';
$color = '';
$stock = '';

// Procesamos los formularios.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (
        !is_string($tokenRecibido) ||
        !hash_equals($_SESSION['csrf_variantes'], $tokenRecibido)
    ) {
        $errores[] = "Solicitud no válida. Recarga la página.";
    }

    $accion = $_POST['accion'] ?? '';

    // ------------------------------------------------
    // AÑADIR VARIANTE
    // ------------------------------------------------

    if ($accion === 'crear') {

        $talla = trim($_POST['talla'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $stock = trim($_POST['stock'] ?? '');

        // Validamos la talla.
        if (
            !preg_match('/^\d{1,2}(\.\d)?$/', $talla) ||
            (float) $talla <= 0
        ) {
            $errores[] = "Introduce una talla válida, por ejemplo 38 o 38.5.";
        }

        // Validamos el color.
        if ($color === '' || mb_strlen($color) > 50) {
            $errores[] = "Introduce un color válido de máximo 50 caracteres.";
        }

        // Validamos el stock.
        if (
            filter_var(
                $stock,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            ) === false
        ) {
            $errores[] = "El stock debe ser un número entero igual o mayor que cero.";
        }

        if (empty($errores)) {

            // Comprobamos que no exista la misma talla y color.
            $comprobar = $conexion->prepare(
                "SELECT id_variante
                 FROM variantes
                 WHERE id_producto = :id_producto
                   AND talla = :talla
                   AND color = :color"
            );

            $comprobar->execute([
                ':id_producto' => $id_producto,
                ':talla' => $talla,
                ':color' => $color
            ]);

            if ($comprobar->fetch()) {

                $errores[] = "Esta combinación de talla y color ya existe.";

            } else {

                try {

                    $insertar = $conexion->prepare(
                        "INSERT INTO variantes
                            (id_producto, talla, color, stock)
                         VALUES
                            (:id_producto, :talla, :color, :stock)"
                    );

                    $insertar->execute([
                        ':id_producto' => $id_producto,
                        ':talla' => $talla,
                        ':color' => $color,
                        ':stock' => (int) $stock
                    ]);

                    $_SESSION['mensaje_variantes'] =
                        "¡Variante añadida correctamente!";

                    $_SESSION['csrf_variantes'] =
                        bin2hex(random_bytes(32));

                    header(
                        "Location: admin_variantes.php?id=" . $id_producto
                    );
                    exit;

                } catch (PDOException $e) {

                    $errores[] = "No se ha podido añadir la variante.";
                }
            }
        }
    }

    // ------------------------------------------------
    // MODIFICAR VARIANTE
    // ------------------------------------------------

    elseif ($accion === 'editar') {

        $id_variante = filter_var(
            $_POST['id_variante'] ?? null,
            FILTER_VALIDATE_INT
        );

        $nuevoStock = trim($_POST['stock'] ?? '');

        if (!$id_variante || $id_variante <= 0) {
            $errores[] = "La variante seleccionada no es válida.";
        }

        if (
            filter_var(
                $nuevoStock,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0]]
            ) === false
        ) {
            $errores[] = "Introduce un stock válido.";
        }

        if (empty($errores)) {

            $actualizar = $conexion->prepare(
                "UPDATE variantes
                 SET stock = :stock
                 WHERE id_variante = :id_variante
                   AND id_producto = :id_producto"
            );

            $actualizar->execute([
                ':stock' => (int) $nuevoStock,
                ':id_variante' => $id_variante,
                ':id_producto' => $id_producto
            ]);

            $_SESSION['mensaje_variantes'] =
                "¡Stock actualizado correctamente!";

            $_SESSION['csrf_variantes'] =
                bin2hex(random_bytes(32));

            header(
                "Location: admin_variantes.php?id=" . $id_producto
            );
            exit;
        }
    }

    // ------------------------------------------------
    // ELIMINAR VARIANTE
    // ------------------------------------------------

    elseif ($accion === 'eliminar') {

        $id_variante = filter_var(
            $_POST['id_variante'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id_variante || $id_variante <= 0) {
            $errores[] = "La variante seleccionada no es válida.";
        }

        if (empty($errores)) {

            // Comprobamos si la variante figura en algún pedido.
            $comprobarPedidos = $conexion->prepare(
                "SELECT COUNT(*)
                 FROM detalle_pedido
                 WHERE id_variante = :id_variante"
            );

            $comprobarPedidos->execute([
                ':id_variante' => $id_variante
            ]);

            if ((int) $comprobarPedidos->fetchColumn() > 0) {

                $errores[] =
                    "No puedes eliminar una variante asociada a pedidos.";

            } else {

                try {

                    $eliminar = $conexion->prepare(
                        "DELETE FROM variantes
                         WHERE id_variante = :id_variante
                           AND id_producto = :id_producto"
                    );

                    $eliminar->execute([
                        ':id_variante' => $id_variante,
                        ':id_producto' => $id_producto
                    ]);

                    $_SESSION['mensaje_variantes'] =
                        "¡Variante eliminada correctamente!";

                    $_SESSION['csrf_variantes'] =
                        bin2hex(random_bytes(32));

                    header(
                        "Location: admin_variantes.php?id=" . $id_producto
                    );
                    exit;

                } catch (PDOException $e) {

                    $errores[] =
                        "No se ha podido eliminar la variante.";
                }
            }
        }
    }

    else {
        $errores[] = "Acción no válida.";
    }
}

// Consultamos todas las variantes del producto.
$consultaVariantes = $conexion->prepare(
    "SELECT id_variante, talla, color, stock
     FROM variantes
     WHERE id_producto = :id_producto
     ORDER BY talla, color"
);

$consultaVariantes->execute([
    ':id_producto' => $id_producto
]);

$variantes = $consultaVariantes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestionar variantes</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        .contenedor-variantes {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }

        .tarjeta-variante {
            background: white;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .formulario-linea {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 12px;
        }

        .formulario-linea label {
            display: block;
            margin-bottom: 5px;
        }

        .formulario-linea input {
            padding: 8px;
            max-width: 120px;
        }

        .boton-eliminar {
            background: #a51d2d;
            color: white;
            border: 0;
            padding: 10px 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        .mensaje-error {
            color: #b00020;
        }

        .mensaje-exito {
            color: green;
        }
    </style>
</head>

<body>

<main class="contenedor-variantes">

    <h1>Gestionar tallas, colores y stock</h1>

    <p>
        <a href="admin_productos.php">
            ← Volver a gestión de productos
        </a>
    </p>

    <h2>
        <?= escapar($producto['marca']) ?>
        <?= escapar($producto['modelo']) ?>
    </h2>

    <p>
        Precio:
        <strong>
            <?= number_format(
                (float) $producto['precio'],
                2,
                ',',
                '.'
            ) ?> €
        </strong>
    </p>

    <?php if ($mensaje !== ''): ?>
        <p class="mensaje-exito">
            <?= escapar($mensaje) ?>
        </p>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <p class="mensaje-error">
            <?= escapar($error) ?>
        </p>
    <?php endforeach; ?>

    <h2>Añadir nueva variante</h2>

    <div class="tarjeta-variante">

        <form
            action="admin_variantes.php?id=<?= (int) $id_producto ?>"
            method="POST"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapar($_SESSION['csrf_variantes']) ?>"
            >

            <input type="hidden" name="accion" value="crear">

            <p>
                <label for="talla">Talla:</label><br>
                <input
                    type="number"
                    id="talla"
                    name="talla"
                    min="1"
                    max="99.9"
                    step="0.5"
                    value="<?= escapar($talla) ?>"
                    required
                >
            </p>

            <p>
                <label for="color">Color:</label><br>
                <input
                    type="text"
                    id="color"
                    name="color"
                    maxlength="50"
                    value="<?= escapar($color) ?>"
                    required
                >
            </p>

            <p>
                <label for="stock">Stock:</label><br>
                <input
                    type="number"
                    id="stock"
                    name="stock"
                    min="0"
                    step="1"
                    value="<?= escapar($stock) ?>"
                    required
                >
            </p>

            <button type="submit">
                Añadir variante
            </button>

        </form>

    </div>

    <h2>Variantes existentes</h2>

    <?php if (empty($variantes)): ?>

        <p>
            Este producto todavía no tiene tallas ni colores configurados.
        </p>

    <?php else: ?>

        <?php foreach ($variantes as $variante): ?>

            <div class="tarjeta-variante">

                <h3>
                    Talla <?= escapar($variante['talla']) ?>
                    — <?= escapar($variante['color']) ?>
                </h3>

                <p>
                    Stock actual:
                    <strong><?= (int) $variante['stock'] ?></strong>
                </p>

                <!-- Modificar stock -->
                <form
                    action="admin_variantes.php?id=<?= (int) $id_producto ?>"
                    method="POST"
                    class="formulario-linea"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapar($_SESSION['csrf_variantes']) ?>"
                    >

                    <input type="hidden" name="accion" value="editar">

                    <input
                        type="hidden"
                        name="id_variante"
                        value="<?= (int) $variante['id_variante'] ?>"
                    >

                    <div>
                        <label>
                            Nuevo stock:
                            <input
                                type="number"
                                name="stock"
                                min="0"
                                step="1"
                                value="<?= (int) $variante['stock'] ?>"
                                required
                            >
                        </label>
                    </div>

                    <button type="submit">
                        Guardar stock
                    </button>

                </form>

                <!-- Eliminar variante -->
                <form
                    action="admin_variantes.php?id=<?= (int) $id_producto ?>"
                    method="POST"
                    onsubmit="return confirm('¿Seguro que quieres eliminar esta variante?');"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapar($_SESSION['csrf_variantes']) ?>"
                    >

                    <input type="hidden" name="accion" value="eliminar">

                    <input
                        type="hidden"
                        name="id_variante"
                        value="<?= (int) $variante['id_variante'] ?>"
                    >

                    <button type="submit" class="boton-eliminar">
                        Eliminar variante
                    </button>

                </form>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>