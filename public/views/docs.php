<?php
session_start();
require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('docs.html.twig', []);
