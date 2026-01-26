<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MercApp - Footer</title>

    <!--Bootstrap-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- CSS personalizados-->
    <link href="../css/style-guide.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

</head>

<body>


    <footer class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row gy-4">

            <!-- Marca -->
            <div class="col-12 col-md-3">
                <img src="../img/logo_sinfondo.png" alt="Logo" width="70" height="70" class="mb-3">
                <p class="text-light opacity-75 mb-1">MercaApp</p>
                <small class="text-light opacity-75">Compra fácil, vende seguro.</small>
            </div>

            <!-- Explorar -->
            <div class="col-6 col-md-3">
                <h5 class="text-white mb-3">Explorar</h5>
                <ul class="list-unstyled text-small">
                    <li><a class="text-light opacity-75 text-decoration-none" href="#">Productos</a></li>
                    <li><a class="text-light opacity-75 text-decoration-none" href="#">Categorías</a></li>
                    <li><a class="text-light opacity-75 text-decoration-none" href="#">Vender</a></li>
                    <li><a class="text-light opacity-75 text-decoration-none" href="#">Ofertas</a></li>
                </ul>
            </div>

            <!-- Ayuda -->
            <div class="col-6 col-md-3">
                <h5 class="text-white mb-3">Ayuda</h5>
                <ul class="list-unstyled text-small">
                    <li><a class="text-light opacity-75 text-decoration-none" href="../views/help.php#centro-ayuda">Centro de Ayuda</a></li>
                    <li><a class="text-light opacity-75 text-decoration-none" href="../views/help.php#preguntas-frecuentes">Preguntas frecuentes</a></li>
                    <li><a class="text-light opacity-75 text-decoration-none" href="#">Seguridad</a></li>
                    <li><a class="text-light opacity-75 text-decoration-none" href="#">Contacto</a></li>
                </ul>
            </div>

            <!-- Redes sociales -->
            <div class="col-12 col-md-3">
                <h5 class="text-white mb-3">Síguenos</h5>
                <div class="d-flex gap-3 align-items-center">
                    <a href="https://facebook.com" target="_blank" class="text-light fs-4 text-decoration-none">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" class="text-light fs-4 text-decoration-none">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="https://twitter.com" target="_blank" class="text-light fs-4 text-decoration-none">
                        <i class="bi bi-twitter-x"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- Línea inferior -->
        <div class="border-top mt-4 pt-3 d-flex flex-column justify-content-center align-items-center text-center">
            <p class="mb-2 text-light opacity-75">
                © <?php echo date('Y'); ?> MercaApp — Todos los derechos reservados
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="/privacidad.php" class="text-light opacity-75 text-decoration-none">Privacidad</a>
                <a href="/terminos.php" class="text-light opacity-75 text-decoration-none">Términos</a>
            </div>
        </div>
    </div>
</footer>





</body>

</html>