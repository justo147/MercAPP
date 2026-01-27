<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ayuda - Marketplace</title>

  <!-- Favicon -->
  <link rel="icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../ico/logo_sinfondo.ico" type="image/x-icon">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS personalizado -->
  <link rel="stylesheet" href="../../public/css/reset.css">
  <link rel="stylesheet" href="../../public/css/style-guide.css">
  <link rel="stylesheet" href="../../public/css/help.css"> <!-- tu SCSS compilado -->


  <!-- JS específico de la página de ayuda -->
  <script src="../../public/js/help.js" defer></script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>


</head>


<body>
  <?php
  $showSearch = false; // Mostrar barra de búsqueda en el navbar
  include("../../public/views/navbar.php");
  ?>

  <h1 id="centro-ayuda">Centro de Ayuda</h1>

  <p class="d-flex justify-content-center">Encuentra información útil, guías paso a paso y respuestas a las preguntas más frecuentes sobre el uso de nuestra plataforma. Estamos aquí para ayudarte a comprar y vender de forma segura.</p>

  <!-- Vídeo explicativo -->
  <h2 class="d-flex justify-content-center">Cómo funciona la plataforma</h2>

  <div class="d-flex justify-content-center">
    <video width="50%" controls>
      <source src="../video/6011533_People_Person_3840x2160.mp4" type="video/mp4">
      Tu navegador no soporta el elemento de vídeo.
    </video>
  </div>


  <h2 id="preguntas-frecuentes" class="mt-4 d-flex justify-content-center">Preguntas Frecuentes</h2>

  <!-- FAQ 1 -->
  <div class="faq-item mt-4">
    <div class="faq-question">
      ¿Cómo puedo subir un producto?
      <span class="arrow">&#9662;</span>
    </div>
    <div class="faq-answer">
      <p>Ve al menú “Subir producto”, añade fotos, descripción y precio. Luego guarda los cambios.</p>
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">
      ¿Cómo contacto con un vendedor?
      <span class="arrow">&#9662;</span>
    </div>
    <div class="faq-answer">
      <p>En la página del producto encontrarás un botón para enviar un mensaje al vendedor.</p>
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">
      ¿Puedo editar un producto ya publicado?
      <span class="arrow">&#9662;</span>
    </div>
    <div class="faq-answer">
      <p>Sí, desde “Mis productos” puedes editar título, precio, fotos y descripción.</p>
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question">
      ¿Cómo elimino mi cuenta?
      <span class="arrow">&#9662;</span>
    </div>
    <div class="faq-answer">
      <p>En “Ajustes de cuenta” encontrarás la opción para desactivar o eliminar tu cuenta.</p>
    </div>
  </div>

  </div>

  <footer>

    <?php include __DIR__ . '/footer.php'; ?>

  </footer>




</body>



</html>