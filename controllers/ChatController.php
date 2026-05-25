<?php

require_once __DIR__ . '/../core/Controller.php';

class ChatController extends Controller
{
    public function list(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/Chat.php';

        $usuarioActual = intval($_SESSION['user_id']);
        $filtrosValidos = ['', 'no_leidos', 'con_transaccion', 'sin_transaccion', 'abierto', 'cerrado'];
        $filtroActual   = in_array($_GET['filtro'] ?? '', $filtrosValidos) ? ($_GET['filtro'] ?? '') : '';

        $chatModel = new Chat($this->conn);
        $chats     = $chatModel->getChatsByUser($usuarioActual, $filtroActual);

        $filtros = [
            ''                => ['Todos',              'bi-chat-dots'],
            'no_leidos'       => ['No leídos',          'bi-envelope-exclamation'],
            'con_transaccion' => ['Con transacción',    'bi-arrow-left-right'],
            'sin_transaccion' => ['Sin transacción',    'bi-chat-text'],
            'abierto'         => ['Transacción activa', 'bi-hourglass-split'],
            'cerrado'         => ['Cerrada/Entregada',  'bi-check-circle'],
        ];

        foreach ($chats as &$chat) {
            $fecha = '';
            if (!empty($chat['fecha_ultimo_mensaje'])) {
                $ts   = strtotime($chat['fecha_ultimo_mensaje']);
                $diff = time() - $ts;
                if ($diff < 60)         $fecha = 'Ahora';
                elseif ($diff < 3600)   $fecha = floor($diff / 60) . ' min';
                elseif ($diff < 86400)  $fecha = floor($diff / 3600) . ' h';
                elseif ($diff < 604800) $fecha = floor($diff / 86400) . ' d';
                else                    $fecha = date('d/m/Y', $ts);
            }
            $chat['fecha_relativa'] = $fecha;

            $msg = $chat['ultimo_mensaje'] ?? 'Sin mensajes';
            $msg = preg_replace('/^\[SISTEMA\]\s*/i', '⚙ ', $msg);
            if (mb_strlen($msg) > 65) $msg = mb_substr($msg, 0, 65) . '…';
            $chat['ultimo_msg_clean'] = htmlspecialchars($msg);
        }
        unset($chat);

        $this->render('chat_list.html.twig', [
            'chats'        => $chats,
            'filtros'      => $filtros,
            'filtroActual' => $filtroActual,
        ]);
    }

    public function detail(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/Chat.php';
        require_once __DIR__ . '/../models/Message.php';
        require_once __DIR__ . '/../models/Transaction.php';
        require_once __DIR__ . '/../models/Product.php';
        require_once __DIR__ . '/../models/Rating.php';
        require_once __DIR__ . '/../models/Report.php';
        require_once __DIR__ . '/../models/Notification.php';
        require_once __DIR__ . '/../config/bootstrap.php';

        $chatId        = intval($params['id'] ?? 0);
        $usuarioActual = intval($_SESSION['user_id']);

        if ($chatId <= 0) {
            $this->redirect("{$this->base}/chat");
        }

        $chatModel        = new Chat($this->conn);
        $mensajeModel     = new Message($this->conn);
        $transactionModel = new Transaction($this->conn);
        $productoModel    = new Product($this->conn);
        $ratingModel      = new Rating($this->conn);
        $reportModel      = new Report($this->conn);
        $notifModel       = new Notification($this->conn);

        if (!$chatModel->userBelongsToChat($chatId, $usuarioActual)) {
            $this->redirect("{$this->base}/chat");
        }

        $chat        = $chatModel->getById($chatId);
        $transaccion = null;
        if (!empty($chat['transaccion_id'])) {
            $transaccion = $transactionModel->getById($chat['transaccion_id']);
        }

        $esVendedor  = ($usuarioActual == $chat['usuario_vendedor']);
        $esComprador = ($usuarioActual == $chat['usuario_comprador']);

        $mostrarModalValoracion = (
            $esComprador &&
            $transaccion &&
            $transaccion['estado'] === 'entregado' &&
            !$ratingModel->hasRated($transaccion['id'], $usuarioActual)
        );

        $otroUsuarioId = $esVendedor ? $chat['usuario_comprador'] : $chat['usuario_vendedor'];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $mensajeModel->markAsRead($chatId, $usuarioActual);
            $mensajeModel->markSystemAsRead($chatId);
        }

        // Enviar mensaje
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
            $contenido = trim($_POST['mensaje'] ?? '');
            if ($contenido !== '') {
                $mensajeModel->send($chatId, $usuarioActual, $contenido);
                $chatData       = $chatModel->getById($chatId);
                $destinatarioId = ($chatData['usuario_comprador'] == $usuarioActual)
                    ? intval($chatData['usuario_vendedor'])
                    : intval($chatData['usuario_comprador']);
                $nombreRemit = htmlspecialchars($_SESSION['name'] ?? 'Alguien');
                $notifModel->create(
                    $destinatarioId,
                    'mensaje',
                    "{$nombreRemit} te ha enviado un mensaje en el chat sobre \"{$chatData['producto_titulo']}\".",
                    "{$this->base}/chat/{$chatId}"
                );
            }
            $this->redirect("{$this->base}/chat/{$chatId}");
        }

        // Valoración (AJAX)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valoracion'])) {
            header('Content-Type: application/json');
            if (!$esComprador) {
                echo json_encode(['ok' => false, 'error' => 'Solo el comprador puede valorar.']);
                exit;
            }
            $transaccionId = intval($_POST['transaccion_id'] ?? 0);
            $fiabilidad    = intval($_POST['fiabilidad']     ?? 0);
            $comunicacion  = intval($_POST['comunicacion']   ?? 0);
            $puntualidad   = intval($_POST['puntualidad']    ?? 0);
            $comentario    = trim($_POST['comentario']       ?? '');
            $transaccionVal = $transactionModel->getById($transaccionId);
            if (
                $transaccionVal &&
                $transaccionVal['estado'] === 'entregado' &&
                $usuarioActual == $transaccionVal['comprador_id'] &&
                !$ratingModel->hasRated($transaccionId, $usuarioActual) &&
                $fiabilidad   >= 1 && $fiabilidad   <= 5 &&
                $comunicacion >= 1 && $comunicacion <= 5 &&
                $puntualidad  >= 1 && $puntualidad  <= 5
            ) {
                $puntuacion = round(($fiabilidad + $comunicacion + $puntualidad) / 3);
                $ok         = $ratingModel->create(
                    $transaccionId,
                    $usuarioActual,
                    $transaccionVal['vendedor_id'],
                    $puntuacion,
                    $comentario ?: null,
                    $fiabilidad,
                    $comunicacion,
                    $puntualidad
                );
                echo json_encode(['ok' => $ok]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la valoración.']);
            }
            exit;
        }

        // Reporte desde chat
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reporte_motivo'])) {
            $motivo     = trim($_POST['reporte_motivo'] ?? '');
            $productoId = intval($chat['producto_id'] ?? 0);
            if ($motivo !== '' && $productoId > 0) {
                $reportModel->create($usuarioActual, $productoId, $motivo);
            }
            $this->redirect("{$this->base}/chat/{$chatId}?reporte=ok");
        }

        $mensajes = $mensajeModel->getByChat($chatId);
        foreach ($mensajes as &$msg) {
            if ($msg['usuario_id'] === null) {
                $msg['contenido_clean'] = preg_replace('/^\[SISTEMA\]\s*/i', '', $msg['contenido'] ?? '');
            }
            $msg['contenido_html'] = nl2br(htmlspecialchars($msg['contenido'] ?? ''));
        }
        unset($msg);

        $pasosVenta       = ['pendiente', 'aceptada', 'pago_pendiente', 'enviado', 'entregado'];
        $pasosIntercambio = ['pendiente', 'propuesta_intercambio', 'aceptada', 'pago_pendiente', 'enviado', 'entregado'];
        $pasos            = ($transaccion && $transaccion['tipo'] === 'intercambio') ? $pasosIntercambio : $pasosVenta;
        $stepIndex        = array_flip($pasos);

        $esIntercambio      = $transaccion && in_array($transaccion['tipo'] ?? '', ['intercambio', 'mixto']);
        $intercambioDetalle = $esIntercambio ? $transactionModel->getIntercambioDetalle($transaccion['id']) : [];

        $productoParaStripe      = $productoModel->getById($chat['producto_id'] ?? 0);
        $precioProducto          = floatval($productoParaStripe['precio'] ?? 0);
        $tipoTransaccionProducto = $productoParaStripe['tipo_transaccion'] ?? 'venta';

        $importeStripe = ($transaccion && $transaccion['tipo'] === 'intercambio' && $transaccion['estado'] === 'aceptada')
            ? floatval($transaccion['dinero_extra'] ?? 0)
            : $precioProducto;
        $stripeDisponible = $importeStripe >= 0.50;

        $etiquetaMetodo = [
            'efectivo'      => ['bi-cash-coin',           'Efectivo al entregar'],
            'transferencia' => ['bi-bank',                'Transferencia bancaria'],
            'bizum'         => ['bi-phone',               'Bizum'],
            'paypal'        => ['bi-paypal',              'PayPal'],
            'stripe'        => ['bi-credit-card-2-front', 'Tarjeta (Stripe) — Pagado'],
            'otro'          => ['bi-three-dots',          'Otro método'],
        ];

        $chatToasts = [];
        if (isset($_GET['reporte']) && $_GET['reporte'] === 'ok') {
            $chatToasts[] = ['success', 'Reporte enviado. Lo revisaremos próximamente.'];
        }
        $stripeErrors = [
            'stripe_no_intent'    => 'No se recibió confirmación del pago. Inténtalo de nuevo.',
            'stripe_config'       => 'El sistema de pagos con tarjeta no está configurado correctamente.',
            'stripe_pago_fallido' => 'El pago con tarjeta no fue completado o no es válido.',
            'stripe_error'        => 'Error de comunicación con Stripe. Inténtalo de nuevo.',
            'metodo_pago'         => 'Debes seleccionar un método de pago.',
            'producto_ofrecido'   => 'El producto seleccionado no es válido o no te pertenece.',
        ];
        $errCode = $_GET['error'] ?? '';
        if (isset($stripeErrors[$errCode])) {
            $chatToasts[] = ['error', $stripeErrors[$errCode]];
        }
        if (isset($_GET['stripe']) && $_GET['stripe'] === 'ok') {
            $chatToasts[] = ['success', '¡Pago con tarjeta confirmado! El vendedor recibirá el aviso para preparar el envío.'];
        }

        $this->render('chat.html.twig', [
            'chatId'                  => $chatId,
            'chat'                    => $chat,
            'transaccion'             => $transaccion,
            'mensajes'                => $mensajes,
            'esVendedor'              => $esVendedor,
            'esComprador'             => $esComprador,
            'usuarioActual'           => $usuarioActual,
            'otroUsuarioId'           => $otroUsuarioId,
            'mostrarModalValoracion'  => $mostrarModalValoracion,
            'esIntercambio'           => $esIntercambio,
            'intercambioDetalle'      => $intercambioDetalle,
            'etiquetaMetodo'          => $etiquetaMetodo,
            'stepIndex'               => $stepIndex,
            'stripeKey'               => $_ENV['STRIPE_KEY'] ?? '',
            'stripeDisponible'        => $stripeDisponible,
            'precioProducto'          => $precioProducto,
            'importeStripe'           => $importeStripe,
            'tipoTransaccionProducto' => $tipoTransaccionProducto,
            'chatToasts'              => $chatToasts,
        ]);
    }

    public function start(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/Product.php';
        require_once __DIR__ . '/../models/Chat.php';
        require_once __DIR__ . '/../models/Message.php';

        $productoId    = intval($_GET['producto_id'] ?? 0);
        $usuarioActual = intval($_SESSION['user_id']);

        if ($productoId <= 0) {
            $this->redirect("{$this->base}/home");
        }

        $productModel = new Product($this->conn);
        $chatModel    = new Chat($this->conn);

        $producto = $productModel->getById($productoId);
        if (!$producto) {
            $this->redirect("{$this->base}/home");
        }

        $vendedorId = intval($producto['usuario_id']);
        if ($vendedorId === $usuarioActual) {
            $this->redirect("{$this->base}/profile/{$usuarioActual}");
        }

        $resultado = $chatModel->getOrCreate($productoId, $usuarioActual, $vendedorId);
        $chatId    = $resultado['id'];

        if ($resultado['nuevo']) {
            $mensajeModel = new Message($this->conn);
            $mensajeModel->enviarMensajeSistema($chatId, "El comprador ha iniciado un chat contigo.");
        }

        $this->redirect("{$this->base}/chat/{$chatId}");
    }

    public function startTransaction(array $params = []): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("{$this->base}/home");
        }

        require_once __DIR__ . '/../models/Chat.php';
        require_once __DIR__ . '/../models/Product.php';
        require_once __DIR__ . '/../models/Message.php';
        require_once __DIR__ . '/../models/Transaction.php';

        $usuarioActual = intval($_SESSION['user_id']);
        $chatId        = intval($_POST['chat_id'] ?? 0);

        if ($chatId <= 0) {
            $this->redirect("{$this->base}/home");
        }

        $chatModel        = new Chat($this->conn);
        $productoModel    = new Product($this->conn);
        $mensajeModel     = new Message($this->conn);
        $transactionModel = new Transaction($this->conn);

        $chat = $chatModel->getById($chatId);
        if (!$chat) {
            $this->redirect("{$this->base}/home");
        }

        if (intval($chat['usuario_vendedor']) !== $usuarioActual) {
            $this->redirect("{$this->base}/chat/{$chatId}");
        }

        if (!empty($chat['transaccion_id'])) {
            $this->redirect("{$this->base}/chat/{$chatId}");
        }

        $productoId  = intval($chat['producto_id']);
        $compradorId = intval($chat['usuario_comprador']);

        $tipoElegido  = trim($_POST['tipo_elegido'] ?? '');
        $tiposValidos = ['venta', 'intercambio'];
        $tipoParam    = in_array($tipoElegido, $tiposValidos) ? $tipoElegido : null;

        $transactionId = $transactionModel->createFromChat($productoId, $compradorId, $usuarioActual, $tipoParam);
        $chatModel->setTransaction($chatId, $transactionId);
        $productoModel->reservarProducto($productoId);

        $tipoMsg = ($tipoParam === 'intercambio') ? 'intercambio' : 'venta';
        $mensajeModel->enviarMensajeSistema(
            $chatId,
            "El vendedor ha iniciado una transacción de {$tipoMsg}. El producto ha sido pausado mientras se negocia."
        );

        $this->redirect("{$this->base}/chat/{$chatId}");
    }

    public function updateTransaction(array $params = []): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("{$this->base}/home");
        }

        require_once __DIR__ . '/../models/Transaction.php';
        require_once __DIR__ . '/../models/Message.php';
        require_once __DIR__ . '/../models/Chat.php';
        require_once __DIR__ . '/../models/Product.php';
        require_once __DIR__ . '/../models/Notification.php';
        require_once __DIR__ . '/../config/mail_config.php';
        require_once __DIR__ . '/../config/mail_templates.php';
        require_once __DIR__ . '/../vendor/autoload.php';

        $transactionModel = new Transaction($this->conn);
        $messageModel     = new Message($this->conn);
        $chatModel        = new Chat($this->conn);
        $productModel     = new Product($this->conn);
        $notifModel       = new Notification($this->conn);

        $usuarioActual = intval($_SESSION['user_id']);
        $transaccionId = intval($_POST['transaccion_id'] ?? 0);
        $nuevoEstado   = trim($_POST['estado']           ?? '');
        $chatId        = intval($_POST['chat_id']        ?? 0);

        if (!$transaccionId || !$nuevoEstado || !$chatId) {
            $this->redirect("{$this->base}/home");
        }

        $transaccion = $transactionModel->getById($transaccionId);
        if (!$transaccion) {
            $this->redirect("{$this->base}/home");
        }

        if ($usuarioActual != $transaccion['comprador_id'] && $usuarioActual != $transaccion['vendedor_id']) {
            $this->redirect("{$this->base}/home");
        }

        $estadoActual = $transaccion['estado'];
        $esComprador  = ($usuarioActual == $transaccion['comprador_id']);
        $esVendedor   = ($usuarioActual == $transaccion['vendedor_id']);

        if (in_array($estadoActual, ['entregado', 'cancelada'])) {
            $this->redirect("{$this->base}/chat/{$chatId}");
        }

        $transicionesValidas = [
            'pendiente'             => ['aceptada', 'propuesta_intercambio', 'cancelada'],
            'propuesta_intercambio' => ['aceptada', 'pendiente',             'cancelada'],
            'aceptada'              => ['pago_pendiente',                    'cancelada'],
            'pago_pendiente'        => ['enviado',                           'cancelada'],
            'enviado'               => ['entregado',                         'cancelada'],
        ];

        if (!isset($transicionesValidas[$estadoActual]) || !in_array($nuevoEstado, $transicionesValidas[$estadoActual])) {
            $this->redirect("{$this->base}/chat/{$chatId}");
        }

        // Autorización por rol
        switch ($nuevoEstado) {
            case 'propuesta_intercambio':
                if (!$esComprador) $this->redirect("{$this->base}/chat/{$chatId}");
                break;
            case 'aceptada':
                if ($estadoActual === 'pendiente' && !$esComprador) $this->redirect("{$this->base}/chat/{$chatId}");
                if ($estadoActual === 'propuesta_intercambio' && !$esVendedor) $this->redirect("{$this->base}/chat/{$chatId}");
                break;
            case 'pendiente':
                if ($estadoActual === 'propuesta_intercambio' && !$esVendedor) $this->redirect("{$this->base}/chat/{$chatId}");
                break;
            case 'pago_pendiente':
                if (!$esComprador) $this->redirect("{$this->base}/chat/{$chatId}");
                break;
            case 'enviado':
                if (!$esVendedor) $this->redirect("{$this->base}/chat/{$chatId}");
                break;
            case 'entregado':
                if (!$esComprador) $this->redirect("{$this->base}/chat/{$chatId}");
                break;
        }

        $productoId = intval($transaccion['producto_id']);

        switch ($nuevoEstado) {
            case 'propuesta_intercambio':
                $productoOfrecidoId = intval($_POST['producto_ofrecido_id'] ?? 0);
                $dineroExtra        = floatval($_POST['dinero_extra']        ?? 0);
                if ($productoOfrecidoId <= 0) {
                    $this->redirect("{$this->base}/chat/{$chatId}?error=producto_ofrecido");
                }
                $productoOfrecido = $productModel->getById($productoOfrecidoId);
                if (!$productoOfrecido || intval($productoOfrecido['usuario_id']) !== $usuarioActual || $productoOfrecido['estado_publicacion'] !== 'activo') {
                    $this->redirect("{$this->base}/chat/{$chatId}?error=producto_ofrecido");
                }
                $transactionModel->proponerIntercambio($transaccionId, $productoOfrecidoId, $dineroExtra);
                $msgPropuesta = "El comprador ha propuesto un intercambio.";
                if ($dineroExtra > 0) $msgPropuesta .= " Dinero extra ofrecido: " . number_format($dineroExtra, 2) . " €.";
                $messageModel->enviarMensajeSistema($chatId, $msgPropuesta);
                break;

            case 'pendiente':
                $transactionModel->rechazarPropuestaIntercambio($transaccionId);
                $messageModel->enviarMensajeSistema($chatId, "El vendedor ha rechazado la propuesta de intercambio. El comprador puede hacer una nueva propuesta.");
                break;

            case 'aceptada':
                if ($estadoActual === 'propuesta_intercambio') {
                    $transactionModel->aceptarPropuestaIntercambio($transaccionId);
                    $messageModel->enviarMensajeSistema($chatId, "El vendedor ha aceptado la propuesta de intercambio. El comprador debe confirmar el método de pago.");
                    break;
                }
                $metodoPago          = trim($_POST['metodo_pago']              ?? '');
                $direccionEnvio      = trim($_POST['direccion_envio']          ?? '');
                $notas               = trim($_POST['notas_comprador']          ?? '');
                $stripePaymentIntent = trim($_POST['stripe_payment_intent_id'] ?? '');
                $metodosValidos      = ['efectivo', 'transferencia', 'bizum', 'paypal', 'stripe', 'otro'];
                if (!in_array($metodoPago, $metodosValidos)) {
                    $this->redirect("{$this->base}/chat/{$chatId}?error=metodo_pago");
                }
                if ($metodoPago === 'stripe') {
                    if (!$stripePaymentIntent) $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_no_intent");
                    $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
                    if (!$stripeSecretKey) $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_config");
                    try {
                        \Stripe\Stripe::setApiKey($stripeSecretKey);
                        $intent              = \Stripe\PaymentIntent::retrieve($stripePaymentIntent);
                        $intentTransaccionId = intval($intent->metadata->transaccion_id ?? 0);
                        if ($intent->status !== 'succeeded' || $intentTransaccionId !== $transaccionId) {
                            $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_pago_fallido");
                        }
                    } catch (\Stripe\Exception\ApiErrorException $e) {
                        $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_error");
                    }
                    $transactionModel->aceptarConStripe($transaccionId, $direccionEnvio, $notas, $stripePaymentIntent);
                    $msgStripe = "El comprador ha pagado con tarjeta (Stripe). El pago ha sido confirmado automáticamente.";
                    if ($direccionEnvio) $msgStripe .= " Dirección de envío: {$direccionEnvio}.";
                    $messageModel->enviarMensajeSistema($chatId, $msgStripe);
                    $nuevoEstado = 'pago_pendiente';
                    break;
                }
                $transactionModel->aceptar($transaccionId, $metodoPago, $direccionEnvio, $notas);
                $etiquetas = ['efectivo' => 'Efectivo al entregar', 'transferencia' => 'Transferencia bancaria', 'bizum' => 'Bizum', 'paypal' => 'PayPal', 'otro' => 'Otro método'];
                $msgAceptada = "El comprador ha aceptado la transacción.";
                if (isset($etiquetas[$metodoPago])) $msgAceptada .= " Método de pago: {$etiquetas[$metodoPago]}.";
                if ($direccionEnvio) $msgAceptada .= " Dirección de envío: {$direccionEnvio}.";
                $messageModel->enviarMensajeSistema($chatId, $msgAceptada);
                break;

            case 'pago_pendiente':
                $metodoPago          = trim($_POST['metodo_pago']              ?? '');
                $direccionEnvio      = trim($_POST['direccion_envio']          ?? '');
                $notas               = trim($_POST['notas_comprador']          ?? '');
                $stripePaymentIntent = trim($_POST['stripe_payment_intent_id'] ?? '');
                if ($metodoPago && !$transaccion['metodo_pago']) {
                    $metodosValidos = ['efectivo', 'transferencia', 'bizum', 'paypal', 'stripe', 'otro'];
                    if (!in_array($metodoPago, $metodosValidos)) {
                        $this->redirect("{$this->base}/chat/{$chatId}?error=metodo_pago");
                    }
                    if ($metodoPago === 'stripe') {
                        if (!$stripePaymentIntent) $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_no_intent");
                        $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
                        if (!$stripeSecretKey) $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_config");
                        try {
                            \Stripe\Stripe::setApiKey($stripeSecretKey);
                            $intent              = \Stripe\PaymentIntent::retrieve($stripePaymentIntent);
                            $intentTransaccionId = intval($intent->metadata->transaccion_id ?? 0);
                            if ($intent->status !== 'succeeded' || $intentTransaccionId !== $transaccionId) {
                                $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_pago_fallido");
                            }
                        } catch (\Stripe\Exception\ApiErrorException $e) {
                            $this->redirect("{$this->base}/chat/{$chatId}?error=stripe_error");
                        }
                        $transactionModel->confirmarPagoConStripe($transaccionId, $direccionEnvio, $notas, $stripePaymentIntent);
                        $msgPago = "El comprador ha pagado con tarjeta (Stripe). El pago ha sido confirmado automáticamente.";
                        if ($direccionEnvio) $msgPago .= " Dirección de envío: {$direccionEnvio}.";
                        $messageModel->enviarMensajeSistema($chatId, $msgPago);
                        break;
                    }
                    $transactionModel->confirmarPagoIntercambio($transaccionId, $metodoPago, $direccionEnvio, $notas);
                    $etiquetas = ['efectivo' => 'Efectivo al entregar', 'transferencia' => 'Transferencia bancaria', 'bizum' => 'Bizum', 'paypal' => 'PayPal', 'otro' => 'Otro método'];
                    $msgPago = "El comprador confirma el pago del intercambio.";
                    if (isset($etiquetas[$metodoPago])) $msgPago .= " Método de pago: {$etiquetas[$metodoPago]}.";
                    if ($direccionEnvio) $msgPago .= " Dirección de envío: {$direccionEnvio}.";
                    $messageModel->enviarMensajeSistema($chatId, $msgPago);
                } else {
                    $transactionModel->marcarPagado($transaccionId);
                    $messageModel->enviarMensajeSistema($chatId, "El comprador indica que ha realizado el pago. El vendedor debe confirmarlo antes de enviar.");
                }
                break;

            case 'enviado':
                $numeroSeguimiento = trim($_POST['numero_seguimiento'] ?? '');
                $transactionModel->marcarEnviado($transaccionId, $numeroSeguimiento);
                $msgEnvio = "El vendedor ha enviado el producto.";
                if ($numeroSeguimiento) $msgEnvio .= " Número de seguimiento: {$numeroSeguimiento}.";
                $messageModel->enviarMensajeSistema($chatId, $msgEnvio);
                break;

            case 'entregado':
                $transactionModel->marcarEntregado($transaccionId);
                $productModel->cambiarEstadoPublicacion($productoId, 'vendido');
                $messageModel->enviarMensajeSistema($chatId, "¡El comprador ha confirmado la entrega! Transacción completada con éxito.");

                $stmtUsers = $this->conn->prepare("SELECT u.email, u.nombre, u.id FROM usuario u WHERE u.id IN (:cid, :vid)");
                $stmtUsers->execute([':cid' => $transaccion['comprador_id'], ':vid' => $transaccion['vendedor_id']]);
                $usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

                $stmtProd = $this->conn->prepare("SELECT titulo, precio FROM Productos WHERE id = :pid");
                $stmtProd->execute([':pid' => $productoId]);
                $prodRow        = $stmtProd->fetch(PDO::FETCH_ASSOC);
                $productoTitulo = $prodRow['titulo'] ?? 'producto';
                $precioProd     = $prodRow['precio'] ? number_format((float) $prodRow['precio'], 2, ',', '.') . ' €' : 'Trueque';
                $fechaTrans     = date('d/m/Y');

                foreach ($usuarios as $u) {
                    $esCompradorEmail = ($u['id'] == $transaccion['comprador_id']);
                    $rol  = $esCompradorEmail ? 'comprador' : 'vendedor';
                    $html = mailTransaccionCompletada($u['nombre'], $rol, $productoTitulo, $precioProd, $fechaTrans);
                    sendMail($u['email'], $u['nombre'], "Transacción completada — {$productoTitulo}", $html);
                }
                break;

            case 'cancelada':
                $transactionModel->cancelar($transaccionId);
                $productModel->cambiarEstadoPublicacion($productoId, 'activo');
                if (in_array($estadoActual, ['propuesta_intercambio', 'pendiente'])) {
                    $this->conn->prepare("DELETE FROM Intercambio_Detalle WHERE transaccion_id = :tid")
                               ->execute([':tid' => $transaccionId]);
                }
                $messageModel->enviarMensajeSistema($chatId, "La transacción ha sido cancelada. El producto vuelve a estar disponible.");
                break;
        }

        // Notificar al otro participante
        $mensajesEstado = [
            'propuesta_intercambio' => 'El comprador ha propuesto un intercambio.',
            'pendiente'      => 'El vendedor ha rechazado la propuesta. El comprador puede hacer una nueva.',
            'aceptada'       => 'La transacción ha avanzado al siguiente paso.',
            'pago_pendiente' => 'El comprador indica que ha realizado el pago.',
            'enviado'        => 'El vendedor ha marcado el pedido como enviado.',
            'entregado'      => '¡La entrega ha sido confirmada! Transacción completada.',
            'cancelada'      => 'La transacción ha sido cancelada.',
        ];
        if (isset($mensajesEstado[$nuevoEstado])) {
            $destinatarioNotif = $esComprador ? intval($transaccion['vendedor_id']) : intval($transaccion['comprador_id']);
            $chatDataNotif     = $chatModel->getById($chatId);
            $tituloProducto    = $chatDataNotif['producto_titulo'] ?? 'un producto';
            $notifModel->create(
                $destinatarioNotif,
                'mensaje',
                $mensajesEstado[$nuevoEstado] . " ({$tituloProducto})",
                "{$this->base}/chat/{$chatId}"
            );
        }

        $this->redirect("{$this->base}/chat/{$chatId}");
    }
}
