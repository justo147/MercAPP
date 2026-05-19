<?php

/**
 * Envuelve el contenido en el layout base de email de MercApp.
 */
function mailLayout(string $content): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MercApp</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          <!-- Header -->
          <tr>
            <td style="background-color:#04aa86;border-radius:12px 12px 0 0;padding:28px 40px;text-align:center;">
              <span style="font-size:26px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">
                🛍 MercApp
              </span>
              <p style="margin:4px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">El mercado de segunda mano que conecta personas</p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="background-color:#ffffff;padding:40px;border-left:1px solid #e8ecf0;border-right:1px solid #e8ecf0;">
              {$content}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:#f8f9fb;border:1px solid #e8ecf0;border-top:none;border-radius:0 0 12px 12px;padding:24px 40px;text-align:center;">
              <p style="margin:0 0 6px;color:#94a3b8;font-size:12px;">
                Este es un correo automático, por favor no respondas a este mensaje.
              </p>
              <p style="margin:0;color:#94a3b8;font-size:12px;">
                © 2026 MercApp · Todos los derechos reservados
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Email de verificación de cuenta tras el registro.
 */
function mailVerificacion(string $nombre, string $verifyUrl): string
{
    $content = <<<HTML
      <h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1a1d27;">
        ¡Bienvenido/a a MercApp, {$nombre}!
      </h1>
      <p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;">
        Gracias por registrarte. Solo queda un paso: confirma tu dirección de correo electrónico para activar tu cuenta y empezar a comprar y vender.
      </p>

      <div style="background-color:#f0fdf9;border:1px solid #bbf0e2;border-radius:8px;padding:20px;margin-bottom:28px;">
        <p style="margin:0;font-size:14px;color:#047857;line-height:1.5;">
          <strong>🔒 Tu cuenta está casi lista.</strong> Haz clic en el botón de abajo para verificar tu correo. El enlace es válido durante <strong>24 horas</strong>.
        </p>
      </div>

      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center" style="padding-bottom:28px;">
            <a href="{$verifyUrl}"
               style="display:inline-block;background-color:#04aa86;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 40px;border-radius:8px;letter-spacing:0.3px;">
              ✅ Confirmar mi cuenta
            </a>
          </td>
        </tr>
      </table>

      <p style="margin:0 0 8px;font-size:13px;color:#94a3b8;">
        Si el botón no funciona, copia y pega este enlace en tu navegador:
      </p>
      <p style="margin:0;font-size:12px;word-break:break-all;">
        <a href="{$verifyUrl}" style="color:#04aa86;">{$verifyUrl}</a>
      </p>

      <hr style="border:none;border-top:1px solid #f1f5f9;margin:28px 0;">
      <p style="margin:0;font-size:13px;color:#94a3b8;">
        ¿No has creado esta cuenta? Puedes ignorar este correo con total seguridad.
      </p>
HTML;

    return mailLayout($content);
}

/**
 * Email de recuperación de contraseña.
 */
function mailRecuperarContrasena(string $nombre, string $resetUrl): string
{
    $content = <<<HTML
      <div style="text-align:center;margin-bottom:28px;">
        <div style="display:inline-block;background-color:#fef3c7;border-radius:50%;width:64px;height:64px;line-height:64px;font-size:30px;">
          🔑
        </div>
      </div>

      <h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1a1d27;text-align:center;">
        Restablecer contraseña
      </h1>
      <p style="margin:0 0 24px;font-size:15px;color:#64748b;line-height:1.6;text-align:center;">
        Hola{$nombre}, hemos recibido una solicitud para restablecer la contraseña de tu cuenta en MercApp.
      </p>

      <div style="background-color:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:20px;margin-bottom:28px;">
        <p style="margin:0;font-size:14px;color:#92400e;line-height:1.5;">
          <strong>⏱ Este enlace caduca en 1 hora.</strong> Si no solicitaste este cambio, puedes ignorar este correo; tu contraseña seguirá siendo la misma.
        </p>
      </div>

      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center" style="padding-bottom:28px;">
            <a href="{$resetUrl}"
               style="display:inline-block;background-color:#04aa86;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 40px;border-radius:8px;letter-spacing:0.3px;">
              🔐 Cambiar mi contraseña
            </a>
          </td>
        </tr>
      </table>

      <p style="margin:0 0 8px;font-size:13px;color:#94a3b8;">
        Si el botón no funciona, copia y pega este enlace en tu navegador:
      </p>
      <p style="margin:0;font-size:12px;word-break:break-all;">
        <a href="{$resetUrl}" style="color:#04aa86;">{$resetUrl}</a>
      </p>

      <hr style="border:none;border-top:1px solid #f1f5f9;margin:28px 0;">
      <p style="margin:0;font-size:13px;color:#94a3b8;">
        Por seguridad, nunca compartiremos tu contraseña ni te pediremos que la introduzcas por correo electrónico.
      </p>
HTML;

    return mailLayout($content);
}

/**
 * Email de transacción completada.
 *
 * @param string $nombre       Nombre del destinatario.
 * @param string $rol          'comprador' o 'vendedor'.
 * @param string $producto     Título del producto.
 * @param string $precio       Precio formateado (ej. "35.00 €") o "Trueque".
 * @param string $fecha        Fecha de la transacción formateada.
 */
function mailTransaccionCompletada(string $nombre, string $rol, string $producto, string $precio = '', string $fecha = ''): string
{
    $esComprador = $rol === 'comprador';

    $icono   = $esComprador ? '📦' : '💰';
    $titulo  = $esComprador ? '¡Tu pedido ha sido confirmado!' : '¡Tu venta se ha completado!';
    $mensaje = $esComprador
        ? "Has confirmado la recepción de tu pedido. Esperamos que estés satisfecho/a con tu compra."
        : "El comprador ha confirmado la entrega. La transacción ha finalizado correctamente.";

    $badgeColor = $esComprador ? '#0d6efd' : '#04aa86';
    $badgeLabel = $esComprador ? 'Comprador' : 'Vendedor';

    $precioFila = $precio
        ? "<tr>
             <td style='padding:8px 0;font-size:14px;color:#64748b;border-bottom:1px solid #f1f5f9;'>Importe</td>
             <td style='padding:8px 0;font-size:14px;color:#1a1d27;font-weight:600;border-bottom:1px solid #f1f5f9;text-align:right;'>{$precio}</td>
           </tr>"
        : "";

    $fechaFila = $fecha
        ? "<tr>
             <td style='padding:8px 0;font-size:14px;color:#64748b;'>Fecha</td>
             <td style='padding:8px 0;font-size:14px;color:#1a1d27;text-align:right;'>{$fecha}</td>
           </tr>"
        : "";

    $content = <<<HTML
      <div style="text-align:center;margin-bottom:28px;">
        <div style="display:inline-block;background-color:#f0fdf9;border-radius:50%;width:64px;height:64px;line-height:64px;font-size:30px;">
          {$icono}
        </div>
      </div>

      <h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1a1d27;text-align:center;">
        {$titulo}
      </h1>
      <p style="margin:0 0 28px;font-size:15px;color:#64748b;line-height:1.6;text-align:center;">
        Hola <strong>{$nombre}</strong>, {$mensaje}
      </p>

      <!-- Detalle del pedido -->
      <div style="background-color:#f8f9fb;border:1px solid #e8ecf0;border-radius:10px;padding:24px;margin-bottom:28px;">
        <p style="margin:0 0 16px;font-size:13px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">
          Resumen de la transacción
        </p>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:8px 0;font-size:14px;color:#64748b;border-bottom:1px solid #f1f5f9;">Producto</td>
            <td style="padding:8px 0;font-size:14px;color:#1a1d27;font-weight:600;border-bottom:1px solid #f1f5f9;text-align:right;">{$producto}</td>
          </tr>
          <tr>
            <td style="padding:8px 0;font-size:14px;color:#64748b;border-bottom:1px solid #f1f5f9;">Tu rol</td>
            <td style="padding:8px 0;border-bottom:1px solid #f1f5f9;text-align:right;">
              <span style="display:inline-block;background-color:{$badgeColor};color:#fff;font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;">{$badgeLabel}</span>
            </td>
          </tr>
          {$precioFila}
          {$fechaFila}
          <tr>
            <td style="padding:8px 0;font-size:14px;color:#64748b;">Estado</td>
            <td style="padding:8px 0;text-align:right;">
              <span style="display:inline-block;background-color:#04aa86;color:#fff;font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;">✓ Completada</span>
            </td>
          </tr>
        </table>
      </div>

      <div style="background-color:#f0fdf9;border:1px solid #bbf0e2;border-radius:8px;padding:16px;margin-bottom:28px;">
        <p style="margin:0;font-size:14px;color:#047857;line-height:1.5;">
          🌟 <strong>¡Gracias por usar MercApp!</strong> Si la transacción fue de tu agrado, recuerda dejar una valoración al otro usuario.
        </p>
      </div>

      <hr style="border:none;border-top:1px solid #f1f5f9;margin:0 0 20px;">
      <p style="margin:0;font-size:13px;color:#94a3b8;text-align:center;">
        ¿Algún problema con esta transacción? Contacta con nosotros desde la plataforma.
      </p>
HTML;

    return mailLayout($content);
}
