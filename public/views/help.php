<?php
require_once __DIR__ . '/../../config/twig.php';

echo $twig->render('help.html.twig', []);
