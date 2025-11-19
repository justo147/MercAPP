-- ============================
-- Poblar la BD con datos de ejemplo
-- ============================

-- Usuarios
INSERT INTO Usuario (email, nick, contraseña_hash, nombre, apellidos, telefono, rol)
VALUES
('ana@mail.com','Ana123','hash1','Ana','López','600111222','registrado'),
('carlos@mail.com','CarlosX','hash2','Carlos','García','600333444','registrado'),
('maria@mail.com','Maria88','hash3','María','Ruiz','600555666','registrado'),
('admin@mail.com','Admin','hash4','Admin','System','600999000','admin');

-- Categorías
INSERT INTO Categorias (nombre, descripcion)
VALUES
('Electrónica','Móviles, tablets, ordenadores'),
('Moda','Ropa y complementos'),
('Hogar','Muebles y decoración');

-- Productos
INSERT INTO Productos (usuario_id, categoria_id, titulo, descripcion, precio, estado_producto_id, tipo_transaccion, estado_publicacion_id, ubicacion)
VALUES
(1,1,'Tablet Samsung S7','Tablet en buen estado, 128GB',250,2,'intercambio',1,'Sevilla'),
(2,1,'Portátil Lenovo Ideapad','Portátil ligero para estudiantes',300,2,'intercambio',1,'Sevilla'),
(3,2,'Chaqueta Zara','Chaqueta de invierno, talla M',40,1,'venta',1,'Córdoba'),
(3,3,'Mesa de comedor','Mesa de madera para 6 personas',120,3,'venta',1,'Córdoba');

-- Imágenes
INSERT INTO Imagenes_prod (id_producto, url, orden)
VALUES
(1,'/img/tablet.jpg',1),
(2,'/img/lenovo.jpg',1),
(3,'/img/chaqueta.jpg',1),
(4,'/img/mesa.jpg',1);

-- Transacción (trueque tablet ↔ portátil)
INSERT INTO Transacciones (producto_id, comprador_id, vendedor_id, tipo, estado, precio_final, dinero_extra)
VALUES
(1,1,2,'intercambio','completada',0,0);

-- Detalle de intercambio
INSERT INTO Intercambio_Detalle (transaccion_id, producto_id, tipo_item)
VALUES
(1,1,'producto'),
(1,2,'producto');

-- Valoraciones
INSERT INTO Valoraciones (transaccion_id, usuario_valorador, usuario_valorado, puntuacion, comentario, fiabilidad, comunicacion, puntualidad)
VALUES
(1,1,2,5,'Muy buen trato',5,5,5),
(1,2,1,4,'Producto correcto, todo bien',4,4,4);

-- Favoritos
INSERT INTO Favoritos (usuario_id, producto_id)
VALUES
(3,1),
(3,2),
(1,3);

-- Chat y mensajes
INSERT INTO Chat (producto_id, usuario_comprador, usuario_vendedor)
VALUES (1,1,2);

INSERT INTO Mensajes (chat_id, usuario_id, contenido)
VALUES
(1,1,'Hola, me interesa tu portátil'),
(1,2,'Perfecto, yo busco una tablet'),
(1,1,'Podemos hacer el intercambio');

-- Notificaciones
INSERT INTO Notificaciones (usuario_id, tipo, contenido)
VALUES
(1,'coincidencia','Se ha encontrado un intercambio con Carlos'),
(2,'mensaje','Tienes un nuevo mensaje de Ana'),
(3,'valoracion','Has recibido una nueva valoración positiva');

-- Reportes
INSERT INTO Reportes (usuario_reportador, producto_id, motivo)
VALUES
(3,1,'La descripción parece incompleta'),
(2,3,'El producto está mal categorizado');

-- ============================
-- Consultas de prueba
-- ============================

-- 1. Listar todos los productos activos
SELECT p.id, p.titulo, p.descripcion, c.nombre AS categoria, u.nick AS vendedor
FROM Productos p
JOIN Categorias c ON p.categoria_id = c.id
JOIN Usuario u ON p.usuario_id = u.id
WHERE p.estado_publicacion_id = 1;

-- 2. Buscar coincidencias de trueque (tablet ↔ portátil)
SELECT t.id AS transaccion, u1.nick AS vendedor, u2.nick AS comprador,
       p1.titulo AS producto_vendedor, p2.titulo AS producto_comprador
FROM Transacciones t
JOIN Intercambio_Detalle d1 ON t.id = d1.transaccion_id AND d1.tipo_item='producto'
JOIN Productos p1 ON d1.producto_id = p1.id
JOIN Usuario u1 ON t.vendedor_id = u1.id
JOIN Intercambio_Detalle d2 ON t.id = d2.transaccion_id AND d2.tipo_item='producto' AND d2.producto_id <> p1.id
JOIN Productos p2 ON d2.producto_id = p2.id
JOIN Usuario u2 ON t.comprador_id = u2.id;

-- 3. Ver reputación de usuarios (vista)
SELECT * FROM vw_usuario_reputacion;

-- 4. Mensajes de un chat
SELECT m.fecha_envio, u.nick, m.contenido
FROM Mensajes m
JOIN Usuario u ON m.usuario_id = u.id
WHERE m.chat_id = 1
ORDER BY m.fecha_envio;

-- 5. Productos favoritos de María
SELECT f.fecha_guardado, p.titulo, u.nick AS vendedor
FROM Favoritos f
JOIN Productos p ON f.producto_id = p.id
JOIN Usuario u ON p.usuario_id = u.id
WHERE f.usuario_id = 3;

-- 6. Notificaciones pendientes de Ana
SELECT tipo, contenido, fecha
FROM Notificaciones
WHERE usuario_id = 1 AND leida = FALSE;

-- 7. Reportes pendientes
SELECT r.id, u.nick AS reportador, p.titulo AS producto, r.motivo, r.estado
FROM Reportes r
JOIN Usuario u ON r.usuario_reportador = u.id
LEFT JOIN Productos p ON r.producto_id = p.id
WHERE r.estado = 'pendiente';

-- 8. Ranking de usuarios mejor valorados
SELECT usuario_valorado, AVG(puntuacion) AS reputacion_media, COUNT(*) AS total_valoraciones
FROM Valoraciones
GROUP BY usuario_valorado
ORDER BY reputacion_media DESC;

-- 9. Productos por categoría con precio medio
SELECT c.nombre AS categoria, AVG(p.precio) AS precio_medio, COUNT(*) AS total_productos
FROM Productos p
JOIN Categorias c ON p.categoria_id = c.id
GROUP BY c.nombre;

-- 10. Chats activos con último mensaje
SELECT c.id AS chat_id, u1.nick AS comprador, u2.nick AS vendedor,
       (SELECT contenido FROM Mensajes m WHERE m.chat_id = c.id ORDER BY m.fecha_envio DESC LIMIT 1) AS ultimo_mensaje
FROM Chat c
JOIN Usuario u1 ON c.usuario_comprador = u1.id
JOIN Usuario u2 ON c.usuario_vendedor = u2.id
WHERE c.estado = 'activo';

-- 11. Productos publicados por cada usuario
SELECT u.nick, COUNT(p.id) AS total_productos
FROM Usuario u
LEFT JOIN Productos p ON u.id = p.usuario_id
GROUP BY u.nick;

-- 12. Transacciones completadas por usuario
SELECT u.nick, COUNT(t.id) AS transacciones_completadas
FROM Usuario u
JOIN Transacciones t ON u.id = t.comprador_id OR u.id = t.vendedor_id
WHERE t.estado = 'completada'
GROUP BY u.nick;