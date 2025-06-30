<?php 
include 'conexion.php'; 
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="img/avion.jpg" type="image/jpeg" />
  <title>Golden Trip</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="styles1.css" />

  <style>
    /* Hero video container */
    .video-hero {
      position: relative;
      width: 100%;
      height: 100vh;
      overflow: hidden;
    }

    .video-hero video {
      position: absolute;
      top: 50%;
      left: 50%;
      min-width: 100%;
      min-height: 100%;
      width: auto;
      height: auto;
      transform: translate(-50%, -50%);
      object-fit: cover;
      z-index: 1;
    }

    /* Dark gray overlay for contrast */
    .video-overlay {
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(30, 30, 30, 0.6);
      z-index: 2;
    }

    /* Centered text over video */
    .video-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 3;
      color: #fff;
      text-align: center;
    }

    .video-text h1 {
      font-size: 3rem;
      font-weight: bold;
      color: #fff;
    }

    .video-text button {
      margin-top: 20px;
      background: transparent;
      border: 2px solid #fff;
      color: #fff;
      padding: 10px 30px;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .video-text button:hover {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
    }

    /* Navbar styles */
    .navbar {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 10;
      background: transparent !important;
      box-shadow: none !important;
      transition: color 0.3s ease;
    }

    /* BOTONES BLANCOS en INDEX sin scroll */
    body.index-page .navbar .nav-link,
    body.index-page .navbar .navbar-brand {
      color: white !important;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    body.index-page .navbar .nav-link:hover,
    body.index-page .navbar .navbar-brand:hover {
      color: #ddd !important;
    }

    /* BOTONES NEGROS en INDEX con scroll */
    body.index-page .navbar.scrolled .nav-link,
    body.index-page .navbar.scrolled .navbar-brand {
      color: black !important;
    }

    /* BOTONES NEGROS en otras páginas */
    body:not(.index-page) .navbar .nav-link,
    body:not(.index-page) .navbar .navbar-brand {
      color: black !important;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    body:not(.index-page) .navbar .nav-link:hover,
    body:not(.index-page) .navbar .navbar-brand:hover {
      color: #444 !important;
    }

    /* Content below hero */
    .contenido {
      background: #f9f9f9;
      padding-top: 60px;
      padding-bottom: 80px;
      position: relative;
      z-index: 5;
      margin-top: 0;
    }

    body.index-page .navbar.scrolled {
  background-color: white !important;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  transition: background-color 0.4s ease, box-shadow 0.4s ease;
}

  </style>
</head>
<body class="index-page">

<!-- Carrusel automático -->
 
<section class="video-hero">
  <div id="carouselHero" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-inner">
      <?php
      $sql = "SELECT * FROM carrusel_imagenes ORDER BY orden ASC";
      $resultado = $conexion->query($sql);
      $primera = true;

      while ($fila = $resultado->fetch_assoc()):
      ?>
        <div class="carousel-item <?php if ($primera) { echo 'active'; $primera = false; } ?>">
          <img src="carrusel/<?php echo htmlspecialchars($fila['nombre_archivo']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($fila['descripcion']); ?>" style="height:100vh; object-fit:cover;">
        </div>
      <?php endwhile; ?>
    </div>

    <!-- Eliminamos flechas manuales -->

    <div class="video-overlay"></div>
    <div class="video-text">
      <h1>Explora el mundo con nosotros</h1>
      <button onclick="document.getElementById('buscador').scrollIntoView({behavior: 'smooth'})">Explorar</button>
    </div>

    <?php include 'header.php'; ?>
  </div>
</section>



<!-- Main content -->
<div class="contenido container">

  <!-- Search bar -->
  <section id="buscador" class="p-4 shadow mb-5 bg-white rounded">
    <h1 class="text-center mb-4 text-dark">Encuentra tu próximo destino</h1>
    <form class="row g-3" method="GET" action="resultados.php">
      <div class="col-md-3">
        <label>Origen</label>
        <input type="text" name="origen" class="form-control" placeholder="Ej: Buenos Aires" />
      </div>
      <div class="col-md-3">
        <label>Destino</label>
        <input type="text" name="destino" class="form-control" placeholder="Ej: París" />
      </div>
      <div class="col-md-3">
        <label>Fecha de salida</label>
        <input type="date" name="fecha" class="form-control" />
      </div>
      <div class="col-md-2">
        <label>Precio máximo</label>
        <input type="number" name="precio" class="form-control" placeholder="Ej: 2500" />
      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Buscar</button>
      </div>
    </form>
  </section>

  <!-- Recommended destinations -->
  <section>
    <h2 class="text-center mb-4 text-dark">Recomendados</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4">
      <?php
      $sql = "SELECT * FROM destinos ORDER BY precio DESC LIMIT 6";
      $resultado = $conexion->query($sql);

      while ($destino = $resultado->fetch_assoc()):
        $stmt = $conexion->prepare("SELECT AVG(estrellas) AS promedio FROM opiniones WHERE destino_id = ?");
        $stmt->bind_param("i", $destino['id']);
        $stmt->execute();
        $stmt->bind_result($promedio);
        $stmt->fetch();
        $stmt->close();

        $promedio = $promedio ? round($promedio, 1) : 0;
      ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="img/<?php echo htmlspecialchars($destino['imagen']); ?>" class="card-img-top" alt="Destino" />
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($destino['nombre']); ?></h5>
            <p><?php echo htmlspecialchars($destino['descripcion']); ?></p>
            <p class="fw-bold">$<?php echo number_format($destino['precio'], 2); ?></p>
            <span class="badge bg-success">
              <?php 
                if ($promedio > 0) {
                  for ($i = 1; $i <= 5; $i++) {
                    echo $i <= round($promedio) ? "⭐" : "☆";
                  }
                  echo " (" . $promedio . ")";
                } else {
                  echo "Sin calificación";
                }
              ?>
            </span>

            <a href="detalle.php?id=<?php echo $destino['id']; ?>" class="btn btn-outline-primary w-100 mt-2">Ver más</a>

            <a href="carrito.php?accion=sumar&id=<?php echo $destino['id']; ?>" class="btn btn-primary w-100 mt-2">
              <i class="bi bi-cart-plus"></i> Agregar al carrito
            </a>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Cambia color botones al hacer scroll SOLO en index
 document.addEventListener('DOMContentLoaded', () => {
  const navbar = document.querySelector('.navbar');
  const hero = document.querySelector('.video-hero');

  const handleNavbarScroll = () => {
    const heroHeight = hero.offsetHeight;
    if (window.scrollY > heroHeight - 80) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', handleNavbarScroll);
  handleNavbarScroll(); // Ejecutar una vez al cargar
});

</script>

</body>
</html>
