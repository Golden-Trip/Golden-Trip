<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CÓDIGO DE SEGURIDAD CRÍTICO ---
// Si el usuario NO está logueado O su rol NO es 'admin', lo redirigimos

// --- FIN CÓDIGO DE SEGURIDAD ---

include 'conexion.php'; // Asegúrate de que este archivo contiene la conexión a tu base de datos

// Trae datos de la sesión para mostrar en el panel
$nombre_admin = $_SESSION['nombre'] ?? 'Administrador';
$foto_admin = $_SESSION['foto'] ?? '';
$rol_admin = $_SESSION['rol'] ?? '';

// --- Consulta para obtener todas las compras con detalles ---
// Se unen las tablas 'usuarios', 'destinos', y 'alojamiento'
// Se usa LEFT JOIN para asegurar que todas las compras se muestren, incluso si hay datos faltantes en las uniones
// La columna 'nombre_producto_comprado' se construye condicionalmente
$query_compras = "
    SELECT
        c.id AS compra_id,
        u.nombre AS nombre_usuario,
        u.email AS email_usuario,
        d.nombre AS nombre_destino_general,
        c.fecha_compra,
        c.cantidad,
        c.total,
        c.tipo_compra,
        -- Condición para mostrar el nombre del paquete o alojamiento
        CASE
            WHEN c.tipo_compra = 'paquete' THEN dp.nombre  -- dp.nombre es el nombre del destino/paquete
            WHEN c.tipo_compra = 'alojamiento' THEN a.nombre
            ELSE 'Desconocido'
        END AS nombre_producto_comprado
    FROM
        compras c
    JOIN
        usuarios u ON c.usuario_id = u.id
    LEFT JOIN
        destinos d ON c.destino_id = d.id -- Unión para el destino general
    LEFT JOIN
        destinos dp ON c.paquete_comprado_id = dp.id -- Unión para el nombre del paquete (si existe en destinos)
    LEFT JOIN
        alojamiento a ON c.alojamiento_reservado_id = a.id -- Unión para el nombre del alojamiento
    ORDER BY
        c.fecha_compra DESC;
";

$resultado_compras = $conexion->query($query_compras);

// Manejo de errores de consulta
if (!$resultado_compras) {
    echo "Error en la consulta de compras: " . $conexion->error;
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Compras - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 56px; }
        .admin-header { background-color: #343a40; color: white; padding: 1rem; display: flex; align-items: center; justify-content: space-between; position: fixed; width: 100%; top: 0; left: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .admin-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .admin-sidebar { height: 100vh; background-color: #212529; color: white; padding-top: 1rem; position: sticky; top: 56px; min-width: 200px; flex-shrink: 0; }
        .admin-sidebar a { color: white; display: block; padding: 0.75rem 1rem; text-decoration: none; transition: background-color 0.2s ease; }
        .admin-sidebar a:hover { background-color: #343a40; }
        .admin-sidebar a.active { background-color: #343a40; } /* Estilo para el enlace activo */
        .main-content { padding: 2rem; flex-grow: 1; }
        .table-responsive { max-height: calc(100vh - 180px); overflow-y: auto; } /* Ajusta la altura de la tabla */
    </style>
</head>
<body>

<div class="admin-header">
    <div>
        Panel de Administración
    </div>
    <div>
        <?php if (!empty($foto_admin)): ?>
            <img src="<?php echo htmlspecialchars($foto_admin); ?>" alt="Avatar" class="me-2 rounded-circle">
        <?php else: ?>
            <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
        <?php endif; ?>
        <span><?php echo htmlspecialchars($nombre_admin); ?> (<?php echo htmlspecialchars($rol_admin); ?>)</span>
        <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Cerrar Sesión</a>
    </div>
</div>

<div class="d-flex">
    <div class="admin-sidebar">
        <a href="admin_panel.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a href="admin_usuarios.php"><i class="bi bi-people-fill me-2"></i>Usuarios</a>
        <a href="admin_paquetes.php"><i class="bi bi-globe-americas me-2"></i>Paquetes</a>
        <a href="admin_alojamientos.php"><i class="bi bi-building me-2"></i>Alojamientos</a>
        <a href="admin_opiniones.php"><i class="bi bi-chat-dots me-2"></i>Opiniones</a>
        <a href="admin_estadisticas.php"><i class="bi bi-graph-up me-2"></i>Estadísticas</a>
        <a href="admin_compras.php" class="active"><i class="bi bi-bag-check me-2"></i>Compras</a>
    </div>

    <div class="main-content w-100">
        <h2><i class="bi bi-bag-check me-2"></i>Gestión de Compras</h2>
        <p>Aquí puedes ver un listado detallado de todas las compras y reservas realizadas en la plataforma.</p>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Listado de Compras</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID Compra</th>
                                <th>Usuario</th>
                                <th>Email Usuario</th>
                                <th>Destino General</th>
                                <th>Tipo Compra</th>
                                <th>Producto Comprado/Reservado</th>
                                <th>Fecha Compra</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resultado_compras->num_rows > 0): ?>
                                <?php while ($compra = $resultado_compras->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($compra['compra_id']) ?></td>
                                        <td><?= htmlspecialchars($compra['nombre_usuario']) ?></td>
                                        <td><?= htmlspecialchars($compra['email_usuario']) ?></td>
                                        <td><?= htmlspecialchars($compra['nombre_destino_general']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($compra['tipo_compra'])) ?></td>
                                        <td><?= htmlspecialchars($compra['nombre_producto_comprado'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($compra['fecha_compra']) ?></td>
                                        <td><?= htmlspecialchars($compra['cantidad']) ?></td>
                                        <td>$<?= htmlspecialchars(number_format($compra['total'], 2, ',', '.')) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No hay compras registradas aún.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>