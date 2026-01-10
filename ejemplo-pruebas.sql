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


