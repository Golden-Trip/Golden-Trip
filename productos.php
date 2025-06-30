<?php
include 'conexion.php';
$sql = "SELECT * FROM destinos ORDER BY fecha_salida ASC";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paquetes de Viaje</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="styles1.css">
</head>
<body class="index-page">
<?php include 'header.php'; ?>

<main class="container pt-5 mt-5">
  <h2 class="mb-4 text-center">Todos los Paquetes Disponibles</h2>
  <div class="row row-cols-1 row-cols-md-3 g-4">
    <?php while ($paquete = $resultado->fetch_assoc()):

      $stmt = $conexion->prepare("SELECT AVG(estrellas) AS promedio FROM opiniones WHERE destino_id = ?");
      $stmt->bind_param("i", $paquete['id']);
      $stmt->execute();
      $stmt->bind_result($promedio);
      $stmt->fetch();
      $stmt->close();

      $promedio = $promedio ? round($promedio, 1) : 0;
      $estrellas_redondeadas = round($promedio);
    ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <img src="img/<?php echo htmlspecialchars($paquete['imagen']); ?>" class="card-img-top" alt="Destino">
        <div class="card-body">
          <h5 class="card-title"><?php echo htmlspecialchars($paquete['nombre']); ?></h5>
          <p><?php echo htmlspecialchars($paquete['descripcion']); ?></p>
          <p class="fw-bold">$<?php echo number_format($paquete['precio'], 2); ?></p>
          <span class="badge bg-success">
            <?php 
              if ($promedio > 0) {
                for ($i = 1; $i <= 5; $i++) {
                  echo $i <= $estrellas_redondeadas ? "⭐" : "☆";
                }
                echo " (" . $promedio . ")";
              } else {
                echo "Sin calificación";
              }
            ?>
          </span>
          <a href="detalle.php?id=<?php echo $paquete['id']; ?>" class="btn btn-outline-primary w-100 mt-2">Ver más</a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
