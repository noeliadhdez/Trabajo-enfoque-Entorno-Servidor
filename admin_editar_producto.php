<?php
session_start();

// Solo los administradores pueden editar productos.
if (
    !isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';

$errores = [];
$mensaje = "";

// Recuperamos el identificador del producto desde la URL.
$id_producto = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Comprobamos que el identificador sea válido.
if (!$id_producto || $id_producto <= 0) {
    header("Location: admin_productos.php");
    exit;
}

// Consultamos los datos actuales del producto.
$consulta = $conexion->prepare(
    "SELECT id_producto, marca, modelo, precio, descripcion
     FROM productos
     WHERE id_producto = :id_producto"
);

$consulta->execute([
    ':id_producto' => $id_producto
]);

$producto = $consulta->fetch(PDO::FETCH_ASSOC);

// Si no existe el producto, volvemos al listado.
if (!$producto) {
    header("Location: admin_productos.php");
    exit;
}

// Procesamos el formulario al pulsar Guardar cambios.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validamos los campos.
    if ($marca === '' || $modelo === '' || $precio === '') {
        $errores[] = "Debes completar los campos obligatorios.";
    }

    if (strlen($marca) > 150 || strlen($modelo) > 150) {
        $errores[] = "La marca y el modelo no pueden superar los 150 caracteres.";
    }

    if (
        $precio !== '' &&
        (!is_numeric($precio) || (float) $precio <= 0 || (float) $precio > 99999999.99)
    ) {
        $errores[] = "Introduce un precio válido mayor que cero.";
    }

    // Si los datos son válidos, actualizamos el producto.
    if (empty($errores)) {

        try {

            $actualizar = $conexion->prepare(
                "UPDATE productos
                 SET marca = :marca,
                     modelo = :modelo,
                     precio = :precio,
                     descripcion = :descripcion
                 WHERE id_producto = :id_producto"
            );

            $actualizar->execute([
                ':marca' => $marca,
                ':modelo' => $modelo,
                ':precio' => $precio,
                ':descripcion' => $descripcion !== '' ? $descripcion : null,
                ':id_producto' => $id_producto
            ]);

            $mensaje = "¡Producto actualizado correctamente!";

            // Actualizamos los datos que se muestran en el formulario.
            $producto['marca'] = $marca;
            $producto['modelo'] = $modelo;
            $producto['precio'] = $precio;
            $producto['descripcion'] = $descripcion;

        } catch (PDOException $e) {

            $errores[] = "No se ha podido actualizar el producto.";
        }

    } else {

        // Conservamos los valores introducidos para corregirlos.
        $producto['marca'] = $marca;
        $producto['modelo'] = $modelo;
        $producto['precio'] = $precio;
        $producto['descripcion'] = $descripcion;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Editar producto</h1>

    <p>
        <a href="admin_productos.php">Volver a gestión de productos</a>
    </p>

    <?php if ($mensaje !== ''): ?>
        <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <div class="producto">

        <h2>
            Producto:
            <?= htmlspecialchars($producto['marca'], ENT_QUOTES, 'UTF-8') ?>
            <?= htmlspecialchars($producto['modelo'], ENT_QUOTES, 'UTF-8') ?>
        </h2>

        <form
            action="admin_editar_producto.php?id=<?= (int) $id_producto ?>"
            method="POST"
        >

            <p>
                <label for="marca">Marca:</label><br>
                <input
                    type="text"
                    id="marca"
                    name="marca"
                    maxlength="150"
                    value="<?= htmlspecialchars($producto['marca'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </p>

            <p>
                <label for="modelo">Modelo:</label><br>
                <input
                    type="text"
                    id="modelo"
                    name="modelo"
                    maxlength="150"
                    value="<?= htmlspecialchars($producto['modelo'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </p>

            <p>
                <label for="precio">Precio (€):</label><br>
                <input
                    type="number"
                    id="precio"
                    name="precio"
                    min="0.01"
                    max="99999999.99"
                    step="0.01"
                    value="<?= htmlspecialchars($producto['precio'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </p>

            <p>
                <label for="descripcion">Descripción:</label><br>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="4"
                    cols="40"
                ><?= htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </p>

            <button type="submit">Guardar cambios</button>

        </form>

    </div>

</body>
</html>