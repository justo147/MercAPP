INSERT INTO Categorias (nombre, descripcion, icono) VALUES
('Electrónica', 'Dispositivos y gadgets', 'bi bi-phone'),
('Hogar', 'Artículos para el hogar', 'bi bi-house'),
('Deportes', 'Material deportivo', 'bi bi-bicycle'),
('Moda', 'Ropa y accesorios', 'bi bi-bag');

INSERT INTO EstadoProducto (nombre) VALUES
('nuevo'),
('como nuevo'),
('bueno'),
('regular');

INSERT INTO EstadoPublicacion (nombre) VALUES
('activo'),
('pausado'),
('vendido');

INSERT INTO Productos (
  usuario_id, categoria_id, titulo, descripcion, precio,
  estado_producto_id, tipo_transaccion, estado_publicacion_id, ubicacion
) VALUES
(1, 1, 'Auriculares Bluetooth', 'Auriculares inalámbricos con cancelación de ruido.', 29.99, 2, 'venta', 1, 'Sevilla'),
(1, 3, 'Bicicleta de montaña', 'Bicicleta en buen estado, ideal para rutas.', 120.00, 3, 'venta', 1, 'Marchena'),
(1, 2, 'Lámpara de escritorio LED', 'Lámpara regulable con luz cálida y fría.', 15.50, 1, 'venta', 1, 'Córdoba');

INSERT INTO Imagenes_prod (id_producto, url, orden) VALUES
-- Auriculares Bluetooth (id_producto = 1)
(1, 'uploads/products/auriculares1.jfif', 1),
(1, 'uploads/products/auriculares2.jfif', 2),

-- Bicicleta de montaña (id_producto = 2)
(2, 'uploads/products/bici1.jfif', 1),
(2, 'uploads/products/bici2.jfif', 2),

-- Lámpara LED (id_producto = 3)
(3, 'uploads/products/lampara1.jfif', 1);

-- introduccion 40 productos  e imagenes para id 1

INSERT INTO Productos 
(usuario_id, categoria_id, titulo, descripcion, precio, estado_producto_id, tipo_transaccion, estado_publicacion_id, ubicacion)
VALUES
(1, 1, 'iPhone 12 Pro Max', 'Smartphone de alta gama en perfecto estado.', 799.99, 2, 'venta', 1, 'Sevilla'),
(1, 1, 'Samsung Galaxy S21', 'Móvil potente con gran cámara.', 599.00, 3, 'venta', 1, 'Córdoba'),
(1, 1, 'Xiaomi Redmi Note 11', 'Ideal para uso diario.', 199.00, 4, 'venta', 1, 'Granada'),
(1, 1, 'iPad Air 2020', 'Tablet ligera y potente.', 450.00, 2, 'venta', 1, 'Málaga'),
(1, 1, 'MacBook Pro 2019', 'Portátil profesional con gran rendimiento.', 1200.00, 3, 'venta', 1, 'Sevilla'),
(1, 1, 'Monitor LG Ultrawide', 'Pantalla panorámica ideal para multitarea.', 250.00, 3, 'venta', 1, 'Jaén'),
(1, 1, 'Teclado mecánico Redragon', 'Switches rojos, muy cómodo.', 45.00, 1, 'venta', 1, 'Huelva'),
(1, 1, 'Ratón Logitech G502', 'Ratón gaming de precisión.', 35.00, 2, 'venta', 1, 'Cádiz'),
(1, 1, 'Auriculares Sony WH-1000XM4', 'Cancelación de ruido de primera.', 220.00, 2, 'venta', 1, 'Sevilla'),
(1, 1, 'Smartwatch Amazfit GTS 3', 'Reloj inteligente con GPS.', 90.00, 3, 'venta', 1, 'Córdoba'),

(1, 2, 'Sofá de 3 plazas gris', 'Muy cómodo y en buen estado.', 180.00, 3, 'venta', 1, 'Sevilla'),
(1, 2, 'Mesa de comedor extensible', 'Perfecta para familias.', 120.00, 2, 'venta', 1, 'Málaga'),
(1, 2, 'Lámpara LED de pie', 'Iluminación cálida.', 25.00, 1, 'venta', 1, 'Granada'),
(1, 2, 'Silla ergonómica de oficina', 'Ideal para largas jornadas.', 90.00, 2, 'venta', 1, 'Córdoba'),
(1, 2, 'Microondas Samsung 800W', 'Funciona perfectamente.', 40.00, 3, 'venta', 1, 'Jaén'),
(1, 2, 'Cafetera Dolce Gusto', 'Incluye cápsulas.', 30.00, 3, 'venta', 1, 'Huelva'),
(1, 2, 'Ventilador de torre Cecotec', 'Silencioso y potente.', 35.00, 2, 'venta', 1, 'Cádiz'),
(1, 2, 'Estantería de madera', '5 niveles, muy resistente.', 50.00, 2, 'venta', 1, 'Sevilla'),
(1, 2, 'Alfombra beige 200x300', 'Suave y elegante.', 60.00, 2, 'venta', 1, 'Córdoba'),
(1, 2, 'Juego de sábanas 150cm', 'Algodón suave.', 20.00, 1, 'venta', 1, 'Granada'),

(1, 3, 'Balón de fútbol Adidas', 'Tamaño oficial.', 25.00, 1, 'venta', 1, 'Sevilla'),
(1, 3, 'Bicicleta de montaña', 'Suspensión delantera.', 180.00, 3, 'venta', 1, 'Córdoba'),
(1, 3, 'Raqueta de tenis Wilson', 'Ligera y resistente.', 45.00, 2, 'venta', 1, 'Málaga'),
(1, 3, 'Guantes de boxeo Everlast', '12 oz, poco uso.', 35.00, 2, 'venta', 1, 'Jaén'),
(1, 3, 'Patinete deportivo', 'Ideal para ciudad.', 60.00, 3, 'venta', 1, 'Huelva'),
(1, 3, 'Cinta de correr plegable', 'Perfecta para casa.', 250.00, 3, 'venta', 1, 'Sevilla'),
(1, 3, 'Pesas ajustables 20kg', 'Muy prácticas.', 50.00, 2, 'venta', 1, 'Cádiz'),
(1, 3, 'Casco de ciclismo', 'Talla M.', 20.00, 1, 'venta', 1, 'Granada'),
(1, 3, 'Mochila de senderismo', '30L, impermeable.', 35.00, 2, 'venta', 1, 'Córdoba'),
(1, 3, 'Tabla de surf', 'Buena flotabilidad.', 120.00, 3, 'venta', 1, 'Málaga'),

(1, 4, 'Zapatillas Nike Air Force 1', 'Clásicas y cómodas.', 70.00, 3, 'venta', 1, 'Sevilla'),
(1, 4, 'Sudadera Adidas Essentials', 'Talla L.', 30.00, 2, 'venta', 1, 'Córdoba'),
(1, 4, 'Chaqueta vaquera Levi’s', 'Original.', 50.00, 3, 'venta', 1, 'Granada'),
(1, 4, 'Pantalón cargo beige', 'Talla M.', 25.00, 2, 'venta', 1, 'Málaga'),
(1, 4, 'Camiseta básica Uniqlo', 'Algodón premium.', 10.00, 1, 'venta', 1, 'Jaén'),
(1, 4, 'Gafas de sol Ray-Ban', 'Modelo Aviator.', 80.00, 2, 'venta', 1, 'Huelva'),
(1, 4, 'Reloj Casio Vintage', 'Clásico y elegante.', 20.00, 1, 'venta', 1, 'Cádiz'),
(1, 4, 'Mochila Herschel', 'Muy resistente.', 40.00, 2, 'venta', 1, 'Sevilla'),
(1, 4, 'Botas Timberland', 'Talla 42.', 90.00, 3, 'venta', 1, 'Córdoba'),
(1, 4, 'Jersey de lana gris', 'Muy calentito.', 25.00, 2, 'venta', 1, 'Granada')
;

