<?php
session_start();
require_once 'conexion.php';

$mensaje = "";
$correo = "";

// Función para mostrar texto de forma segura.
function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Procesamos el formulario cuando el usuario lo envía.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($correo === '' || $contrasena === '') {

        $mensaje = "Debes introducir tu correo y contraseña.";

    } else {

        // Buscamos al usuario por su correo electrónico.
        $consulta = $conexion->prepare(
            "SELECT id_usuario, nombre, correo, `contraseña`, rol
             FROM usuarios
             WHERE correo = :correo"
        );

        $consulta->execute([
            ':correo' => $correo
        ]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        // Comprobamos la contraseña introducida.
        if ($usuario && password_verify($contrasena, $usuario['contraseña'])) {

            // Renovamos el identificador de sesión por seguridad.
            session_regenerate_id(true);

            // Guardamos los datos necesarios en la sesión.
            $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];

            // Redirigimos según el rol.
            if ($usuario['rol'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: productos.php");
            }

            exit;

        } else {

            $mensaje = "Correo electrónico o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | FITSTEP</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        /* DISEÑO DEL LOGIN FITSTEP */

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

        .seccion-login {
            min-height: 70vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
            box-sizing: border-box;
        }

        .tarjeta-login {
            background: white;
            width: 100%;
            max-width: 430px;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }

        .tarjeta-login h1 {
            font-size: 30px;
            color: #151515;
            text-align: center;
            margin: 0 0 12px;
        }

        .subtitulo-login {
            color: #666;
            text-align: center;
            font-size: 15px;
            margin-bottom: 30px;
        }

        .grupo-campo {
            margin-bottom: 22px;
        }

        .grupo-campo label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
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

        .boton-login {
            display: block;
            width: 100%;
            padding: 15px;
            background: #b8f238;
            color: #111;
            border: none;
            border-radius: 7px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
        }

        .boton-login:hover {
            background: #a4dc27;
        }

        .mensaje-error {
            background: #fff0f0;
            color: #a51d2d;
            border-left: 4px solid #a51d2d;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .enlaces-login {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            line-height: 1.8;
        }

        .enlaces-login a {
            color: #222;
            font-weight: 700;
        }

        .enlaces-login a:hover {
            color: #75a61c;
        }

        .pie-tienda {
            background: #151515;
            color: #aaa;
            text-align: center;
            padding: 30px 20px;
            font-size: 14px;
        }

        @media (max-width: 650px) {

            .cabecera-contenido {
                flex-direction: column;
                align-items: flex-start;
            }

            .tarjeta-login {
                padding: 30px 22px;
            }

            .seccion-login {
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

                    <a href="logout.php">Cerrar sesión</a>

                <?php else: ?>

                    <a href="login.php">Iniciar sesión</a>

                    <a href="registro.php">Registrarse</a>

                <?php endif; ?>

            </nav>

        </div>

    </header>

    <!-- FORMULARIO DE INICIO DE SESIÓN -->

    <main class="seccion-login">

        <div class="tarjeta-login">

            <h1>Iniciar sesión</h1>

            <p class="subtitulo-login">
                Accede a tu cuenta de FITSTEP
            </p>

            <?php if ($mensaje !== ''): ?>

                <div class="mensaje-error" role="alert">
                    <?= escapar($mensaje) ?>
                </div>

            <?php endif; ?>

            <form action="login.php" method="POST">

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
                        autocomplete="current-password"
                        placeholder="Introduce tu contraseña"
                        required
                    >

                </div>

                <button type="submit" class="boton-login">
                    Iniciar sesión
                </button>

            </form>

            <div class="enlaces-login">

                <p>
                    ¿Todavía no tienes cuenta?
                    <a href="registro.php">Regístrate aquí</a>
                </p>

                <p>
                    <a href="productos.php">
                        ← Volver al catálogo
                    </a>
                </p>

            </div>

        </div>

    </main>

    <!-- PIE DE PÁGINA -->

    <footer class="pie-tienda">
        FITSTEP · Tienda de zapatillas para entrenamiento funcional
    </footer>

</body>
</html>