<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$BASE = $_ENV['BASE_PATH'];

// Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig   = new \Twig\Environment($loader, ['cache' => false, 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addGlobal('BASE',    $BASE);
$twig->addGlobal('session', $_SESSION ?? []);
$twig->addGlobal('year',    (int) date('Y'));
$twig->addFunction(new \Twig\TwigFunction('asset', function (string $path) use ($BASE): string {
    return $BASE . '/public/' . ltrim($path, '/');
}));
$twig->addFilter(new \Twig\TwigFilter('md5', fn(string $s): string => md5($s)));
$twig->addFunction(new \Twig\TwigFunction('flash_scripts', function (): string {
    if (empty($_SESSION['_flash'])) return '';
    $out = '';
    foreach ($_SESSION['_flash'] as $flash) {
        $msg  = addslashes(htmlspecialchars($flash['mensaje'], ENT_QUOTES));
        $tipo = addslashes(htmlspecialchars($flash['tipo'],    ENT_QUOTES));
        $out .= "<script>document.addEventListener('DOMContentLoaded',()=>mostrarToast('{$msg}','{$tipo}'));</script>\n";
    }
    unset($_SESSION['_flash']);
    return $out;
}, ['is_safe' => ['html']]));

// Base de datos
require_once __DIR__ . '/config/db.php';
$db   = new Database();
$conn = $db->getConnection();

// Controladores
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProductController.php';
require_once __DIR__ . '/controllers/ChatController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/AccountController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/PageController.php';

$router  = new Router();
$context = ['twig' => $twig, 'base' => $BASE, 'conn' => $conn];

// ── Rutas ──────────────────────────────────────────────────────────────

// Landing & Home
$router->get('/',     [HomeController::class, 'landing']);
$router->get('/home', [HomeController::class, 'index']);

// Auth
$router->get('/login',                [AuthController::class, 'login']);
$router->post('/login',               [AuthController::class, 'login']);
$router->get('/register',             [AuthController::class, 'register']);
$router->post('/register',            [AuthController::class, 'register']);
$router->get('/logout',               [AuthController::class, 'logout']);
$router->get('/pending-verification', [AuthController::class, 'pendingVerification']);
$router->get('/verify-email',         [AuthController::class, 'verifyEmail']);
$router->get('/forgot-password',      [AuthController::class, 'forgotPassword']);
$router->post('/forgot-password',     [AuthController::class, 'forgotPassword']);
$router->get('/reset-password',       [AuthController::class, 'resetPassword']);
$router->post('/reset-password',      [AuthController::class, 'resetPassword']);

// Productos — rutas literales ANTES que las dinámicas
$router->get('/product/upload',       [ProductController::class, 'upload']);
$router->post('/product/upload',      [ProductController::class, 'upload']);
$router->get('/product/delete',       [ProductController::class, 'delete']);
$router->post('/report',              [ProductController::class, 'report']);
$router->get('/product/{id}',         [ProductController::class, 'detail']);
$router->get('/product/{id}/edit',    [ProductController::class, 'edit']);
$router->post('/product/{id}/edit',   [ProductController::class, 'edit']);

// Chat — rutas literales ANTES que las dinámicas
$router->get('/chat',                       [ChatController::class, 'list']);
$router->get('/chat/start',                 [ChatController::class, 'start']);
$router->post('/chat/start-transaction',    [ChatController::class, 'startTransaction']);
$router->post('/chat/update-transaction',   [ChatController::class, 'updateTransaction']);
$router->get('/chat/{id}',                  [ChatController::class, 'detail']);
$router->post('/chat/{id}',                 [ChatController::class, 'detail']);

// Perfil y social
$router->get('/profile/{id}',  [ProfileController::class, 'show']);
$router->post('/follow',       [ProfileController::class, 'follow']);
$router->post('/unfollow',     [ProfileController::class, 'unfollow']);

// Cuenta
$router->get('/account',  [AccountController::class, 'detail']);
$router->post('/account', [AccountController::class, 'detail']);

// Admin
$router->get('/admin', [AdminController::class, 'index']);

// Páginas de usuario
$router->get('/favorites',    [PageController::class, 'favorites']);
$router->get('/transactions', [PageController::class, 'transactions']);
$router->get('/wishlist',     [PageController::class, 'wishlist']);
$router->get('/following',    [PageController::class, 'following']);

// Páginas estáticas
$router->get('/help', [PageController::class, 'help']);
$router->get('/documentacion', [PageController::class, 'docs']);

// ── Dispatch ───────────────────────────────────────────────────────────

$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = parse_url($BASE, PHP_URL_PATH) ?: '';
$uri      = '/' . ltrim(substr($uri, strlen($basePath)), '/');
if ($uri === '') $uri = '/';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $uri, $context);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre style="background:#1e1e1e;color:#f8d7da;padding:2rem;font-size:.9rem;">';
    echo '<strong>ERROR 500</strong>' . PHP_EOL . PHP_EOL;
    echo htmlspecialchars($e->getMessage()) . PHP_EOL . PHP_EOL;
    echo htmlspecialchars($e->getTraceAsString());
    echo '</pre>';
}
