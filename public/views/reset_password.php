<?php
/**
 * Incluye el handler que procesa la actualización de la contraseña.
 * Este archivo maneja la validación del token, verificación y actualización en la base de datos.
 */
require __DIR__ . "/../../controllers/handlers/reset_password_handlers.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column align-items-center justify-content-center min-vh-100 bg-light">

    <!-- Contenedor central para el formulario de restablecimiento -->
    <div class="card shadow p-4 rounded-3" style="max-width: 480px; width: 100%;">
        
        <!-- Título del formulario -->
        <h1 class="text-center mb-3">Restablecer contraseña</h1>
        
        <!-- Formulario de actualización de contraseña -->
        <form method="post">
            
            <!-- Campo para la nueva contraseña -->
            <div class="mb-3">
                <label for="password" class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <!-- Botón de envío -->
            <div class="d-grid">
                <button type="submit" class="btn btn-success">Actualizar contraseña</button>
            </div>
        </form>
        <?php
        if(isset($message)) echo $message ?>
    </div>

</body>
</html>
