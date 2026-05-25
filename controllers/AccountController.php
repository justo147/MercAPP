<?php

require_once __DIR__ . '/../core/Controller.php';

class AccountController extends Controller
{
    public function detail(array $params = []): void
    {
        $this->requireAuth();

        require_once __DIR__ . '/../models/User.php';

        $userModel = new User($this->conn);
        $userId    = $_SESSION['user_id'];
        $user      = $userModel->getById($userId);
        $error     = '';

        if (!$user) {
            $this->redirect("{$this->base}/home");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre    = trim($_POST['nombre'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $email     = trim($_POST['email'] ?? '');
            $telefono  = trim($_POST['telefono'] ?? '');

            if (empty($nombre) || empty($email)) {
                $error = "Nombre y correo son obligatorios.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "El correo no es válido.";
            } else {
                $foto = $user['foto_perfil'];

                if (!empty($_FILES['foto']['name'])) {
                    $targetDir = __DIR__ . "/../public/uploads/users/";
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    $info = getimagesize($_FILES['foto']['tmp_name']);
                    if ($info === false) {
                        $error = "El archivo subido no es una imagen válida.";
                    } else {
                        $img = match($info['mime']) {
                            'image/jpeg', 'image/jpg', 'image/pjpeg' => imagecreatefromjpeg($_FILES['foto']['tmp_name']),
                            'image/png'  => imagecreatefrompng($_FILES['foto']['tmp_name']),
                            'image/gif'  => imagecreatefromgif($_FILES['foto']['tmp_name']),
                            'image/webp' => imagecreatefromwebp($_FILES['foto']['tmp_name']),
                            default      => null,
                        };

                        if (!$img) {
                            $error = "Formato de imagen no soportado.";
                        } else {
                            $maxWidth = 500;
                            $width    = imagesx($img);
                            $height   = imagesy($img);

                            if ($width > $maxWidth) {
                                $ratio     = $height / $width;
                                $newWidth  = $maxWidth;
                                $newHeight = (int) ($maxWidth * $ratio);
                                $tmp       = imagecreatetruecolor($newWidth, $newHeight);
                                imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                                $img = $tmp;
                            }

                            $fileName   = "FotoPerfil_{$userId}.webp";
                            $targetFile = $targetDir . $fileName;
                            imagewebp($img, $targetFile, 80);
                            $foto = "public/uploads/users/" . $fileName;
                            $_SESSION['profile_photo'] = $foto;
                        }
                    }
                }

                if (empty($error)) {
                    $userModel->updateProfile($userId, $nombre, $apellidos, $telefono, $foto);
                    $this->redirect("{$this->base}/account?updated=1");
                }
            }
        }

        $this->render('detail_account.html.twig', [
            'user'    => $user,
            'error'   => $error,
            'updated' => isset($_GET['updated']),
        ]);
    }
}
