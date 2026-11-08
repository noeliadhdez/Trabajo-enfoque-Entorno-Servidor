<?php
session_start();

// Comprobamos que el usuario haya iniciado sesión
// y que tenga permisos de administrador.
if (
    !isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit;
}

// Recuperamos el nombre del administrador.
$nombre = $_SESSION['nombre'] ?? 'Administrador';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de administración</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        .panel-admin {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }

        .opciones-admin {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .tarjeta-admin {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .tarjeta-admin h2 {
            font-size: 21px;
            margin-bottom: 15px;
        }

        .tarjeta-admin p {
            color: #555;
            margin-bottom: 25px;
        }

        .boton-admin {
            display: inline-block;
            background-color: #222;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
        }

        .boton-admin:hover {
            background-color: #444;
        }

        .cerrar-sesion {
            display: inline-block;
            margin-top: 30px;
            color: #b00020;
            font-weight: bold;
        }
    </style>

</head>

<body>

    <main class="panel-admin">

        <h1>Panel de administración</h1>

        <p>
            Bienvenido,
            <strong>
                <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
            </strong>
        </p>

        <p>
            Desde aquí puedes gestionar los productos
            y los usuarios de la tienda.
        </p>

        <div class="opciones-admin">

            <!-- Gestión de productos -->

            <div class="tarjeta-admin">

                <h2>👟 Gestión de productos</h2>

                <p>
                    Consulta, crea, modifica y elimina
                    las zapatillas disponibles.
                </p>

                <a
                    href="admin_productos.php"
                    class="boton-admin"
                >
                    Gestionar productos
                </a>

            </div>

            <!-- Gestión de usuarios -->

            <div class="tarjeta-admin">

                <h2>👤 Gestión de usuarios</h2>

                <p>
                    Administra las cuentas de clientes
                    y administradores.
                </p>

                <a
                    href="admin_usuarios.php"
                    class="boton-admin"
                >
                    Gestionar usuarios
                </a>

            </div>

            <!-- Catálogo -->

            <div class="tarjeta-admin">

                <h2>🛍️ Catálogo de zapatillas</h2>

                <p>
                    Consulta los productos que
                    aparecen en la tienda.
                </p>

                <a
                    href="productos.php"
                    class="boton-admin"
                >
                    Ver catálogo
                </a>

            </div>

        </div>

        <p>
            <a href="logout.php" class="cerrar-sesion">
                Cerrar sesión
            </a>
        </p>

    </main>

</body>
</html>