<?php

abstract class Controller
{
    protected \Twig\Environment $twig;
    protected string $base;
    protected PDO $conn;

    public function __construct(\Twig\Environment $twig, string $base, PDO $conn)
    {
        $this->twig = $twig;
        $this->base = $base;
        $this->conn = $conn;
    }

    protected function render(string $template, array $data = []): void
    {
        echo $this->twig->render($template, $data);
    }

    protected function redirect(string $url): never
    {
        header("Location: $url");
        exit;
    }

    /**
     * Verifica que hay sesión activa y que el usuario sigue activo en BD.
     * Actualiza el rol en sesión en cada petición.
     */
    protected function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect("{$this->base}/login");
        }

        require_once __DIR__ . '/../models/User.php';
        $userModel = new User($this->conn);
        $user      = $userModel->getById($_SESSION['user_id']);

        if (!$user || $user['estado'] !== 'activo') {
            session_unset();
            session_destroy();
            $this->redirect("{$this->base}/login?error=cuenta_inactiva");
        }

        $_SESSION['role'] = $user['rol'];
    }

    protected function requireRole(string $role): void
    {
        $this->requireAuth();

        if (($_SESSION['role'] ?? '') !== $role) {
            $this->redirect("{$this->base}/home");
        }
    }
}
