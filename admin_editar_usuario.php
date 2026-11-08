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

$errores = [];
$mensaje = "";

// Recuperamos el identificador del usuario desde la URL.
$id_usuario = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Comprobamos que sea válido.
if (!$id_usuario || $id_usuario <= 0) {
    header("Location: admin_usuarios.php");
    exit;
}

// Consultamos los datos actuales del usuario.
// No recuperamos su contraseña porque no necesitamos mostrarla.
$consulta = $conexion->prepare(
    "SELECT id_usuario, nombre, correo, direccion, rol
     FROM usuarios
     WHERE id_usuario = :id_usuario"
);

$consulta->execute([
    ':id_usuario' => $id_usuario
]);

$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit;
}

// Comprobamos si el administrador está editando su propia cuenta.
$esMiCuenta = $id_usuario === (int) $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $nuevaContrasena = $_POST['nueva_contrasena'] ?? '';

    // Validamos los campos.
    if ($nombre === '' || $correo === '') {
        $errores[] = "El nombre y el correo son obligatorios.";
    }

    if (mb_strlen($nombre) > 100) {
        $errores[] = "El nombre no puede superar los 100 caracteres.";
    }

    if (
        mb_strlen($correo) > 150 ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL)
    ) {
        $errores[] = "Introduce un correo electrónico válido.";
    }

    if (mb_strlen($direccion) > 255) {
        $errores[] = "La dirección no puede superar los 255 caracteres.";
    }

    if (!in_array($rol, ['cliente', 'admin'], true)) {
        $errores[] = "El rol seleccionado no es válido.";
    }

    // Impedimos que el administrador cambie su propio rol.
    if ($esMiCuenta && $rol !== 'admin') {
        $errores[] = "No puedes quitarte tus propios permisos de administrador.";
    }

    // La nueva contraseña es opcional.
    if ($nuevaContrasena !== '' && strlen($nuevaContrasena) < 8) {
        $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
    }

    // Comprobamos si el correo pertenece a otro usuario.
    if (empty($errores)) {

        $comprobar = $conexion->prepare(
            "SELECT id_usuario
             FROM usuarios
             WHERE correo = :correo
             AND id_usuario <> :id_usuario"
        );

        $comprobar->execute([
            ':correo' => $correo,
            ':id_usuario' => $id_usuario
        ]);

        if ($comprobar->fetch()) {
            $errores[] = "Ese correo electrónico ya pertenece a otro usuario.";
        }
    }

    if (empty($errores)) {

        try {

            // Si se introduce una nueva contraseña, actualizamos su hash.
            if ($nuevaContrasena !== '') {

                $hash = password_hash(
                    $nuevaContrasena,
                    PASSWORD_DEFAULT
                );

                $actualizar = $conexion->prepare(
                    "UPDATE usuarios
                     SET nombre = :nombre,
                         correo = :correo,
                         direccion = :direccion,
                         rol = :rol,
                         `contraseña` = :contrasena
                     WHERE id_usuario = :id_usuario"
                );

                $actualizar->execute([
                    ':nombre' => $nombre,
                    ':correo' => $correo,
                    ':direccion' => $direccion !== '' ? $direccion : null,
                    ':rol' => $rol,
                    ':contrasena' => $hash,
                    ':id_usuario' => $id_usuario
                ]);

            } else {

                // Si no se introduce contraseña, conservamos la actual.
                $actualizar = $conexion->prepare(
                    "UPDATE usuarios
                     SET nombre = :nombre,
                         correo = :correo,
                         direccion = :direccion,
                         rol = :rol
                     WHERE id_usuario = :id_usuario"
                );

                $actualizar->execute([
                    ':nombre' => $nombre,
                    ':correo' => $correo,
                    ':direccion' => $direccion !== '' ? $direccion : null,
                    ':rol' => $rol,
                    ':id_usuario' => $id_usuario
                ]);
            }

            $mensaje = "¡Usuario actualizado correctamente!";

            // Actualizamos los datos del formulario.
            $usuario['nombre'] = $nombre;
            $usuario['correo'] = $correo;
            $usuario['direccion'] = $direccion;
            $usuario['rol'] = $rol;

            // Si modificamos nuestra cuenta, actualizamos la sesión.
            if ($esMiCuenta) {
                $_SESSION['nombre'] = $nombre;
            }

        } catch (PDOException $e) {

            $errores[] = "No se ha podido actualizar el usuario.";
        }

    } else {

        // Conservamos los datos introducidos para corregirlos.
        $usuario['nombre'] = $nombre;
        $usuario['correo'] = $correo;
        $usuario['direccion'] = $direccion;
        $usuario['rol'] = $rol;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar usuario</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Editar usuario</h1>

    <p>
        <a href="admin_usuarios.php">Volver a gestión de usuarios</a>
    </p>

    <?php if ($mensaje !== ''): ?>
        <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php foreach ($errores as $error): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <div class="producto">

        <h2>
            <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>
        </h2>

        <form
            action="admin_editar_usuario.php?id=<?= (int) $id_usuario ?>"
            method="POST"
        >

            <p>
                <label for="nombre">Nombre:</label><br>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    value="<?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </p>

            <p>
                <label for="correo">Correo electrónico:</label><br>
                <input
                    type="email"
                    id="correo"
                    name="correo"
                    maxlength="150"
                    value="<?= htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </p>

            <p>
                <label for="direccion">Dirección:</label><br>
                <input
                    type="text"
                    id="direccion"
                    name="direccion"
                    maxlength="255"
                    value="<?= htmlspecialchars($usuario['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >
            </p>

            <p>
                <label for="rol">Tipo de usuario:</label><br>

                <?php if ($esMiCuenta): ?>

                    <input type="hidden" name="rol" value="admin">

                    <select id="rol" disabled>
                        <option selected>Administrador</option>
                    </select>

                    <br>
                    <small>No puedes modificar tu propio rol.</small>

                <?php else: ?>

                    <select id="rol" name="rol" required>

                        <option
                            value="cliente"
                            <?= $usuario['rol'] === 'cliente' ? 'selected' : '' ?>
                        >
                            Cliente
                        </option>

                        <option
                            value="admin"
                            <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>
                        >
                            Administrador
                        </option>

                    </select>

                <?php endif; ?>

            </p>

            <p>
                <label for="nueva_contrasena">
                    Nueva contraseña (opcional):
                </label><br>

                <input
                    type="password"
                    id="nueva_contrasena"
                    name="nueva_contrasena"
                    minlength="8"
                    autocomplete="new-password"
                >

                <br>
                <small>
                    Déjala vacía si no quieres cambiar la contraseña.
                </small>
            </p>

            <button type="submit">Guardar cambios</button>

        </form>

    </div>

</body>
</html>