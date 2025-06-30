<?php
include 'conexion.php';
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener usuario (nombre, email)
$stmt = $conexion->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
}

// --- Obtener últimos destinos y alojamientos vistos (últimos 10 combinados) ---
// Se ha combinado la lógica para obtener ambos tipos de vistas
$stmt = $conexion->prepare("
    SELECT
        v.id AS vista_id,
        v.fecha_vista,
        v.tipo_vista,
        d.id AS destino_id,
        d.nombre AS destino_nombre,
        d.imagen AS destino_imagen,
        d.precio AS destino_precio,
        a.id AS alojamiento_id,
        a.nombre AS alojamiento_nombre,
        a.imagen AS alojamiento_imagen,
        a.direccion AS alojamiento_direccion,
        a.precio_noche AS alojamiento_precio_noche
    FROM
        vistas v
    LEFT JOIN
        destinos d ON v.destino_id = d.id AND v.tipo_vista = 'destino'
    LEFT JOIN
        alojamiento a ON v.alojamiento_id = a.id AND v.tipo_vista = 'alojamiento'
    WHERE
        v.usuario_id = ?
    ORDER BY
        v.fecha_vista DESC
    LIMIT 10
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$vistas_recientes = $stmt->get_result();
$stmt->close();


// --- Obtener historial de compras y reservas con todos los detalles ---
$stmt = $conexion->prepare("
    SELECT
        c.id AS compra_id,
        c.fecha_compra,
        c.cantidad,
        c.total,
        c.tipo_compra,
        d.nombre AS destino_general_nombre, -- El destino general asociado a la compra
        d_paquete.nombre AS paquete_nombre,
        d_paquete.origen AS paquete_origen,
        d_paquete.destino AS paquete_destino,
        d_paquete.fecha_salida AS paquete_fecha_salida,
        d_paquete.imagen AS paquete_imagen,
        d_paquete.precio AS paquete_precio_unitario,
        a.nombre AS alojamiento_nombre,
        a.direccion AS alojamiento_direccion,
        a.imagen AS alojamiento_imagen,
        a.precio_noche AS alojamiento_precio_noche
    FROM
        compras c
    JOIN
        destinos d ON c.destino_id = d.id
    LEFT JOIN
        destinos d_paquete ON c.paquete_comprado_id = d_paquete.id
    LEFT JOIN
        alojamiento a ON c.alojamiento_reservado_id = a.id
    WHERE
        c.usuario_id = ?
    ORDER BY
        c.fecha_compra DESC
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$compras_y_reservas = $stmt->get_result();
$stmt->close();

// SE ELIMINÓ LA LÓGICA DE OBTENER EL HISTORIAL DE PAGOS

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Historial de Compras y Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles1.css"> <style>
        body {
            background: #f9fafb;
        }
        h2 {
            font-weight: 700;
        }
        .compra-card, .vista-card { /* Añadido .vista-card para el mismo estilo */
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .item-detalle {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .item-detalle:last-child {
            border-bottom: none;
        }
        .item-detalle img {
            width: 80px;
            height: auto;
            object-fit: cover;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="container pt-5 mt-5">
    <h2>Hola, <?php echo htmlspecialchars($usuario['nombre']); ?></h2>

    <section class="mb-5">
        <h3>Últimos Vistos (Destinos y Alojamientos)</h3>
        <?php if ($vistas_recientes && $vistas_recientes->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <?php while ($vista = $vistas_recientes->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php if ($vista['tipo_vista'] == 'destino'): ?>
                                <img src="img/<?php echo htmlspecialchars($vista['destino_imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($vista['destino_nombre']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($vista['destino_nombre']); ?></h5>
                                    <p class="card-text">Destino</p>
                                    <p class="card-text">$<?php echo number_format($vista['destino_precio'], 2, ',', '.'); ?></p>
                                    <a href="detalle.php?id=<?php echo $vista['destino_id']; ?>" class="btn btn-primary btn-sm">Ver detalle</a>
                                </div>
                            <?php elseif ($vista['tipo_vista'] == 'alojamiento'): ?>
                                <img src="img/<?php echo htmlspecialchars($vista['alojamiento_imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($vista['alojamiento_nombre']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($vista['alojamiento_nombre']); ?></h5>
                                    <p class="card-text">Alojamiento</p>
                                    <p class="card-text"><?php echo htmlspecialchars($vista['alojamiento_direccion']); ?></p>
                                    <p class="card-text">Precio/noche: $<?php echo number_format($vista['alojamiento_precio_noche'], 2, ',', '.'); ?></p>
                                    <a href="alojamiento_detalle.php?id=<?php echo $vista['alojamiento_id']; ?>" class="btn btn-primary btn-sm">Ver detalle (Alojamiento)</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No hay destinos o alojamientos vistos recientemente.</p>
        <?php endif; ?>
    </section>


    <section class="mt-5">
        <h3>Historial de Compras y Reservas</h3>

        <?php if ($compras_y_reservas && $compras_y_reservas->num_rows > 0): ?>
            <?php while ($compra = $compras_y_reservas->fetch_assoc()): ?>
                <div class="compra-card">
                    <h5>
                        <i class="bi bi-journal-check text-primary"></i> Compra/Reserva N° <?php echo $compra['compra_id']; ?> | 
                        Tipo: <?php echo htmlspecialchars(ucfirst($compra['tipo_compra'])); ?> |
                        Fecha: <?php echo date('d/m/Y H:i', strtotime($compra['fecha_compra'])); ?>
                    </h5>
                    <p><strong>Total de la Compra:</strong> $<?php echo number_format($compra['total'], 2, ',', '.'); ?></p>
                    <p><strong>Cantidad:</strong> <?php echo htmlspecialchars($compra['cantidad']); ?></p>

                    <h6 class="mt-3">Detalle del Ítem:</h6>
                    <div class="item-detalle d-flex align-items-center">
                        <?php if ($compra['tipo_compra'] == 'paquete'): ?>
                            <img src="img/<?php echo htmlspecialchars($compra['paquete_imagen'] ?? 'default_paquete.jpg'); ?>" alt="Paquete" class="rounded me-3" />
                            <div>
                                <strong>Paquete: <?php echo htmlspecialchars($compra['paquete_nombre'] ?? 'N/A'); ?></strong><br>
                                Origen: <?php echo htmlspecialchars($compra['paquete_origen'] ?? 'N/A'); ?> |
                                Destino: <?php echo htmlspecialchars($compra['paquete_destino'] ?? 'N/A'); ?> |
                                Fecha de salida: <?php echo htmlspecialchars($compra['paquete_fecha_salida'] ?? 'N/A'); ?><br>
                                Precio unitario: $<?php echo number_format($compra['paquete_precio_unitario'] ?? 0, 2, ',', '.'); ?>
                            </div>
                        <?php elseif ($compra['tipo_compra'] == 'alojamiento'): ?>
                            <img src="img/<?php echo htmlspecialchars($compra['alojamiento_imagen'] ?? 'default_alojamiento.jpg'); ?>" alt="Alojamiento" class="rounded me-3" />
                            <div>
                                <strong>Alojamiento: <?php echo htmlspecialchars($compra['alojamiento_nombre'] ?? 'N/A'); ?></strong><br>
                                Dirección: <?php echo htmlspecialchars($compra['alojamiento_direccion'] ?? 'N/A'); ?><br>
                                Precio por noche: $<?php echo number_format($compra['alojamiento_precio_noche'] ?? 0, 2, ',', '.'); ?>
                            </div>
                        <?php else: ?>
                            <p><em>Tipo de compra desconocido para este registro.</em></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                No tienes compras o reservas registradas todavía.
            </div>
        <?php endif; ?>
    </section>

    </main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>