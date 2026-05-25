<?php

require_once __DIR__ . '/../core/Controller.php';

class HomeController extends Controller
{
    public function landing(array $params = []): void
    {
        $this->render('home.html.twig', [
            'app_query' => htmlspecialchars($_GET['q'] ?? ''),
        ]);
    }

    public function index(array $params = []): void
    {
        $this->render('home.html.twig', [
            'app_query' => htmlspecialchars($_GET['q'] ?? ''),
        ]);
    }
}
