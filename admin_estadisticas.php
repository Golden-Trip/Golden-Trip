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

// --- Consulta para los "paquetes" más comprados (usando la tabla 'destinos') ---
// La consulta ahora se une a la tabla 'destinos' y usa el nombre del destino
$top_paquetes_query = "
    SELECT d.nombre, COUNT(c.paquete_comprado_id) AS total_compras
    FROM compras c
    JOIN destinos d ON c.paquete_comprado_id = d.id -- Cambiado de 'paquetes' a 'destinos'
    WHERE c.tipo_compra = 'paquete' AND c.paquete_comprado_id IS NOT NULL
    GROUP BY c.paquete_comprado_id
    ORDER BY total_compras DESC
    LIMIT 5
";
$top_paquetes = $conexion->query($top_paquetes_query);

// --- Consulta para los alojamientos más comprados/reservados ---
// Esta consulta ya estaba correcta y asume que la tabla 'alojamiento' sí existe
$top_alojamientos_query = "
    SELECT a.nombre, COUNT(c.alojamiento_reservado_id) AS total_compras
    FROM compras c
    JOIN alojamiento a ON c.alojamiento_reservado_id = a.id
    WHERE c.tipo_compra = 'alojamiento' AND c.alojamiento_reservado_id IS NOT NULL
    GROUP BY c.alojamiento_reservado_id
    ORDER BY total_compras DESC
    LIMIT 5
";
$top_alojamientos = $conexion->query($top_alojamientos_query);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Panel de Administración</title>
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
        .statistic-card { margin-bottom: 20px; }
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
        <a href="admin_estadisticas.php" class="active"><i class="bi bi-graph-up me-2"></i>Estadísticas</a>
        <a href="admin_compras.php"><i class="bi bi-bag-check me-2"></i>Compras</a>
    </div>

    <div class="main-content w-100">
        <h2><i class="bi bi-graph-up me-2"></i>Estadísticas del Negocio</h2>
        <p>Aquí puedes ver un resumen de las métricas clave de tu plataforma.</p>

        <div class="row">
            <div class="col-md-6">
                <div class="card statistic-card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Top 5 Paquetes Más Comprados</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($top_paquetes && $top_paquetes->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while ($paquete = $top_paquetes->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($paquete['nombre']) ?>
                                        <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($paquete['total_compras']) ?> compras</span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No hay datos de paquetes comprados aún o la tabla 'destinos' no contiene datos de paquetes.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card statistic-card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Top 5 Alojamientos Más Reservados</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($top_alojamientos && $top_alojamientos->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while ($alojamiento = $top_alojamientos->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($alojamiento['nombre']) ?>
                                        <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($alojamiento['total_compras']) ?> reservas</span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No hay datos de alojamientos reservados aún o la tabla 'alojamiento' no existe/contiene datos.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>