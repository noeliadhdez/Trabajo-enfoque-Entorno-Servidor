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

// Función para mostrar texto de forma segura.
function escapar($texto) {
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

$errores = [];
$mensaje = "";

// Recibimos el identificador del usuario.
$id_usuario = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Comprobamos que el identificador sea válido.
if (!$id_usuario || $id_usuario <= 0) {
    header("Location: admin_usuarios.php");
    exit;
}

// Impedimos que el administrador elimine su propia cuenta.
if ($id_usuario === (int) $_SESSION['id_usuario']) {
    $errores[] = "No puedes eliminar tu propia cuenta.";
}

// Buscamos al usuario.
$consulta = $conexion->prepare(
    "SELECT id_usuario, nombre, correo, rol
     FROM usuarios
     WHERE id_usuario = :id_usuario"
);

$consulta->execute([
    ':id_usuario' => $id_usuario
]);

$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

// Comprobamos que exista.
if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit;
}

// Comprobamos si tiene pedidos asociados.
$consultaPedidos = $conexion->prepare(
    "SELECT COUNT(*)
     FROM pedidos
     WHERE id_usuario = :id_usuario"
);

$consultaPedidos->execute([
    ':id_usuario' => $id_usuario
]);

$totalPedidos = (int) $consultaPedidos->fetchColumn();

if ($totalPedidos > 0) {
    $errores[] = "Este usuario tiene pedidos asociados y no se puede eliminar.";
}

// Procesamos la confirmación.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($errores)) {

        try {

            $eliminar = $conexion->prepare(
                "DELETE FROM usuarios
                 WHERE id_usuario = :id_usuario
                 AND id_usuario <> :administrador"
            );

            $eliminar->execute([
                ':id_usuario' => $id_usuario,
                ':administrador' => (int) $_SESSION['id_usuario']
            ]);

            if ($eliminar->rowCount() === 1) {
                header("Location: admin_usuarios.php");
                exit;
            }

            $errores[] = "No se ha podido eliminar el usuario.";

        } catch (PDOException $e) {

            $errores[] = "No se ha podido eliminar el usuario. Puede tener datos relacionados.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Eliminar usuario</title>

    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Eliminar usuario</h1>

    <p>
        <a href="admin_usuarios.php">
            Volver a gestión de usuarios
        </a>
    </p>

    <div class="producto">

        <h2>Datos del usuario</h2>

        <p>
            <strong>Nombre:</strong>
            <?= escapar($usuario['nombre']) ?>
        </p>

        <p>
            <strong>Correo:</strong>
            <?= escapar($usuario['correo']) ?>
        </p>

        <p>
            <strong>Rol:</strong>
            <?= escapar($usuario['rol']) ?>
        </p>

        <?php if (!empty($errores)): ?>

            <?php foreach ($errores as $error): ?>

                <p style="color: red;">
                    <?= escapar($error) ?>
                </p>

            <?php endforeach; ?>

        <?php else: ?>

            <p style="color: red;">
                <strong>
                    ¿Estás seguro de que quieres eliminar este usuario?
                </strong>
            </p>

            <p>
                Esta operación no se puede deshacer.
            </p>

            <form
                action="admin_eliminar_usuario.php?id=<?= (int) $id_usuario ?>"
                method="POST"
                onsubmit="return confirm('¿Confirmas que deseas eliminar este usuario?');"
            >

                <button type="submit">
                    Confirmar eliminación
                </button>

            </form>

        <?php endif; ?>

    </div>

</body>
</html>