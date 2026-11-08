<?php
session_start();

// Comprobamos que el usuario es administrador.
if (
    !isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';

$mensaje = "";
$errores = [];

// Procesamos el formulario cuando se envía.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recuperamos los datos introducidos.
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

    // Si no hay errores, guardamos el producto.
    if (empty($errores)) {

        try {
            $consulta = $conexion->prepare(
                "INSERT INTO productos (marca, modelo, precio, descripcion)
                 VALUES (:marca, :modelo, :precio, :descripcion)"
            );

            $consulta->execute([
                ':marca' => $marca,
                ':modelo' => $modelo,
                ':precio' => $precio,
                ':descripcion' => $descripcion !== '' ? $descripcion : null
            ]);

            $mensaje = "¡Producto creado correctamente!";

            // Limpiamos los campos después de guardar.
            $marca = "";
            $modelo = "";
            $precio = "";
            $descripcion = "";

        } catch (PDOException $e) {
            $errores[] = "No se ha podido guardar el producto.";
        }
    }
}

// Valores iniciales para el formulario.
$marca = $marca ?? '';
$modelo = $modelo ?? '';
$precio = $precio ?? '';
$descripcion = $descripcion ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear producto</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Crear nuevo producto</h1>

    <p>
        <a href="admin_productos.php">Volver a gestión de productos</a>
    </p>

    <?php if ($mensaje !== ''): ?>
        <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form action="admin_crear_producto.php" method="POST">

        <p>
            <label for="marca">Marca:</label><br>
            <input
                type="text"
                id="marca"
                name="marca"
                maxlength="150"
                value="<?= htmlspecialchars($marca, ENT_QUOTES, 'UTF-8') ?>"
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
                value="<?= htmlspecialchars($modelo, ENT_QUOTES, 'UTF-8') ?>"
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
                value="<?= htmlspecialchars($precio, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </p>

        <p>
            <label for="descripcion">Descripción (opcional):</label><br>
            <textarea
                id="descripcion"
                name="descripcion"
                rows="4"
                cols="40"
            ><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
        </p>

        <button type="submit">Guardar producto</button>

    </form>

</body>
</html>