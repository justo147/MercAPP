<?php
require_once __DIR__ . '/../../controllers/check_auth.php';
include   __DIR__ . '/../../controllers/chat_list.php';
require_once __DIR__ . '/../../config/twig.php';

$filtros = [
    ''                => ['Todos',             'bi-chat-dots'],
    'no_leidos'       => ['No leídos',         'bi-envelope-exclamation'],
    'con_transaccion' => ['Con transacción',   'bi-arrow-left-right'],
    'sin_transaccion' => ['Sin transacción',   'bi-chat-text'],
    'abierto'         => ['Transacción activa','bi-hourglass-split'],
    'cerrado'         => ['Cerrada/Entregada', 'bi-check-circle'],
];

// Pre-process chat data for Twig
foreach ($chats as &$chat) {
    // Relative date
    $fecha = '';
    if (!empty($chat['fecha_ultimo_mensaje'])) {
        $ts   = strtotime($chat['fecha_ultimo_mensaje']);
        $diff = time() - $ts;
        if ($diff < 60)         $fecha = 'Ahora';
        elseif ($diff < 3600)   $fecha = floor($diff/60) . ' min';
        elseif ($diff < 86400)  $fecha = floor($diff/3600) . ' h';
        elseif ($diff < 604800) $fecha = floor($diff/86400) . ' d';
        else                    $fecha = date('d/m/Y', $ts);
    }
    $chat['fecha_relativa'] = $fecha;

    // Clean last message
    $msg = $chat['ultimo_mensaje'] ?? 'Sin mensajes';
    $msg = preg_replace('/^\[SISTEMA\]\s*/i', '⚙ ', $msg);
    if (mb_strlen($msg) > 65) $msg = mb_substr($msg, 0, 65) . '…';
    $chat['ultimo_msg_clean'] = htmlspecialchars($msg);
}
unset($chat);

echo $twig->render('chat_list.html.twig', [
    'chats'        => $chats,
    'filtros'      => $filtros,
    'filtroActual' => $filtroActual,
]);
