-- ============================================================
-- Migración 001: Nuevos campos en Transacciones
-- Ejecutar una sola vez sobre la base de datos mercapp.
--
-- Añade los campos del flujo de pago ampliado:
--   metodo_pago, direccion_envio, numero_seguimiento,
--   notas_comprador, stripe_payment_intent_id
--   y los timestamps de cada paso del flujo.
-- ============================================================

-- Método de pago elegido por el comprador al aceptar
ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS metodo_pago ENUM('efectivo','transferencia','bizum','paypal','stripe','otro') NULL
  AFTER dinero_extra;

-- Dirección de envío normalizada (vía Nominatim)
ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS direccion_envio VARCHAR(300) NULL
  AFTER metodo_pago;

-- Número de seguimiento que añade el vendedor al marcar como enviado
ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS numero_seguimiento VARCHAR(100) NULL
  AFTER direccion_envio;

-- Notas del comprador (instrucciones de entrega, etc.)
ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS notas_comprador TEXT NULL
  AFTER numero_seguimiento;

-- ID del PaymentIntent de Stripe (cuando se paga con tarjeta)
ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS stripe_payment_intent_id VARCHAR(100) NULL
  AFTER notas_comprador;

-- Timestamps de cada paso del flujo
ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS fecha_aceptacion DATETIME NULL
  AFTER stripe_payment_intent_id;

ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS fecha_pago_confirmado DATETIME NULL
  AFTER fecha_aceptacion;

ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS fecha_envio DATETIME NULL
  AFTER fecha_pago_confirmado;

ALTER TABLE Transacciones
  ADD COLUMN IF NOT EXISTS fecha_entrega DATETIME NULL
  AFTER fecha_envio;

-- Verificar resultado (muestra las columnas de la tabla)
DESCRIBE Transacciones;
