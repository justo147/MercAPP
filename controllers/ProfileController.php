<?php

require_once __DIR__ . '/../core/Controller.php';

class ProfileController extends Controller
{
    public function show(array $params = []): void
    {
        require_once __DIR__ . '/../models/User.php';

        $perfilId        = intval($params['id'] ?? 0);
        $usuarioLogueado = $_SESSION['user_id'] ?? null;
        $esPropietario   = ($usuarioLogueado !== null && $perfilId === $usuarioLogueado);
        $user            = null;
        $yaSigue         = false;

        if ($perfilId > 0) {
            $stmt = $this->conn->prepare("SELECT * FROM usuario WHERE id = ?");
            $stmt->execute([$perfilId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($user && $usuarioLogueado && !$esPropietario) {
                $userModel = new User($this->conn);
                $yaSigue   = $userModel->sigueA($usuarioLogueado, $perfilId);
            }
        }

        $this->render('profile.html.twig', [
            'user'            => $user,
            'perfilId'        => $perfilId,
            'esPropietario'   => $esPropietario,
            'usuarioLogueado' => $usuarioLogueado,
            'yaSigue'         => $yaSigue,
        ]);
    }

    public function follow(array $params = []): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        $seguidor = intval($_POST['seguidor'] ?? 0);
        $seguido  = intval($_POST['seguido']  ?? 0);

        if (!$seguidor || !$seguido) {
            echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            exit;
        }

        if ($seguidor === $seguido) {
            echo json_encode(['success' => false, 'error' => 'No puedes seguirte a ti mismo']);
            exit;
        }

        if ($seguidor !== intval($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        require_once __DIR__ . '/../models/User.php';
        $userModel = new User($this->conn);

        try {
            $ok = $userModel->seguirUsuario($seguidor, $seguido);
            echo json_encode(['success' => $ok]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function unfollow(array $params = []): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            exit;
        }

        $seguidor = intval($_POST['seguidor'] ?? 0);
        $seguido  = intval($_POST['seguido']  ?? 0);

        require_once __DIR__ . '/../models/User.php';
        $userModel = new User($this->conn);

        $ok = $userModel->dejarDeSeguir($seguidor, $seguido);
        echo json_encode(['success' => $ok]);
        exit;
    }
}
