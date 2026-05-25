<?php

require_once __DIR__ . '/../core/Controller.php';

class AdminController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireRole('admin');

        $this->render('admin_dashboard.html.twig', []);
    }
}
