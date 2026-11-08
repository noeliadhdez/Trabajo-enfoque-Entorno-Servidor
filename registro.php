<?php
session_start();

require_once 'conexion.php';

$mensaje = "";
$registroCorrecto = false;

$nombre = "";
$correo = "";
$direccion = "";

// Función para mostrar texto de forma segura.
function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Procesamos el formulario cuando se envía.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');

    // Comprobamos los campos obligatorios.
    if ($nombre === '' || $correo === '' || $contrasena === '') {

        $mensaje = "Debes completar los campos obligatorios.";

    } elseif (
        mb_strlen($nombre) > 100 ||
        mb_strlen($correo) > 150 ||
        mb_strlen($direccion) > 255
    ) {

        $mensaje = "Uno de los campos supera la longitud permitida.";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "El correo electrónico no es válido.";

    } elseif (strlen($contrasena) < 8) {

        $mensaje = "La contraseña debe tener al menos 8 caracteres.";

    } else {

        // Comprobamos si el correo ya está registrado.
        $consulta = $conexion->prepare(
            "SELECT id_usuario
             FROM usuarios
             WHERE correo = :correo"
        );

        $consulta->execute([
            ':correo' => $correo
        ]);

        if ($consulta->fetch()) {

            $mensaje = "Ya existe un usuario con ese correo electrónico.";

        } else {

            // Generamos un hash de la contraseña.
            $hash = password_hash(
                $contrasena,
                PASSWORD_DEFAULT
            );

            try {

                // Registramos siempre al usuario como cliente.
                $consulta = $conexion->prepare(
                    "INSERT INTO usuarios
                        (nombre, correo, `contraseña`, direccion, rol)
                     VALUES
                        (:nombre, :correo, :contrasena, :direccion, 'cliente')"
                );

                $consulta->execute([
                    ':nombre' => $nombre,
                    ':correo' => $correo,
                    ':contrasena' => $hash,
                    ':direccion' => $direccion !== '' ? $direccion : null
                ]);

                $mensaje = "¡Cuenta creada correctamente!";
                $registroCorrecto = true;

                // Limpiamos el formulario.
                $nombre = "";
                $correo = "";
                $direccion = "";

            } catch (PDOException $e) {

                $mensaje = "No se ha podido completar el registro.";
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

    <title>Crear cuenta | FITSTEP</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        /* CABECERA FITSTEP */

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

        /* FORMULARIO DE REGISTRO */

        .seccion-registro {
            min-height: 75vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
            box-sizing: border-box;
        }

        .tarjeta-registro {
            background: white;
            width: 100%;
            max-width: 480px;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }

        .tarjeta-registro h1 {
            color: #151515;
            font-size: 30px;
            text-align: center;
            margin: 0 0 12px;
        }

        .subtitulo-registro {
            color: #666;
            font-size: 15px;
            text-align: center;
            margin-bottom: 30px;
        }

        .grupo-campo {
            margin-bottom: 22px;
        }

        .grupo-campo label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .grupo-campo input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .grupo-campo input:focus {
            outline: none;
            border-color: #86b928;
            box-shadow: 0 0 0 3px rgba(184, 242, 56, 0.2);
        }

        .texto-ayuda {
            display: block;
            color: #777;
            font-size: 12px;
            margin-top: 7px;
        }

        .boton-registro {
            display: block;
            width: 100%;
            padding: 15px;
            background: #b8f238;
            color: #111;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
        }

        .boton-registro:hover {
            background: #a4dc27;
        }

        .mensaje-registro {
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .mensaje-error {
            background: #fff0f0;
            color: #a51d2d;
            border-left: 4px solid #a51d2d;
        }

        .mensaje-exito {
            background: #eaf8d3;
            color: #325b0c;
            border-left: 4px solid #86b928;
        }

        .enlaces-registro {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            line-height: 1.8;
        }

        .enlaces-registro a {
            color: #222;
            font-weight: 700;
        }

        .enlaces-registro a:hover {
            color: #75a61c;
        }

        /* PIE DE PÁGINA */

        .pie-tienda {
            background: #151515;
            color: #aaa;
            text-align: center;
            padding: 30px 20px;
            font-size: 14px;
        }

        /* ADAPTACIÓN A MÓVILES */

        @media (max-width: 650px) {

            .cabecera-contenido {
                flex-direction: column;
                align-items: flex-start;
            }

            .tarjeta-registro {
                padding: 30px 22px;
            }

            .seccion-registro {
                padding: 35px 15px;
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

                    <a href="logout.php">
                        Cerrar sesión
                    </a>

                <?php else: ?>

                    <a href="login.php">
                        Iniciar sesión
                    </a>

                    <a href="registro.php">
                        Registrarse
                    </a>

                <?php endif; ?>

            </nav>

        </div>

    </header>

    <!-- REGISTRO -->

    <main class="seccion-registro">

        <div class="tarjeta-registro">

            <h1>Crea tu cuenta</h1>

            <p class="subtitulo-registro">
                Únete a FITSTEP y encuentra tus próximas
                zapatillas de entrenamiento.
            </p>

            <?php if ($mensaje !== ''): ?>

                <div
                    class="mensaje-registro <?= $registroCorrecto
                        ? 'mensaje-exito'
                        : 'mensaje-error' ?>"
                    role="status"
                >
                    <?= escapar($mensaje) ?>
                </div>

            <?php endif; ?>

            <?php if ($registroCorrecto): ?>

                <div class="enlaces-registro">

                    <p>
                        Tu cuenta ya está preparada.
                    </p>

                    <a href="login.php" class="boton-registro">
                        Iniciar sesión
                    </a>

                </div>

            <?php else: ?>

                <form action="registro.php" method="POST">

                    <div class="grupo-campo">

                        <label for="nombre">
                            Nombre
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            maxlength="100"
                            autocomplete="name"
                            placeholder="Tu nombre"
                            value="<?= escapar($nombre) ?>"
                            required
                        >

                    </div>

                    <div class="grupo-campo">

                        <label for="correo">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            maxlength="150"
                            autocomplete="email"
                            placeholder="tu@email.com"
                            value="<?= escapar($correo) ?>"
                            required
                        >

                    </div>

                    <div class="grupo-campo">

                        <label for="contrasena">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="contrasena"
                            name="contrasena"
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Crea una contraseña"
                            required
                        >

                        <small class="texto-ayuda">
                            Mínimo 8 caracteres.
                        </small>

                    </div>

                    <div class="grupo-campo">

                        <label for="direccion">
                            Dirección (opcional)
                        </label>

                        <input
                            type="text"
                            id="direccion"
                            name="direccion"
                            maxlength="255"
                            autocomplete="street-address"
                            placeholder="Tu dirección de envío"
                            value="<?= escapar($direccion) ?>"
                        >

                    </div>

                    <button
                        type="submit"
                        class="boton-registro"
                    >
                        Crear cuenta
                    </button>

                </form>

                <div class="enlaces-registro">

                    <p>
                        ¿Ya tienes una cuenta?
                        <a href="login.php">
                            Inicia sesión
                        </a>
                    </p>

                    <p>
                        <a href="productos.php">
                            ← Volver al catálogo
                        </a>
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </main>

    <!-- PIE DE PÁGINA -->

    <footer class="pie-tienda">
        FITSTEP · Tienda de zapatillas para entrenamiento funcional
    </footer>

</body>
</html>