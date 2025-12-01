<?php
require_once __DIR__ . '\..\..\config\db.php';
// ===============================
// INICIO DE SESIÓN Y CONTROL DE ACCESO
// ===============================
session_start();
if (!isset($_SESSION["user_id"])) {
    header("location:login.php");
    exit;
}

try {
    // ===============================
    // CONEXIÓN A LA BASE DE DATOS
    // ===============================
    $bd = new Database();
    $bd=$bd->getConnection();
    

    $userId = $_SESSION["user_id"];

    // Obtener datos del usuario
    $stmt = $bd->prepare("SELECT nombre, apellidos, email, telefono, foto_perfil FROM usuario WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='alert alert-danger'>Usuario no encontrado.</div>";
        exit;
    }

    // ===============================
    // PROCESAMIENTO DE FORMULARIO POST
    // ===============================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre   = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        // Validaciones básicas
        if (empty($nombre) || empty($email)) {
            $error = "Nombre y correo son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo no es válido.";
        } else {
            // Manejo de foto
            $foto = $user['foto_perfil'];
            if (!empty($_FILES['foto']['name'])) {
                $targetDir = "../uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                // Obtener extensión del archivo
                $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                // Validar extensión permitida
                $allowed = ['jpg', 'jpeg', 'png', 'gif','webp'];
                if (!in_array($extension, $allowed)) {
                    $error = "Formato de imagen no permitido.";
                } else {
                    // Crear nombre único con time() y sufijo FotoPerfil[idUsuario]
                    $fileName = time() . "_FotoPerfil" . $userId . "." . $extension;
                    $targetFile = $targetDir . $fileName;

                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                        $foto = $targetFile;
                    }
                }
            }


            // Actualizar datos
            $upd = $bd->prepare("UPDATE usuario SET nombre=?, apellidos=?, email=?, telefono=?, foto_perfil=? WHERE id=?");
            $upd->execute([$nombre, $apellidos, $email, $telefono, $foto, $userId]);

            header("Location: detailAccount.php?updated=1");
            exit;
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalles de la cuenta - MercApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <script src="../js/theme.js" defer></script>
</head>

<body>
    <!-- Navbar -->
    <?php
    $showSearch = false;
    include("navbar.php"); ?>

    <div class="container py-5 sinFondo">
        <div class="row justify-content-center sinFondo">
            <div class="col-md-8 sinFondo">
                <div class="card shadow no-hover sinFondo">
                    <div class="card-header bg-primary text-white sinFondo">
                        <h4 class="no-style">Detalles de la cuenta</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['updated'])): ?>
                            <div class="alert alert-success">Datos actualizados correctamente.</div>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <div class="d-flex justify-content-center">
                                    <?php if (!empty($user['foto_perfil'])): ?>
                                        <img src="<?= htmlspecialchars($user['foto_perfil']) ?>"
                                            class="rounded-circle mb-3" width="120" height="120" alt="Foto de perfil">
                                    <?php else: ?>
                                        <i class="rounded-circle mb-3 bi bi-people"
                                            style="font-size:120px;"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="foto" class="form-control">
                            </div>


                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>