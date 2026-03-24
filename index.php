<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();



$BASE = $_ENV['BASE_PATH'];


header("Location: {$BASE}/public/views/auth/login.php");
exit;
