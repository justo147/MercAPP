-----------------------------------------------------
--
--HAY QUE VOLVER A HACER ESTO
--
-------------------------------------------------------
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
(3,2,'Chaqueta Zara','Chaqueta de invierno, talla M',40,1,'venta',1,'Córdoba');

-- Imágenes
INSERT INTO Imagenes_prod (id_producto, url, orden)
VALUES
(1,'/img/tablet.jpg',1),
(2,'/img/lenovo.jpg',1),
(3,'/img/chaqueta.jpg',1);

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
(3,2);

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
(2,'mensaje','Tienes un nuevo mensaje de Ana');

-- Reportes
INSERT INTO Reportes (usuario_reportador, producto_id, motivo)
VALUES
(3,1,'La descripción parece incompleta');
