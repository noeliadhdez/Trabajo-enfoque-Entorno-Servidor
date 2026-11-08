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

// Recuperamos los productos de MySQL.
$consulta = $conexion->prepare(
    "SELECT id_producto, marca, modelo, precio
     FROM productos
     ORDER BY id_producto"
);

$consulta->execute();
$productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de productos</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Gestión de productos</h1>

    <p>Bienvenido/a, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Administrador', ENT_QUOTES, 'UTF-8') ?></p>

    <p><a href="admin.php">Volver al panel de administración</a></p>
<p>
    <a href="admin_crear_producto.php">+ Crear nuevo producto</a>
</p>
    <h2>Productos registrados</h2>

    <?php foreach ($productos as $producto): ?>

        <div class="producto">

            <h3>
                <?= htmlspecialchars($producto['marca'], ENT_QUOTES, 'UTF-8') ?>
                <?= htmlspecialchars($producto['modelo'], ENT_QUOTES, 'UTF-8') ?>
            </h3>

            <p>ID: <?= (int) $producto['id_producto'] ?></p>
 <p>
      <a href="admin_editar_producto.php?id=<?= (int) $producto['id_producto'] ?>">
              Editar producto</a>
</p>
<p>
    <a href="admin_variantes.php?id=<?= (int) $producto['id_producto'] ?>">
        Gestionar tallas, colores y stock
    </a>
</p>
<p>
    <a href="admin_eliminar_producto.php?id=<?= (int) $producto['id_producto'] ?>">
        Eliminar producto
    </a>
</p>
            <p>
                Precio:
                <?= number_format($producto['precio'], 2, ',', '.') ?> €
            </p>

        </div>

    <?php endforeach; ?>

</body>
</html>