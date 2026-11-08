<?php
session_start();

// Conexión con MySQL.
require_once 'conexion.php';

// Función para mostrar texto de forma segura.
function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Recuperamos los productos ordenados.
$consulta = $conexion->prepare(
    "SELECT * FROM productos ORDER BY id_producto"
);

$consulta->execute();
$productos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Recuperamos las variantes.
$consultaVariantes = $conexion->prepare(
    "SELECT id_variante, id_producto, talla, color, stock
     FROM variantes
     ORDER BY talla, color"
);

$consultaVariantes->execute();
$variantes = $consultaVariantes->fetchAll(PDO::FETCH_ASSOC);

// Agrupamos las variantes por producto.
$variantesPorProducto = [];

foreach ($variantes as $variante) {
    $variantesPorProducto[$variante['id_producto']][] = $variante;
}

// Relacionamos cada producto con su fotografía.
// Utilizamos los ID originales de la base de datos.
$imagenesProductos = [
    1 => 'nike-metcon-9.jpg',
    2 => 'nike-metcon-10.jpg',
    3 => 'reebok-nano-x.jpg',
    6 => 'adidas-dropset-4.jpg',
    7 => 'under-armour-tribase-reign-6.jpg',
    8 => 'reebok-nanoflex-3.jpg',
    10 => 'velites-hybrid-1.jpg'
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catálogo de zapatillas CrossFit</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        /* Diseño específico del catálogo */

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

        .hero-catalogo {
            background: #202020;
            color: white;
            padding: 65px 20px;
            text-align: center;
        }

       .hero-catalogo h1 {
            color: #ffffff;
            font-size: clamp(28px, 5vw, 48px);
            font-weight: 800;
            text-align: center;
            line-height: 1.2;
        }

.hero-catalogo h1 span {
    color: #b8f238;
}

        .hero-catalogo p {
            color: #dddddd;
            font-size: 17px;
            max-width: 650px;
            margin: auto;
        }

        .contenedor-catalogo {
            max-width: 1250px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .titulo-seccion {
            margin-bottom: 30px;
            font-size: 28px;
        }

        .rejilla-productos {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
        }

        .tarjeta-producto {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .tarjeta-producto:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .imagen-producto {
            height: 270px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .imagen-producto img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 18px;
            box-sizing: border-box;
        }

        .imagen-no-disponible {
            color: #777;
            font-size: 15px;
            text-align: center;
            padding: 20px;
        }

        .contenido-producto {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .marca-producto {
            color: #666;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 7px;
        }

        .nombre-producto {
            font-size: 23px;
            margin: 0 0 12px;
            color: #151515;
        }

        .descripcion-producto {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
            flex: 1;
        }

        .precio-producto {
            font-size: 26px;
            font-weight: 800;
            color: #151515;
            margin-bottom: 22px;
        }

        .formulario-producto {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .formulario-producto label {
            font-weight: 600;
            font-size: 14px;
        }

        .formulario-producto select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            background: white;
            font-size: 14px;
        }

        .boton-carrito {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 7px;
            background: #b8f238;
            color: #111;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .boton-carrito:hover {
            background: #a4dc27;
        }

        .boton-carrito:disabled {
            background: #ddd;
            color: #777;
            cursor: not-allowed;
        }

        .pie-tienda {
            background: #151515;
            color: #aaa;
            text-align: center;
            padding: 30px 20px;
            margin-top: 60px;
            font-size: 14px;
        }

        @media (max-width: 950px) {
            .rejilla-productos {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .rejilla-productos {
                grid-template-columns: 1fr;
            }

            .cabecera-contenido {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-catalogo {
                padding: 45px 20px;
            }

            .imagen-producto {
                height: 240px;
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

    <!-- PRESENTACIÓN -->

    <section class="hero-catalogo">

        <h1>
            ENTRENA SIN <span>LÍMITES</span>
        </h1>

        <p>
            Descubre nuestra selección de zapatillas
            para CrossFit y entrenamiento funcional.
        </p>

    </section>

    <!-- CATÁLOGO -->

    <main class="contenedor-catalogo">

        <h2 class="titulo-seccion">
            Nuestras zapatillas
        </h2>

        <?php if (empty($productos)): ?>

            <p>No hay productos disponibles.</p>

        <?php else: ?>

            <div class="rejilla-productos">

                <?php foreach ($productos as $producto): ?>

                    <?php
                    $idProducto = (int) $producto['id_producto'];

                    $variantesProducto =
                        $variantesPorProducto[$idProducto] ?? [];

                    $hayStock = false;

                    foreach ($variantesProducto as $variante) {
                        if ((int) $variante['stock'] > 0) {
                            $hayStock = true;
                            break;
                        }
                    }

                    $nombreImagen = $imagenesProductos[$idProducto] ?? null;

                    $rutaImagen = $nombreImagen
                        ? 'img/productos/' . $nombreImagen
                        : null;

                    $existeImagen = $rutaImagen !== null &&
                        is_file(__DIR__ . '/' . $rutaImagen);
                    ?>

                    <article class="tarjeta-producto">

                        <div class="imagen-producto">

                            <?php if ($existeImagen): ?>

                                <img
                                    src="<?= escapar($rutaImagen) ?>"
                                    alt="<?= escapar(
                                        $producto['marca'] . ' ' .
                                        $producto['modelo']
                                    ) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="imagen-no-disponible">
                                    👟<br>
                                    Imagen no disponible
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="contenido-producto">

                            <div class="marca-producto">
                                <?= escapar($producto['marca']) ?>
                            </div>

                            <h3 class="nombre-producto">
                                <?= escapar($producto['modelo']) ?>
                            </h3>

                            <p class="descripcion-producto">
                                <?= escapar($producto['descripcion'] ?? '') ?>
                            </p>

                            <div class="precio-producto">
                                <?= number_format(
                                    (float) $producto['precio'],
                                    2,
                                    ',',
                                    '.'
                                ) ?> €
                            </div>

                            <form
                                action="carrito.php"
                                method="POST"
                                class="formulario-producto"
                            >

                                <label for="talla-<?= $idProducto ?>">
                                    Elige tu talla y color
                                </label>

                                <select
                                    name="id_variante"
                                    id="talla-<?= $idProducto ?>"
                                    required
                                    <?= !$hayStock ? 'disabled' : '' ?>
                                >

                                    <option value="">
                                        Seleccionar talla
                                    </option>

                                    <?php foreach ($variantesProducto as $variante): ?>

                                        <option
                                            value="<?= (int) $variante['id_variante'] ?>"
                                            <?= (int) $variante['stock'] <= 0 ? 'disabled' : '' ?>
                                        >
                                            Talla <?= escapar($variante['talla']) ?>
                                            —
                                            <?= escapar($variante['color']) ?>
                                            (<?= (int) $variante['stock'] ?> disponibles)
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button
                                    type="submit"
                                    class="boton-carrito"
                                    <?= !$hayStock ? 'disabled' : '' ?>
                                >
                                    <?= $hayStock
                                        ? 'Añadir al carrito'
                                        : 'Sin existencias' ?>
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

    <!-- PIE DE PÁGINA -->

    <footer class="pie-tienda">
        FITSTEP · Tienda de zapatillas para entrenamiento funcional
    </footer>

</body>
</html>