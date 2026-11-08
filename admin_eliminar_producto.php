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

$mensaje = "";
$error = "";
$producto = null;

// Recuperamos el identificador del producto.
$id_producto = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Comprobamos que sea válido.
if (!$id_producto || $id_producto <= 0) {
    header("Location: admin_productos.php");
    exit;
}

// Consultamos el producto.
$consulta = $conexion->prepare(
    "SELECT id_producto, marca, modelo, precio
     FROM productos
     WHERE id_producto = :id_producto"
);

$consulta->execute([
    ':id_producto' => $id_producto
]);

$producto = $consulta->fetch(PDO::FETCH_ASSOC);

// Comprobamos que exista.
if (!$producto) {
    header("Location: admin_productos.php");
    exit;
}

// Comprobamos si tiene variantes asociadas.
$consultaVariantes = $conexion->prepare(
    "SELECT COUNT(*)
     FROM variantes
     WHERE id_producto = :id_producto"
);

$consultaVariantes->execute([
    ':id_producto' => $id_producto
]);

$totalVariantes = (int) $consultaVariantes->fetchColumn();

// Procesamos la confirmación.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {

        // Comprobamos nuevamente las variantes.
        $consultaVariantes->execute([
            ':id_producto' => $id_producto
        ]);

        $totalVariantes = (int) $consultaVariantes->fetchColumn();

        if ($totalVariantes > 0) {

            $error = "No se puede eliminar este producto porque tiene variantes asociadas.";

        } else {

            try {

                $eliminar = $conexion->prepare(
                    "DELETE FROM productos
                     WHERE id_producto = :id_producto"
                );

                $eliminar->execute([
                    ':id_producto' => $id_producto
                ]);

                if ($eliminar->rowCount() > 0) {
                    header("Location: admin_productos.php");
                    exit;
                }

                $error = "No se ha podido eliminar el producto.";

            } catch (PDOException $e) {

                $error = "No se puede eliminar el producto porque tiene información relacionada.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar producto</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Eliminar producto</h1>

    <p>
        <a href="admin_productos.php">Volver a gestión de productos</a>
    </p>

    <?php if ($error !== ''): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="producto">

        <h2>
            <?= htmlspecialchars($producto['marca'], ENT_QUOTES, 'UTF-8') ?>
            <?= htmlspecialchars($producto['modelo'], ENT_QUOTES, 'UTF-8') ?>
        </h2>

        <p>
            Precio:
            <?= number_format($producto['precio'], 2, ',', '.') ?> €
        </p>

        <?php if ($totalVariantes > 0): ?>

            <p>
                Este producto tiene
                <?= $totalVariantes ?>
                variantes asociadas.
            </p>

            <p>
                No se puede eliminar mientras tenga variantes relacionadas.
            </p>

        <?php else: ?>

            <p>
                ¿Estás seguro de que quieres eliminar este producto?
            </p>

            <p>
                Esta acción no se puede deshacer.
            </p>

            <form
                action="admin_eliminar_producto.php?id=<?= (int) $id_producto ?>"
                method="POST"
            >

                <button
                    type="submit"
                    name="accion"
                    value="eliminar"
                    onclick="return confirm('¿Seguro que quieres eliminar este producto?');"
                >
                    Confirmar eliminación
                </button>

            </form>

        <?php endif; ?>

    </div>

</body>
</html>