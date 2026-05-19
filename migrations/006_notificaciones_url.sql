-- Añade campo de enlace a la tabla Notificaciones para poder
-- redirigir al usuario al recurso referenciado al hacer clic.
ALTER TABLE Notificaciones
  ADD COLUMN referencia_url VARCHAR(255) NULL DEFAULT NULL AFTER contenido;
