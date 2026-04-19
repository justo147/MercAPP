-- Migración 003: añadir coordenadas geográficas a Productos
-- Ejecutar en XAMPP: phpMyAdmin > base de datos > SQL

ALTER TABLE Productos
    ADD COLUMN lat DECIMAL(10,7) NULL AFTER ubicacion,
    ADD COLUMN lon DECIMAL(10,7) NULL AFTER lat;
