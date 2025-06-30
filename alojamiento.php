<?php
include 'conexion.php';

$sql = "
SELECT a.*, d.destino 
FROM alojamiento a 
JOIN destinos d ON a.id_destino = d.id 
ORDER BY d.destino ASC, a.nombre ASC
";
$resultado = $conexion->query($sql);

// Agrupar por destino
$alojamientos = [];
while ($fila = $resultado->fetch_assoc()) {
  $alojamientos[$fila['destino']][] = $fila;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Alojamientos Disponibles</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="styles1.css">
</head>
<body class="index-page">
<?php include 'header.php'; ?>

<main class="container pt-5 mt-5">
  <h2 class="mb-4 text-center">Todos los Alojamientos Disponibles</h2>

  <?php foreach ($alojamientos as $destino => $hoteles): ?>
    <h3 class="mt-5 mb-4 text-primary"><?php echo htmlspecialchars($destino); ?></h3>
    <div class="row row-cols-1 row-cols-md-3 g-4">
      <?php foreach ($hoteles as $hotel):
        // Obtener promedio de estrellas para este alojamiento
        $stmt = $conexion->prepare("SELECT AVG(estrellas) FROM opiniones WHERE id_alojamiento = ?");
        $stmt->bind_param("i", $hotel['id']);
        $stmt->execute();
        $stmt->bind_result($promedio);
        $stmt->fetch();
        $stmt->close();

        $promedio = $promedio ? round($promedio, 1) : null;
        $estrellas_redondeadas = $promedio ? round($promedio) : 0;
      ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="img3/<?php echo htmlspecialchars($hotel['imagen']); ?>" class="card-img-top" alt="Alojamiento">
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($hotel['nombre']); ?></h5>
            <p><?php echo htmlspecialchars($hotel['descripcion']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($hotel['direccion']); ?></p>
            <p class="fw-bold">$<?php echo number_format($hotel['precio_noche'], 2); ?> por noche</p>
            <span class="badge bg-success">
              <?php if ($promedio !== null): ?>
                <?php
                  for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $estrellas_redondeadas ? "⭐" : "☆";
                  }
                ?>
                (<?php echo number_format($promedio, 1); ?>)
              <?php else: ?>
                Sin calificación
              <?php endif; ?>
            </span>
            <a href="alojamiento_detalle.php?id=<?php echo $hotel['id']; ?>" class="btn btn-outline-primary w-100 mt-2">Ver más</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
