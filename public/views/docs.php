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
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style-guide.css">
  <link rel="stylesheet" href="../css/help.css">



  <!-- JS específico de la página de ayuda -->
  <script src="../js/help.js" defer></script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>


</head>
 <body data-bs-theme="dark">
  <?php
  $showSearch = false; // Mostrar barra de búsqueda en el navbar
  include("../../public/views/navbar.php");
  ?>

<h1 id="centro-ayuda">Documentación técnica</h1>

<p class="d-flex justify-content-center">
  Esta vista reúne la documentación técnica completa del proyecto,
  permitiendo un acceso rápido y centralizado a todos los detalles relevantes.
</p>

<h2 class="d-flex justify-content-center">Lenguajes</h2>

<ul class="nav nav-tabs justify-content-center gap-3" id="docTabs">
  <li class="nav-item">
    <button class="nav-link active" data-doc="php">PHP</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-doc="js">JavaScript</button>
  </li>
</ul>

<div class="container-fluid mt-4 px-4">
  <div class="ratio ratio-16x9">
    <iframe 
      id="docFrame"
      src="../../docs/api/index.html"  <!-- PHPDoc por defecto -->
      class="rounded"
      style="border: none; width: 100%; height: 100%;">
    </iframe>
  </div>
</div>

<script>
  const docFrame = document.getElementById("docFrame");
  const tabs = document.querySelectorAll("#docTabs .nav-link");

  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      tabs.forEach(t => t.classList.remove("active"));
      tab.classList.add("active");

      const tipo = tab.dataset.doc;
      if (tipo === "php") {
        docFrame.src = "../../docs/api/index.html"; // PHPDoc
      } else if (tipo === "js") {
        docFrame.src = "../../docs/js/index.html"; // JSDoc
      }
    });
  });
</script>



  <footer>

    <?php include __DIR__ . '/footer.php'; ?>

  </footer>
</body>

</html>