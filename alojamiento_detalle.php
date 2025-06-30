<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'conexion.php';
session_start();
// ... el resto de tu código

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Traer alojamiento con nombre del destino
$stmt = $conexion->prepare("
    SELECT a.*, d.destino, d.id AS id_destino
    FROM alojamiento a
    JOIN destinos d ON a.id_destino = d.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$alojamiento = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$alojamiento) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_email = $_SESSION['usuario'] ?? $_SESSION['email'] ?? null;

// --- INICIO: Lógica para registrar la vista del alojamiento ---
if ($usuario_id) {
    // Comprobar si ya existe una vista para este usuario y alojamiento hoy
    // Esto es opcional, para no llenar la tabla con muchas vistas si el usuario recarga la página.
    // Si quieres que cada recarga cuente como una nueva vista, puedes omitir esta verificación.
    $stmt_check = $conexion->prepare("SELECT id FROM vistas WHERE usuario_id = ? AND alojamiento_id = ? AND DATE(fecha_vista) = CURDATE() AND tipo_vista = 'alojamiento'");
    $stmt_check->bind_param("ii", $usuario_id, $id);
    $stmt_check->execute();
    $existing_view = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$existing_view) {
        // Insertar la nueva vista si no existe una para hoy
        $stmt_insert_view = $conexion->prepare("INSERT INTO vistas (usuario_id, alojamiento_id, tipo_vista, fecha_vista) VALUES (?, ?, 'alojamiento', NOW())");
        // Asegúrate de que destino_id sea NULL o no se incluya en esta inserción, ya que es una vista de alojamiento
        $stmt_insert_view->bind_param("ii", $usuario_id, $id);
        $stmt_insert_view->execute();
        $stmt_insert_view->close();
    }
}
// --- FIN: Lógica para registrar la vista del alojamiento ---


// Traer opinión del usuario si ya opinó
$opinion_usuario = null;
if ($usuario_email) {
    $stmt = $conexion->prepare("SELECT id, estrellas, comentario FROM opiniones WHERE id_alojamiento = ? AND usuario = ?");
    $stmt->bind_param("is", $id, $usuario_email);
    $stmt->execute();
    $opinion_usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Insertar o actualizar opinión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opinar']) && $usuario_email) {
    $estrellas = intval($_POST['estrellas']);
    $comentario = trim($_POST['comentario']);
    $id_destino = $alojamiento['id_destino']; // Asegúrate de que este id_destino sea el correcto para el alojamiento

    if ($estrellas >= 1 && $estrellas <= 5 && $comentario !== '') {
        if ($opinion_usuario) {
            $stmt = $conexion->prepare("UPDATE opiniones SET estrellas = ?, comentario = ?, fecha = NOW() WHERE id = ?");
            $stmt->bind_param("isi", $estrellas, $comentario, $opinion_usuario['id']);
        } else {
            $stmt = $conexion->prepare("INSERT INTO opiniones (id_alojamiento, destino_id, usuario, estrellas, comentario, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iisis", $id, $id_destino, $usuario_email, $estrellas, $comentario);
        }
        $stmt->execute();
        header("Location: alojamiento_detalle.php?id=$id");
        exit;
    }
}

// Eliminar opinión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_opinion']) && $usuario_email && $opinion_usuario) {
    $stmt = $conexion->prepare("DELETE FROM opiniones WHERE id = ?");
    $stmt->bind_param("i", $opinion_usuario['id']);
    $stmt->execute();
    header("Location: alojamiento_detalle.php?id=$id");
    exit;
}

// Agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_carrito'])) {
    if (!isset($_SESSION['carrito_alojamientos'][$id])) {
        $_SESSION['carrito_alojamientos'][$id] = 1;
    } else {
        $_SESSION['carrito_alojamientos'][$id]++;
    }
    header("Location: carrito.php");
    exit;
}

// Estadísticas de opiniones
$stmt = $conexion->prepare("SELECT estrellas FROM opiniones WHERE id_alojamiento = ?");
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

// Opiniones generales
$stmt = $conexion->prepare("SELECT usuario, estrellas, comentario, fecha FROM opiniones WHERE id_alojamiento = ? ORDER BY fecha DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$opiniones = $stmt->get_result();
?>

<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($alojamiento['nombre']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles1.css">
</head>
<body>

<main class="container pt-5 mt-5">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow rounded">
                <img src="img3/<?php echo htmlspecialchars($alojamiento['imagen']); ?>" class="card-img-top" alt="Alojamiento">
            </div>
        </div>
        <div class="col-md-6">
            <h2><?php echo htmlspecialchars($alojamiento['nombre']); ?></h2>
            <p><strong>Destino:</strong> <?php echo htmlspecialchars($alojamiento['destino']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($alojamiento['direccion']); ?></p>
            <p class="fw-bold fs-4">$<?php echo number_format($alojamiento['precio_noche'], 2); ?> por noche</p>
            <p><?php echo nl2br(htmlspecialchars($alojamiento['descripcion'])); ?></p>

            <form method="POST" class="mt-3">
                <button type="submit" name="add_carrito" class="btn btn-primary w-100">
                    <i class="bi bi-cart-plus"></i> Agregar al carrito
                </button>
            </form>
        </div>
    </div>

    <hr class="my-5">
    <h3>Opiniones</h3>
    <p>Promedio: <?php echo $promedio; ?> ⭐ (<?php echo $count; ?> opiniones)</p>
    <?php for ($i = 5; $i >= 1; $i--): ?>
        <div><?php echo $i; ?> ⭐: <?php echo $conteo[$i]; ?></div>
    <?php endfor; ?>

    <hr>

    <?php if ($usuario_email): ?>
        <?php if ($opinion_usuario): ?>
            <div class="border rounded p-3 my-4">
                <h5>Tu opinión:</h5>
                <p><strong>Estrellas:</strong> <?php echo str_repeat('⭐', $opinion_usuario['estrellas']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($opinion_usuario['comentario'])); ?></p>
                <form method="POST" onsubmit="return confirm('¿Eliminar tu opinión?');">
                    <button type="submit" name="borrar_opinion" class="btn btn-danger btn-sm">Borrar</button>
                </form>
                <hr>
                <h6>Editar tu opinión:</h6>
                <form method="POST">
                    <select name="estrellas" class="form-select mb-2" required>
                        <option value="">Selecciona</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($opinion_usuario['estrellas'] == $i) echo 'selected'; ?>>
                                <?php echo str_repeat('⭐', $i); ?> &nbsp;<?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <textarea name="comentario" class="form-control mb-2" required><?php echo htmlspecialchars($opinion_usuario['comentario']); ?></textarea>
                    <button type="submit" name="opinar" class="btn btn-success">Guardar cambios</button>
                </form>
            </div>
        <?php else: ?>
            <div class="border rounded p-3 my-4">
                <h5>Dejar una opinión:</h5>
                <form method="POST">
                    <select name="estrellas" class="form-select mb-2" required>
                        <option value="">Selecciona</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo str_repeat('⭐', $i); ?> &nbsp;<?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <textarea name="comentario" class="form-control mb-2" required></textarea>
                    <button type="submit" name="opinar" class="btn btn-primary">Enviar</button>
                </form>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-muted">Inicia sesión para dejar una opinión.</p>
    <?php endif; ?>

    <hr>
    <h5>Opiniones de otros huéspedes:</h5>
    <?php while ($op = $opiniones->fetch_assoc()): ?>
        <div class="border rounded p-3 mb-3">
            <strong><?php echo htmlspecialchars($op['usuario']); ?></strong> - <?php echo str_repeat('⭐', $op['estrellas']); ?>
            <br><small><?php echo $op['fecha']; ?></small>
            <p><?php echo nl2br(htmlspecialchars($op['comentario'])); ?></p>
        </div>
    <?php endwhile; ?>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>