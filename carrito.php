<?php
include 'conexion.php';
session_start();

// Inicializa carritos si no existen
if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
if (!isset($_SESSION['carrito_alojamientos'])) $_SESSION['carrito_alojamientos'] = [];

// Acciones del carrito
if (isset($_GET['accion'])) {
    $id = intval($_GET['id'] ?? 0);
    $tipo = $_GET['tipo'] ?? 'paquete'; // puede ser 'paquete' o 'alojamiento'

    if ($tipo === 'paquete') {
        switch ($_GET['accion']) {
            case 'sumar':
                $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + 1;
                break;
            case 'restar':
                if (isset($_SESSION['carrito'][$id])) {
                    $_SESSION['carrito'][$id]--;
                    if ($_SESSION['carrito'][$id] <= 0) unset($_SESSION['carrito'][$id]);
                }
                break;
            case 'eliminar':
                unset($_SESSION['carrito'][$id]);
                break;
        }
    } elseif ($tipo === 'alojamiento') {
        switch ($_GET['accion']) {
            case 'sumar':
                $_SESSION['carrito_alojamientos'][$id] = ($_SESSION['carrito_alojamientos'][$id] ?? 0) + 1;
                break;
            case 'restar':
                if (isset($_SESSION['carrito_alojamientos'][$id])) {
                    $_SESSION['carrito_alojamientos'][$id]--;
                    if ($_SESSION['carrito_alojamientos'][$id] <= 0) unset($_SESSION['carrito_alojamientos'][$id]);
                }
                break;
            case 'eliminar':
                unset($_SESSION['carrito_alojamientos'][$id]);
                break;
        }
    }

    if ($_GET['accion'] === 'vaciar') {
        $_SESSION['carrito'] = [];
        $_SESSION['carrito_alojamientos'] = [];
    }

    header("Location: carrito.php");
    exit;
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Carrito de Compras</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="styles1.css">
</head>
<body>

<main class="container pt-5 mt-5">
  <h2 class="mb-4 text-center"><i class="bi bi-cart4"></i> Tu Carrito</h2>

  <?php if (empty($_SESSION['carrito']) && empty($_SESSION['carrito_alojamientos'])): ?>
    <div class="alert alert-info text-center shadow">
      <i class="bi bi-cart-x fs-3"></i><br> <span class="empty-cart">Tu carrito está vacío.</span>
    </div>
  <?php else: ?>
    <?php
    $total = 0;
    $ids_invalidos = [];

    // Mostrar paquetes (destinos)
    if (!empty($_SESSION['carrito'])):
    ?>
      <h4 class="mb-3">Paquetes turísticos</h4>
      <div class="table-responsive mb-4">
        <table class="table cart-table text-center align-middle">
          <thead class="table-primary">
            <tr>
              <th>Imagen</th>
              <th>Paquete</th>
              <th>Origen</th>
              <th>Destino</th>
              <th>Salida</th>
              <th>Precio</th>
              <th>Cantidad</th>
              <th>Subtotal</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($_SESSION['carrito'] as $id => $cantidad):
              $stmt = $conexion->prepare("SELECT * FROM destinos WHERE id = ?");
              $stmt->bind_param("i", $id);
              $stmt->execute();
              $resultado = $stmt->get_result();
              $stmt->close();

              if ($resultado->num_rows > 0):
                $paquete = $resultado->fetch_assoc();
                $subtotal = $paquete['precio'] * $cantidad;
                $total += $subtotal;
            ?>
              <tr>
                <td><img src="img/<?php echo htmlspecialchars($paquete['imagen']); ?>" width="80" class="img-thumbnail rounded"></td>
                <td><?php echo htmlspecialchars($paquete['nombre']); ?></td>
                <td><?php echo htmlspecialchars($paquete['origen']); ?></td>
                <td><?php echo htmlspecialchars($paquete['destino']); ?></td>
                <td><?php echo htmlspecialchars($paquete['fecha_salida']); ?></td>
                <td><span class="badge bg-primary">$<?php echo number_format($paquete['precio'], 2); ?></span></td>
                <td>
                  <a href="carrito.php?accion=restar&id=<?php echo $id; ?>&tipo=paquete" class="btn btn-outline-secondary btn-sm"><i class="bi bi-dash"></i></a>
                  <span class="mx-2 fw-bold"><?php echo $cantidad; ?></span>
                  <a href="carrito.php?accion=sumar&id=<?php echo $id; ?>&tipo=paquete" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus"></i></a>
                </td>
                <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                <td>
                  <a href="carrito.php?accion=eliminar&id=<?php echo $id; ?>&tipo=paquete" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endif; endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php
    // Mostrar alojamientos
    if (!empty($_SESSION['carrito_alojamientos'])):
    ?>
      <h4 class="mb-3">Alojamientos turísticos</h4>
      <div class="table-responsive mb-4">
        <table class="table cart-table text-center align-middle">
          <thead class="table-success">
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Destino</th>
              <th>Dirección</th>
              <th>Precio/Noche</th>
              <th>Cantidad</th>
              <th>Subtotal</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($_SESSION['carrito_alojamientos'] as $id => $cantidad):
              $stmt = $conexion->prepare("SELECT a.*, d.destino FROM alojamiento a JOIN destinos d ON a.id_destino = d.id WHERE a.id = ?");
              $stmt->bind_param("i", $id);
              $stmt->execute();
              $res = $stmt->get_result();
              $stmt->close();

              if ($res->num_rows > 0):
                $aloj = $res->fetch_assoc();
                $subtotal = $aloj['precio_noche'] * $cantidad;
                $total += $subtotal;
            ?>
              <tr>
                <td><img src="img/<?php echo htmlspecialchars($aloj['imagen']); ?>" width="80" class="img-thumbnail rounded"></td>
                <td><?php echo htmlspecialchars($aloj['nombre']); ?></td>
                <td><?php echo htmlspecialchars($aloj['destino']); ?></td>
                <td><?php echo htmlspecialchars($aloj['direccion']); ?></td>
                <td><span class="badge bg-success">$<?php echo number_format($aloj['precio_noche'], 2); ?></span></td>
                <td>
                  <a href="carrito.php?accion=restar&id=<?php echo $id; ?>&tipo=alojamiento" class="btn btn-outline-secondary btn-sm"><i class="bi bi-dash"></i></a>
                  <span class="mx-2 fw-bold"><?php echo $cantidad; ?></span>
                  <a href="carrito.php?accion=sumar&id=<?php echo $id; ?>&tipo=alojamiento" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus"></i></a>
                </td>
                <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                <td>
                  <a href="carrito.php?accion=eliminar&id=<?php echo $id; ?>&tipo=alojamiento" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
            <?php endif; endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-white rounded shadow">
      <h4 class="mb-0">Total: <span class="text-success fw-bold">$<?php echo number_format($total, 2); ?></span></h4>
      <div>
        <a href="carrito.php?accion=vaciar" class="btn btn-outline-danger me-2" onclick="return confirm('¿Vaciar el carrito?');">
          <i class="bi bi-x-circle"></i> Vaciar
        </a>
        <a href="pago.php" class="btn btn-success"><i class="bi bi-bag-check"></i> Finalizar compra</a>
      </div>
    </div>
  <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
