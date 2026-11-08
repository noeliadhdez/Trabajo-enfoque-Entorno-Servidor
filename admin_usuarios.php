<?php
session_start();

// Comprobamos que el usuario ha iniciado sesión
// y tiene permisos de administrador.
if (
    !isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';

// Recuperamos los usuarios de la base de datos.
// No consultamos las contraseñas por seguridad.
$consulta = $conexion->prepare(
    "SELECT id_usuario, nombre, correo, direccion, rol
     FROM usuarios
     ORDER BY id_usuario"
);

$consulta->execute();
$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de usuarios</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Gestión de usuarios</h1>

    <p>
        <a href="admin.php">Volver al panel de administración</a>
    </p>
    <p>
    <a href="admin_crear_usuario.php">
        + Crear nuevo usuario
    </a>
</p>
    <h2>Usuarios registrados</h2>

    <?php if (empty($usuarios)): ?>

        <p>No hay usuarios registrados.</p>

    <?php else: ?>

        <?php foreach ($usuarios as $usuario): ?>

            <div class="producto">

                <h3>
                    <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <p>
                    <strong>ID:</strong>
                    <?= (int) $usuario['id_usuario'] ?>
                </p>

                <p>
                    <strong>Correo:</strong>
                    <?= htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <p>
                    <strong>Dirección:</strong>
                    <?= htmlspecialchars($usuario['direccion'] ?? 'No indicada', ENT_QUOTES, 'UTF-8') ?>
                </p>

                <p>
                    <strong>Rol:</strong>
                    <?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?>
                </p>
               <p>
                 <a href="admin_editar_usuario.php?id=<?= (int) $usuario['id_usuario'] ?>">
                Editar usuario
                </a>
                </p>
                <p>
                  <a href="admin_eliminar_usuario.php?id=<?= (int) $usuario['id_usuario'] ?>">
                 Eliminar usuario
                 </a>
                </p>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</body>
</html>