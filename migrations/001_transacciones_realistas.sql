-- ============================================================
-- Migración 001: Transacciones realistas + dirección Nominatim
-- Aplica sobre BD existente (no borra datos)
-- Ejecutar una sola vez en phpMyAdmin o consola MySQL
-- ============================================================

USE mercapp;

-- 1. Ampliar ENUM de estado para incluir 'pago_pendiente'
ALTER TABLE Transacciones
  MODIFY COLUMN estado
    ENUM('pendiente','aceptada','pago_pendiente','enviado','entregado','cancelada')
    DEFAULT 'pendiente';

-- 2. Nuevos campos del flujo realista
ALTER TABLE Transacciones
  ADD COLUMN metodo_pago        ENUM('efectivo','transferencia','bizum','paypal','otro') NULL
                                AFTER dinero_extra,
  ADD COLUMN direccion_envio    VARCHAR(300) NULL
                                AFTER metodo_pago,
  ADD COLUMN numero_seguimiento VARCHAR(100) NULL
                                AFTER direccion_envio,
  ADD COLUMN notas_comprador    TEXT NULL
                                AFTER numero_seguimiento,
  ADD COLUMN fecha_aceptacion       DATETIME NULL AFTER notas_comprador,
  ADD COLUMN fecha_pago_confirmado  DATETIME NULL AFTER fecha_aceptacion,
  ADD COLUMN fecha_envio            DATETIME NULL AFTER fecha_pago_confirmado,
  ADD COLUMN fecha_entrega          DATETIME NULL AFTER fecha_envio;
