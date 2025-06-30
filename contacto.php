<?php
session_start();
include 'conexion.php';

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (!$nombre || !$email || !$mensaje) {
        $error = "Por favor completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no es válido.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO contacto (nombre, email, mensaje, fecha) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("sss", $nombre, $email, $mensaje);
            if ($stmt->execute()) {
                $success = "¡Gracias por tu mensaje! Te responderemos pronto.";
                $nombre = $email = $mensaje = "";
            } else {
                $error = "Error al enviar el mensaje. Intenta de nuevo.";
            }
            $stmt->close();
        } else {
            $error = "Error en la base de datos: " . $conexion->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Contacto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="styles1.css" />
</head>
<body>

<?php include 'header.php'; ?>

<main class="container pt-5 mt-5">
  <h2 class="mb-4 text-center">Contáctanos</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" class="bg-light p-4 rounded shadow mx-auto" style="max-width: 600px;">
    <div class="mb-3">
      <label>Nombre</label>
      <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($nombre ?? ''); ?>" />
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email ?? ''); ?>" />
    </div>
    <div class="mb-3">
      <label>Mensaje</label>
      <textarea name="mensaje" class="form-control" rows="5" required><?php echo htmlspecialchars($mensaje ?? ''); ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary w-100">Enviar</button>
  </form>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
