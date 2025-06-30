<?php
include 'conexion.php';
session_start();

$id = $_GET['id'] ?? null;
if (!$id) {
  header("Location: index.php");
  exit;
}

// Traer paquete
$stmt = $conexion->prepare("SELECT * FROM destinos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$paquete = $result->fetch_assoc();
if (!$paquete) {
  header("Location: index.php");
  exit;
}

// Datos sesión
$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_email = $_SESSION['usuario'] ?? $_SESSION['email'] ?? null;

// Registrar vista si logueado
if ($usuario_id) {
  $stmt = $conexion->prepare("INSERT INTO vistas (usuario_id, destino_id, fecha_vista) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE fecha_vista = NOW()");
  $stmt->bind_param("ii", $usuario_id, $id);
  $stmt->execute();
}

// Traer opinión del usuario si existe
$opinion_usuario = null;
if ($usuario_email) {
  $stmt = $conexion->prepare("SELECT id, estrellas, comentario FROM opiniones WHERE destino_id = ? AND usuario = ?");
  $stmt->bind_param("is", $id, $usuario_email);
  $stmt->execute();
  $opinion_usuario = $stmt->get_result()->fetch_assoc();
}

// Crear o actualizar opinión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opinar']) && $usuario_email) {
  $estrellas = intval($_POST['estrellas']);
  $comentario = trim($_POST['comentario']);

  if ($estrellas >= 1 && $estrellas <= 5 && $comentario !== '') {
    if ($opinion_usuario) {
      $stmt = $conexion->prepare("UPDATE opiniones SET estrellas = ?, comentario = ? WHERE id = ?");
      $stmt->bind_param("isi", $estrellas, $comentario, $opinion_usuario['id']);
    } else {
      $stmt = $conexion->prepare("INSERT INTO opiniones (destino_id, usuario, estrellas, comentario) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("isis", $id, $usuario_email, $estrellas, $comentario);
    }
    $stmt->execute();
    header("Location: detalle.php?id=$id");
    exit;
  }
}

// Borrar opinión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_opinion']) && $usuario_email) {
  if ($opinion_usuario) {
    $stmt = $conexion->prepare("DELETE FROM opiniones WHERE id = ?");
    $stmt->bind_param("i", $opinion_usuario['id']);
    $stmt->execute();
  }
  header("Location: detalle.php?id=$id");
  exit;
}

// Agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_carrito'])) {
  if (!isset($_SESSION['carrito'][$id])) {
    $_SESSION['carrito'][$id] = 1;
  } else {
    $_SESSION['carrito'][$id]++;
  }
  header("Location: carrito.php");
  exit;
}

// Traer estadísticas
$stmt = $conexion->prepare("SELECT estrellas FROM opiniones WHERE destino_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$conteo = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$total = 0; $count = 0;
while ($row = $res->fetch_assoc()) {
  $conteo[$row['estrellas']]++;
  $total += $row['estrellas'];
  $count++;
}
$promedio = $count ? round($total / $count, 1) : 0;

// Traer opiniones de todos
$stmt = $conexion->prepare("SELECT usuario, estrellas, comentario, fecha FROM opiniones WHERE destino_id = ? ORDER BY fecha DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$opiniones = $stmt->get_result();

include 'header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($paquete['nombre']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="styles1.css">
</head>
<body>

<main class="container my-5">
  <div class="row">
    <div class="col-md-6">

      <?php
      // Usar SÓLO la columna 'foto'
      $imagenes = explode(',', $paquete['foto']);
      ?>

      <div id="carouselDestino" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner rounded shadow">
          <?php foreach ($imagenes as $index => $img): ?>
            <div class="carousel-item <?php if ($index === 0) echo 'active'; ?>">
              <img src="img2/<?php echo trim(htmlspecialchars($img)); ?>" class="d-block w-100" alt="Destino <?php echo $index + 1; ?>">
            </div>
          <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselDestino" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselDestino" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
        </button>
      </div>

    </div>
    <div class="col-md-6">
      <h2><?php echo htmlspecialchars($paquete['nombre']); ?></h2>
      <p class="fw-bold fs-4">$<?php echo number_format($paquete['precio'], 2); ?></p>
      <p><strong>Origen:</strong> <?php echo htmlspecialchars($paquete['origen']); ?></p>
      <p><strong>Destino:</strong> <?php echo htmlspecialchars($paquete['destino']); ?></p>
      <p><strong>Fecha de salida:</strong> <?php echo htmlspecialchars($paquete['fecha_salida']); ?></p>
      <p><?php echo htmlspecialchars($paquete['descripcion']); ?></p>
      <form method="POST" class="mt-3">
        <button type="submit" name="add_carrito" class="btn btn-primary w-100">
          <i class="bi bi-cart-plus"></i> Agregar al carrito
        </button>
      </form>
    </div>
  </div>

  <hr class="my-5">

  <h3>Opiniones de viajeros</h3>
  <p>Promedio: <?php echo $promedio; ?> ⭐ (<?php echo $count; ?> opiniones)</p>
  <?php for ($i = 5; $i >= 1; $i--): ?>
    <div><?php echo $i; ?> ⭐: <?php echo $conteo[$i]; ?></div>
  <?php endfor; ?>

  <hr>

  <?php if ($usuario_email): ?>
    <?php if ($opinion_usuario): ?>
      <div class="border rounded p-3 my-4">
        <h5>Tu opinión:</h5>
        <p><strong>Estrellas:</strong> <?php echo str_repeat('⭐', $opinion_usuario['estrellas']); ?> (<?php echo $opinion_usuario['estrellas']; ?>)</p>
        <p><?php echo nl2br(htmlspecialchars($opinion_usuario['comentario'])); ?></p>
        <form method="POST" onsubmit="return confirm('¿Eliminar tu opinión?');">
          <button type="submit" name="borrar_opinion" class="btn btn-danger btn-sm">Borrar</button>
        </form>

        <hr>
        <h6>Editar tu opinión:</h6>
        <form method="POST">
          <div class="mb-2">
            <label>Estrellas:</label>
            <select name="estrellas" class="form-select" required>
              <option value="">Selecciona</option>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>" <?php if ($opinion_usuario['estrellas'] == $i) echo 'selected'; ?>>
                  <?php echo str_repeat('⭐', $i); ?> &nbsp;<?php echo $i; ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-2">
            <label>Comentario:</label>
            <textarea name="comentario" class="form-control" required><?php echo htmlspecialchars($opinion_usuario['comentario']); ?></textarea>
          </div>
          <button type="submit" name="opinar" class="btn btn-success">Guardar cambios</button>
        </form>
      </div>
    <?php else: ?>
      <div class="border rounded p-3 my-4">
        <h5>Dejar tu opinión:</h5>
        <form method="POST">
          <div class="mb-2">
            <label>Estrellas:</label>
            <select name="estrellas" class="form-select" required>
              <option value="">Selecciona</option>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo str_repeat('⭐', $i); ?> &nbsp;<?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="mb-2">
            <label>Comentario:</label>
            <textarea name="comentario" class="form-control" required></textarea>
          </div>
          <button type="submit" name="opinar" class="btn btn-primary">Enviar</button>
        </form>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-muted">Inicia sesión para dejar una opinión.</p>
  <?php endif; ?>

  <hr>
  <h5>Opiniones de otros viajeros:</h5>
  <?php while ($op = $opiniones->fetch_assoc()): ?>
    <div class="border rounded p-3 mb-3">
      <strong><?php echo htmlspecialchars($op['usuario']); ?></strong> - <?php echo str_repeat('⭐', $op['estrellas']); ?> (<?php echo $op['estrellas']; ?>)<br>
      <small><?php echo $op['fecha']; ?></small>
      <p><?php echo nl2br(htmlspecialchars($op['comentario'])); ?></p>
    </div>
  <?php endwhile; ?>

</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
