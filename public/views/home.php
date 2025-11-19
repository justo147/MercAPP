<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style-guide.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/homeStyle.css">
    <script src="../js/home.js" defer></script>
    <script src="../js/theme.js" defer></script>
</head>

<body>
    <button id="themeToggle" class="toggle-btn" aria-label="Cambiar tema">🌙</button>
    <div class="welcome sinFondo">
        <h1>Pagina de inicio</h1>
    </div>
    <h2 id="welcomeMessage"></h2>
    <br>
    <div class="container">
        <h2>Datos del Usuario</h2>
        <p><strong>Nombre:</strong> <span id="userName"></span></p>
        <p><strong>Email:</strong> <span id="userEmail"></span></p>
    </div>

    <section class="user-products">
        <h2>Tus productos</h2>
        <div class="product-grid">
            <div class="product-card placeholder">
                <p>Producto 1</p>
                <div class="image-placeholder">📦</div>
            </div>
            <div class="product-card placeholder">
                <p>Producto 2</p>
                <div class="image-placeholder">📦</div>
            </div>
            <div class="product-card placeholder">
                <p>Producto 3</p>
                <div class="image-placeholder">📦</div>
            </div>
            <div class="product-card placeholder">
                <p>Producto 4</p>
                <div class="image-placeholder">📦</div>
            </div>
            <div class="product-card placeholder">
                <p>Producto 5</p>
                <div class="image-placeholder">📦</div>
            </div>
        </div>
    </section>

    <a href="login.html" class="button-primary" onclick="cerrarSesion()">Cerrar sesión</a>
</body>

</html>