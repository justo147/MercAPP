<?php
require_once __DIR__ . '/bootstrap.php';

$loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/templates');
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
    if (empty($_SESSION['_flash'])) {
        return '';
    }
    $out = '';
    foreach ($_SESSION['_flash'] as $flash) {
        $msg  = addslashes(htmlspecialchars($flash['mensaje'], ENT_QUOTES));
        $tipo = addslashes(htmlspecialchars($flash['tipo'],    ENT_QUOTES));
        $out .= "<script>document.addEventListener('DOMContentLoaded',()=>mostrarToast('{$msg}','{$tipo}'));</script>\n";
    }
    unset($_SESSION['_flash']);
    return $out;
}, ['is_safe' => ['html']]));
