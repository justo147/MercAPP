-- Añade el estado 'propuesta_intercambio' al flujo de transacciones.
-- Este estado se activa cuando el comprador propone un producto para intercambiar,
-- y el vendedor aún no ha aceptado ni rechazado la propuesta.

ALTER TABLE Transacciones
MODIFY COLUMN estado ENUM(
    'pendiente',
    'propuesta_intercambio',
    'aceptada',
    'pago_pendiente',
    'enviado',
    'entregado',
    'cancelada'
) DEFAULT 'pendiente';
