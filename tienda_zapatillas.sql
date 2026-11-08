-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 08-11-2026 a las 21:12:22
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tienda_zapatillas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_variante` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle`, `id_pedido`, `id_variante`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 1, 115.99);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(30) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `direccion_envio` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_usuario`, `fecha`, `estado`, `total`, `direccion_envio`) VALUES
(1, 2, '2026-11-08 19:29:56', 'confirmado', 115.99, 'Calle Arjona, Sevilla');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `marca` varchar(150) NOT NULL,
  `modelo` varchar(150) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `marca`, `modelo`, `precio`, `descripcion`) VALUES
(1, 'Nike', 'Metcon 9', 115.99, 'Zapatillas de CrossFit diseñadas para entrenamientos de fuerza, levantamiento de pesas y ejercicios funcionales.'),
(2, 'Nike', 'Metcon 10', 130.00, 'Zapatillas con mejor ligereza, agilidad y estabilidad necesarias para potenciar tu entrenamiento funcional'),
(3, 'Reebok', 'NanoXV', 125.00, 'Zapatillas ideales para deportistas que buscan una única zapatilla para todo el Wod.'),
(6, 'Adidas', 'Dropset 4', 145.00, 'Zapatillas diseñadas para entrenamientos de fuerza que combinan levantamientos de peso, salto y acondicionamiento físico.'),
(7, 'Under Armour', 'TriBase Reign 6', 115.00, 'Zapatillas de entrenamiento de alta intensidad diseñadas específicamente para CrossFit, halterofilia y entrenamientos funcionales en el gimnasio.'),
(8, 'Reebok', 'Nanoflex 3', 70.00, 'Zapatilla más economica con las mejores prestaciones'),
(10, 'Velites', 'Hybrid 1.0', 144.99, 'Zapatillas oficiales de los CrossFit Games: estables al levantar, agarre en rope climb y cómodas al correr.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `rol` enum('cliente','admin') NOT NULL DEFAULT 'cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contraseña`, `direccion`, `rol`) VALUES
(1, 'Administrador', 'admin@tienda.local', '$2y$10$eiveRdxNPunxBb1tuYXP5uofX8TBNggKbx9UkSdZ4gwOQ5yvnKSoi', NULL, 'admin'),
(2, 'Pepe', 'pepe@correo.es', '$2y$10$O3gN48FGA/lOufUoGQ76Oe35GuPb4LPVDhkJl9Vcqg4wY/y1dnSlK', NULL, 'cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `variantes`
--

CREATE TABLE `variantes` (
  `id_variante` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `talla` decimal(3,1) NOT NULL,
  `color` varchar(50) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `variantes`
--

INSERT INTO `variantes` (`id_variante`, `id_producto`, `talla`, `color`, `stock`) VALUES
(1, 1, 38.0, 'Negro', 5),
(2, 1, 39.0, 'Negro', 3),
(3, 1, 40.0, 'Negro', 8),
(4, 1, 41.0, 'Negro', 6),
(5, 1, 42.0, 'Negro', 4),
(6, 2, 38.0, 'Morado', 5),
(7, 2, 39.0, 'Morado', 7),
(8, 2, 40.0, 'Morado', 8),
(9, 2, 41.0, 'Morado', 6),
(10, 2, 42.0, 'Morado', 4),
(11, 3, 38.0, 'Burdeos', 4),
(12, 3, 39.0, 'Burdeos', 5),
(13, 3, 40.0, 'Burdeos', 7),
(14, 3, 41.0, 'Burdeos', 6),
(15, 3, 42.0, 'Burdeos', 3),
(16, 6, 38.0, 'Gris', 5),
(17, 6, 39.0, 'Gris', 4),
(18, 6, 40.0, 'Gris', 8),
(19, 6, 41.0, 'Gris', 7),
(20, 6, 42.0, 'Gris', 5),
(21, 7, 38.0, 'Azul', 3),
(22, 7, 39.0, 'Azul', 5),
(23, 7, 40.0, 'Azul', 6),
(24, 7, 41.0, 'Azul', 5),
(25, 7, 42.0, 'Azul', 4),
(26, 8, 38.0, 'Rojo', 4),
(27, 8, 39.0, 'Rojo', 6),
(28, 8, 40.0, 'Rojo', 7),
(29, 8, 41.0, 'Rojo', 5),
(30, 8, 42.0, 'Rojo', 3),
(31, 10, 38.0, 'Naranja', 2),
(32, 10, 39.0, 'Naranja', 3);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_variante` (`id_variante`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `variantes`
--
ALTER TABLE `variantes`
  ADD PRIMARY KEY (`id_variante`),
  ADD KEY `id_producto` (`id_producto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `variantes`
--
ALTER TABLE `variantes`
  MODIFY `id_variante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`id_variante`) REFERENCES `variantes` (`id_variante`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `variantes`
--
ALTER TABLE `variantes`
  ADD CONSTRAINT `variantes_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
