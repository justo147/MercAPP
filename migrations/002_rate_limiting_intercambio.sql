-- ============================================================
-- Migración 002: Rate limiting + campo tipo en createFromChat
-- Aplica sobre BD existente (no borra datos)
-- Ejecutar una sola vez en phpMyAdmin o consola MySQL
-- ============================================================

USE mercapp;

-- 1. Tabla de intentos de login para rate limiting por IP
CREATE TABLE IF NOT EXISTS LoginIntentos (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  ip    VARCHAR(45)  NOT NULL,
  email VARCHAR(100) DEFAULT NULL,
  fecha DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_fecha (ip, fecha)
) ENGINE=InnoDB;
