<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

include_once 'conexion.php';

$nombreUsuario = null;
$esAdmin = false; // MODIFICADO: asumimos que no es admin por defecto

if (isset($_SESSION['usuario']) || isset($_SESSION['email'])) {
  $email = $_SESSION['usuario'] ?? $_SESSION['email'];
  $stmt = $conexion->prepare("SELECT nombre, rol FROM usuarios WHERE email = ?"); // MODIFICADO: traemos también rol
  if ($stmt) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($nombreReal, $rol); // MODIFICADO: capturamos nombre y rol
    if ($stmt->fetch()) {
      $nombreUsuario = $nombreReal;
      $esAdmin = ($rol === 'admin'); // MODIFICADO: activamos si es admin
    }
    $stmt->close();
  }
}

$isIndex = basename($_SERVER['PHP_SELF']) === 'index.php';
?>

<nav class="navbar navbar-expand-lg fixed-top <?php echo $isIndex ? 'index-page transparent-bg' : 'bg-white shadow-sm not-index'; ?>">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php" title="Inicio">
        Golden Trip
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav gap-3">
        <li class="nav-item">
          <a class="nav-link" href="alojamiento.php" title="Alojamientos">
            <i class="bi bi-house-fill fs-3" title="Alojamientos"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="productos.php" title="Paquetes">
            <i class="bi bi-bag-fill fs-3" title="Paquetes"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="contacto.php" title="Contacto">
            <i class="bi bi-envelope-fill fs-3" title="Contacto"></i>
          </a>
        </li>

        <?php if ($nombreUsuario): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="perfilDropdown" role="button" data-bs-toggle="dropdown" title="Mi Perfil">
              <i class="bi bi-person-fill fs-3" title="Mi Perfil"></i>
              <?php echo htmlspecialchars($nombreUsuario); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a></li>
              <li><a class="dropdown-item" href="historial.php"><i class="bi bi-clock-history"></i> Historial</a></li>
              <li><a class="dropdown-item" href="opiniones.php"><i class="bi bi-chat-text"></i> Mis Opiniones</a></li>
              
              <?php if ($esAdmin): ?> <!-- MODIFICADO: solo para admin -->
                <li><a class="dropdown-item text-primary" href="admin_panel.php"><i class="bi bi-shield-lock"></i> Panel Admin</a></li>
              <?php endif; ?>

              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="login.php" title="Iniciar sesión">
              <i class="bi bi-person-fill fs-3" title="Iniciar sesión"></i>
            </a>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <a class="nav-link" href="carrito.php" title="Carrito">
            <i class="bi bi-cart-fill fs-3" title="Carrito"></i>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<style>
  .navbar {
    transition: background-color 0.4s ease, box-shadow 0.4s ease, color 0.4s ease;
  }

  .index-page.transparent-bg {
    background-color: transparent !important;
    box-shadow: none !important;
  }

  .index-page.transparent-bg .nav-link,
  .index-page.transparent-bg .navbar-brand {
    color: white !important;
  }

  .index-page.scrolled {
    background-color: white !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .index-page.scrolled .nav-link,
  .index-page.scrolled .navbar-brand {
    color: black !important;
  }

  .not-index .nav-link,
  .not-index .navbar-brand {
    color: black !important;
  }

  .navbar .nav-link:hover,
  .navbar .navbar-brand:hover {
    color: #007bff !important;
  }
</style>

<?php if ($isIndex): ?>
<script>
  // Solo en index.php: cambia estilo del navbar cuando se scrollea
  window.addEventListener('scroll', function () {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    if (window.scrollY > 100) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });
</script>
<?php endif; ?>
