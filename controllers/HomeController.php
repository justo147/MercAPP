<?php

require_once __DIR__ . '/../core/Controller.php';

class HomeController extends Controller
{
    public function landing(array $params = []): void
    {
        $this->redirect("{$this->base}/login");
    }

    public function index(array $params = []): void
    {
        $this->requireAuth();

        $this->render('home.html.twig', [
            'app_query' => htmlspecialchars($_GET['q'] ?? ''),
        ]);
    }
}
