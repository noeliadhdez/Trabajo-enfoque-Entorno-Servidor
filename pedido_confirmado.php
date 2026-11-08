<?php
session_start();

// Función para mostrar texto de forma segura.
function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Comprobamos que existe una compra recién confirmada.
if (
    !isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'cliente' ||
    empty($_SESSION['ultimo_pedido_confirmado'])
) {
    header("Location: productos.php");
    exit;
}

// Recuperamos los datos del pedido confirmado.
$pedido = $_SESSION['ultimo_pedido_confirmado'];

// Eliminamos el aviso para no reutilizarlo.
unset($_SESSION['ultimo_pedido_confirmado']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Compra realizada | FITSTEP</title>

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

        /* SECCIÓN PRINCIPAL */

        .seccion-confirmacion {
            min-height: 70vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 70px 20px;
            box-sizing: border-box;
        }

        .tarjeta-confirmacion {
            background: white;
            width: 100%;
            max-width: 620px;
            padding: 45px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            box-sizing: border-box;
        }

        /* ICONO DE CONFIRMACIÓN */

        .icono-exito {
            width: 85px;
            height: 85px;
            background: #b8f238;
            color: #151515;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            font-size: 46px;
            font-weight: bold;
        }

        /* TEXTOS */

        .tarjeta-confirmacion h1 {
            font-size: 32px;
            color: #151515;
            margin: 0 0 15px;
            line-height: 1.3;
        }

        .subtitulo-confirmacion {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        /* DATOS DEL PEDIDO */

        .datos-pedido {
            background: #f7f7f7;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }

        .fila-dato {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 15px 0;
        }

        .fila-dato:not(:last-child) {
            border-bottom: 1px solid #e5e5e5;
        }

        .etiqueta-dato {
            color: #666;
            font-size: 15px;
        }

        .valor-dato {
            color: #151515;
            font-size: 18px;
            font-weight: 800;
            text-align: right;
        }

        .importe-total {
            font-size: 26px;
        }

        /* BOTÓN */

        .boton-catalogo {
            display: inline-block;
            background: #b8f238;
            color: #111;
            text-decoration: none;
            font-size: 16px;
            font-weight: 800;
            padding: 16px 30px;
            border-radius: 7px;
            transition: background 0.2s;
        }

        .boton-catalogo:hover {
            background: #a4dc27;
        }

        .mensaje-gracias {
            color: #777;
            font-size: 14px;
            margin-top: 25px;
            line-height: 1.6;
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

            .seccion-confirmacion {
                padding: 40px 15px;
            }

            .tarjeta-confirmacion {
                padding: 30px 20px;
            }

            .tarjeta-confirmacion h1 {
                font-size: 26px;
            }

            .fila-dato {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }

            .valor-dato {
                text-align: left;
            }

            .importe-total {
                font-size: 24px;
            }

            .boton-catalogo {
                display: block;
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

                <a href="productos.php">
                    Catálogo
                </a>

                <a href="carrito.php">
                    Mi carrito
                </a>

                <a href="logout.php">
                    Cerrar sesión
                </a>

            </nav>

        </div>

    </header>

    <!-- CONFIRMACIÓN DE COMPRA -->

    <main class="seccion-confirmacion">

        <div class="tarjeta-confirmacion">

            <!-- ICONO -->

            <div class="icono-exito" aria-hidden="true">
                ✓
            </div>

            <!-- MENSAJE -->

            <h1>
                ¡Compra realizada correctamente!
            </h1>

            <p class="subtitulo-confirmacion">
                Tu pedido se ha registrado correctamente
                en nuestra tienda.
            </p>

            <!-- INFORMACIÓN DEL PEDIDO -->

            <div class="datos-pedido">

                <div class="fila-dato">

                    <span class="etiqueta-dato">
                        Número de pedido
                    </span>

                    <strong class="valor-dato">
                        #<?= (int) $pedido['id_pedido'] ?>
                    </strong>

                </div>

                <div class="fila-dato">

                    <span class="etiqueta-dato">
                        Importe total
                    </span>

                    <strong class="valor-dato importe-total">
                        <?= number_format(
                            (float) $pedido['total'],
                            2,
                            ',',
                            '.'
                        ) ?> €
                    </strong>

                </div>

                <div class="fila-dato">

                    <span class="etiqueta-dato">
                        Estado del pedido
                    </span>

                    <strong class="valor-dato">
                        Confirmado
                    </strong>

                </div>

            </div>

            <!-- BOTÓN -->

            <a href="productos.php" class="boton-catalogo">
                Seguir comprando
            </a>

            <p class="mensaje-gracias">
                ¡Gracias por confiar en FITSTEP!
                <br>
                

        </div>

    </main>

    <!-- PIE DE PÁGINA -->

    <footer class="pie-tienda">
        FITSTEP · Tienda de zapatillas para entrenamiento funcional
    </footer>

</body>
</html>