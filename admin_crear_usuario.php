<?php
session_start();

// Comprobamos que el usuario tenga permisos de administrador.
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

$nombre = "";
$correo = "";
$direccion = "";
$rol = "cliente";

// Procesamos el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');
    $rol = $_POST['rol'] ?? 'cliente';

    // Validamos los campos obligatorios.
    if ($nombre === '' || $correo === '' || $contrasena === '') {
        $errores[] = "Debes completar los campos obligatorios.";
    }

    // Comprobamos la longitud de los campos.
    if (mb_strlen($nombre) > 100) {
        $errores[] = "El nombre no puede superar los 100 caracteres.";
    }

    if (mb_strlen($correo) > 150) {
        $errores[] = "El correo no puede superar los 150 caracteres.";
    }

    if (mb_strlen($direccion) > 255) {
        $errores[] = "La dirección no puede superar los 255 caracteres.";
    }

    // Validamos el correo electrónico.
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Introduce un correo electrónico válido.";
    }

    // Comprobamos la longitud mínima de la contraseña.
    if ($contrasena !== '' && strlen($contrasena) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres.";
    }

    // Solo permitimos los dos roles definidos en MySQL.
    if (!in_array($rol, ['cliente', 'admin'], true)) {
        $errores[] = "El rol seleccionado no es válido.";
    }

    if (empty($errores)) {

        // Comprobamos si el correo ya existe.
        $consulta = $conexion->prepare(
            "SELECT id_usuario
             FROM usuarios
             WHERE correo = :correo"
        );

        $consulta->execute([
            ':correo' => $correo
        ]);

        if ($consulta->fetch()) {

            $errores[] = "Ya existe un usuario con ese correo electrónico.";

        } else {

            // Generamos el hash de la contraseña.
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);

            try {

                // Insertamos el nuevo usuario.
                $insertar = $conexion->prepare(
                    "INSERT INTO usuarios
                        (nombre, correo, `contraseña`, direccion, rol)
                     VALUES
                        (:nombre, :correo, :contrasena, :direccion, :rol)"
                );

                $insertar->execute([
                    ':nombre' => $nombre,
                    ':correo' => $correo,
                    ':contrasena' => $hash,
                    ':direccion' => $direccion !== '' ? $direccion : null,
                    ':rol' => $rol
                ]);

                $mensaje = "¡Usuario creado correctamente!";

                // Limpiamos el formulario.
                $nombre = "";
                $correo = "";
                $direccion = "";
                $rol = "cliente";

            } catch (PDOException $e) {

                $errores[] = "No se ha podido crear el usuario.";
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
    <title>Crear usuario</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <h1>Crear nuevo usuario</h1>

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

        <form action="admin_crear_usuario.php" method="POST">

            <p>
                <label for="nombre">Nombre:</label><br>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
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
                    value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </p>

            <p>
                <label for="contrasena">Contraseña:</label><br>
                <input
                    type="password"
                    id="contrasena"
                    name="contrasena"
                    minlength="8"
                    required
                >
            </p>

            <p>
                <label for="direccion">Dirección (opcional):</label><br>
                <input
                    type="text"
                    id="direccion"
                    name="direccion"
                    maxlength="255"
                    value="<?= htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8') ?>"
                >
            </p>

            <p>
                <label for="rol">Tipo de usuario:</label><br>

                <select id="rol" name="rol" required>

                    <option
                        value="cliente"
                        <?= $rol === 'cliente' ? 'selected' : '' ?>
                    >
                        Cliente
                    </option>

                    <option
                        value="admin"
                        <?= $rol === 'admin' ? 'selected' : '' ?>
                    >
                        Administrador
                    </option>

                </select>
            </p>

            <button type="submit">Guardar usuario</button>

        </form>

    </div>

</body>
</html>