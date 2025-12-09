-- Opcional: limpia la BD si existe
DROP DATABASE IF EXISTS MercApp;
CREATE DATABASE MercApp
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE MercApp;

-- -----------------------------
-- Tablas de soporte (normalización ligera) en vez de utilizar ENUM
-- -----------------------------

-- Estados del producto (normalizado)
CREATE TABLE EstadoProducto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO EstadoProducto (nombre)
VALUES ('nuevo'), ('como nuevo'), ('bueno'), ('regular');

-- Estados de publicación (normalizado)
CREATE TABLE EstadoPublicacion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO EstadoPublicacion (nombre)
VALUES ('activo'), ('pausado'), ('vendido');

-- -----------------------------
-- Usuarios y categorías
-- -----------------------------

CREATE TABLE Usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) UNIQUE NOT NULL,
  email_verificado TINYINT(1) DEFAULT 0,
  verify_token VARCHAR(64) NULL,
  reset_token VARCHAR(64) NULL,
  reset_expires DATETIME NULL,
  contraseña_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100),
  telefono VARCHAR(20),
  foto_perfil VARCHAR(255),
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('activo','suspendido','eliminado') DEFAULT 'activo',
  rol ENUM('registrado','admin') DEFAULT 'registrado'
) ENGINE=InnoDB;

CREATE TABLE Categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) UNIQUE NOT NULL,
  descripcion TEXT,
  icono VARCHAR(255)
) ENGINE=InnoDB;

-- -----------------------------
-- Productos e imágenes
-- -----------------------------

CREATE TABLE Productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  categoria_id INT NOT NULL,
  titulo VARCHAR(120) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2),
  estado_producto_id INT NOT NULL,
  tipo_transaccion ENUM('venta','intercambio','mixto') NOT NULL,
  fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado_publicacion_id INT NOT NULL,
  ubicacion VARCHAR(120),
  FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES Categorias(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (estado_producto_id) REFERENCES EstadoProducto(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (estado_publicacion_id) REFERENCES EstadoPublicacion(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Índices útiles para búsqueda
CREATE INDEX idx_productos_categoria ON Productos(categoria_id);
CREATE INDEX idx_productos_estado_prod ON Productos(estado_producto_id);
CREATE INDEX idx_productos_estado_pub ON Productos(estado_publicacion_id);
CREATE INDEX idx_productos_ubicacion ON Productos(ubicacion);
CREATE INDEX idx_productos_precio ON Productos(precio);
CREATE INDEX idx_productos_fecha ON Productos(fecha_publicacion);

CREATE TABLE Imagenes_prod (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_producto INT NOT NULL,
  url VARCHAR(255) NOT NULL,
  orden INT DEFAULT 1,
  FOREIGN KEY (id_producto) REFERENCES Productos(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------
-- Deseos (búsquedas para trueque)
-- -----------------------------

CREATE TABLE Deseos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  categoria_id INT,
  producto_id INT NULL, -- opcional: deseo asociado a un producto concreto
  etiquetas TEXT,
  estado_producto_id INT,
  FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES Categorias(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES Productos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (estado_producto_id) REFERENCES EstadoProducto(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------
-- Transacciones y detalle de intercambio
-- -----------------------------

CREATE TABLE Transacciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,      -- producto principal (del vendedor)
  comprador_id INT NOT NULL,
  vendedor_id INT NOT NULL,
  tipo ENUM('venta','intercambio','mixto') NOT NULL,
  estado ENUM('pendiente','aceptada','completada','cancelada') DEFAULT 'pendiente',
  fecha_transaccion DATETIME DEFAULT CURRENT_TIMESTAMP,
  precio_final DECIMAL(10,2) DEFAULT 0.00, -- usado si venta o mixto
  dinero_extra DECIMAL(10,2) DEFAULT 0.00, -- diferencia aportada en mixto
  FOREIGN KEY (producto_id) REFERENCES Productos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (comprador_id) REFERENCES Usuario(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (vendedor_id) REFERENCES Usuario(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_no_auto_transaccion CHECK (comprador_id <> vendedor_id)
) ENGINE=InnoDB;

-- Para soportar múltiples productos por trueque
CREATE TABLE Intercambio_Detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaccion_id INT NOT NULL,
  producto_id INT NULL, -- NULL si es dinero
  tipo_item ENUM('producto','dinero') NOT NULL,
  cantidad_dinero DECIMAL(10,2) DEFAULT 0.00,
  FOREIGN KEY (transaccion_id) REFERENCES Transacciones(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES Productos(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------
-- Valoraciones (reputación granular)
-- -----------------------------

CREATE TABLE Valoraciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaccion_id INT NOT NULL,
  usuario_valorador INT NOT NULL,
  usuario_valorado INT NOT NULL,
  puntuacion INT NOT NULL, -- 1-5
  comentario TEXT,
  fecha_valoracion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fiabilidad INT,    -- 1-5
  comunicacion INT,  -- 1-5
  puntualidad INT,   -- 1-5
  FOREIGN KEY (transaccion_id) REFERENCES Transacciones(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usuario_valorador) REFERENCES Usuario(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (usuario_valorado) REFERENCES Usuario(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Índices para agregaciones rápidas
CREATE INDEX idx_valoraciones_valorado ON Valoraciones(usuario_valorado);
CREATE INDEX idx_valoraciones_transaccion ON Valoraciones(transaccion_id);

-- Vista de reputación (media por usuario)
CREATE VIEW vw_usuario_reputacion AS
SELECT
  u.id AS usuario_id,
  COALESCE(AVG(v.puntuacion), 0) AS reputacion_media,
  COALESCE(AVG(v.fiabilidad), 0) AS fiabilidad_media,
  COALESCE(AVG(v.comunicacion), 0) AS comunicacion_media,
  COALESCE(AVG(v.puntualidad), 0) AS puntualidad_media,
  COUNT(v.id) AS total_valoraciones
FROM Usuario u
LEFT JOIN Valoraciones v ON v.usuario_valorado = u.id
GROUP BY u.id;

-- -----------------------------
-- Favoritos
-- -----------------------------

CREATE TABLE Favoritos (
  usuario_id INT NOT NULL,
  producto_id INT NOT NULL,
  fecha_guardado DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, producto_id),
  FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES Productos(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------
-- Chat y mensajes
-- -----------------------------

CREATE TABLE Chat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT,
  usuario_comprador INT NOT NULL,
  usuario_vendedor INT NOT NULL,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('activo','cerrado') DEFAULT 'activo',
  FOREIGN KEY (producto_id) REFERENCES Productos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (usuario_comprador) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usuario_vendedor) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE Mensajes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chat_id INT NOT NULL,
  usuario_id INT NOT NULL,
  contenido TEXT NOT NULL,
  fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
  leido BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (chat_id) REFERENCES Chat(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_mensajes_chat_fecha ON Mensajes(chat_id, fecha_envio);

-- -----------------------------
-- Notificaciones y reportes
-- -----------------------------

CREATE TABLE Notificaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  tipo ENUM('mensaje','coincidencia','valoracion','moderacion') NOT NULL,
  contenido TEXT,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  leida BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE Reportes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_reportador INT NOT NULL,
  producto_id INT,
  motivo TEXT,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente','revisado','rechazado') DEFAULT 'pendiente',
  admin_id INT NULL, -- moderador que revisa el reporte
  FOREIGN KEY (usuario_reportador) REFERENCES Usuario(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES Productos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES Usuario(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------
-- Buenas prácticas adicionales
-- -----------------------------

-- Garantiza que puntuaciones estén en rango 1-5
ALTER TABLE Valoraciones
  ADD CONSTRAINT chk_puntuacion CHECK (puntuacion BETWEEN 1 AND 5),
  ADD CONSTRAINT chk_fiabilidad CHECK (fiabilidad IS NULL OR (fiabilidad BETWEEN 1 AND 5)),
  ADD CONSTRAINT chk_comunicacion CHECK (comunicacion IS NULL OR (comunicacion BETWEEN 1 AND 5)),
  ADD CONSTRAINT chk_puntualidad CHECK (puntualidad IS NULL OR (puntualidad BETWEEN 1 AND 5));
