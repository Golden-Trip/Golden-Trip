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
$email_admin = $_SESSION['email'] ?? '';
$foto_admin = $_SESSION['foto'] ?? '';
$rol_admin = $_SESSION['rol'] ?? '';

// --- Obtener datos para las estadísticas del Dashboard ---
$total_usuarios = 0;
$total_paquetes = 0; // Usaremos la tabla 'destinos' como acordamos
$total_alojamientos = 0;
$total_opiniones = 0;
$total_compras = 0;

try {
    $result_usuarios = $conexion->query("SELECT COUNT(*) AS total FROM usuarios");
    if ($result_usuarios) {
        $total_usuarios = $result_usuarios->fetch_assoc()['total'];
    }

    $result_paquetes = $conexion->query("SELECT COUNT(*) AS total FROM destinos"); // Asumiendo que 'destinos' contiene tus paquetes
    if ($result_paquetes) {
        $total_paquetes = $result_paquetes->fetch_assoc()['total'];
    }

    $result_alojamientos = $conexion->query("SELECT COUNT(*) AS total FROM alojamiento"); // Asumiendo la tabla 'alojamiento'
    if ($result_alojamientos) {
        $total_alojamientos = $result_alojamientos->fetch_assoc()['total'];
    }

    $result_opiniones = $conexion->query("SELECT COUNT(*) AS total FROM opiniones"); // Asumiendo la tabla 'opiniones'
    if ($result_opiniones) {
        $total_opiniones = $result_opiniones->fetch_assoc()['total'];
    }

    $result_compras = $conexion->query("SELECT COUNT(*) AS total FROM compras");
    if ($result_compras) {
        $total_compras = $result_compras->fetch_assoc()['total'];
    }

} catch (Exception $e) {
    // En un entorno de producción, registrar el error y mostrar un mensaje genérico.
    error_log("Error al obtener estadísticas del dashboard: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 56px; /* Para dejar espacio para la barra de admin fija */
        }
        .admin-header {
            background-color: #343a40;
            color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed; /* Fija la barra en la parte superior */
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000; /* Asegura que esté por encima de otros elementos */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Sombra suave */
        }
        .admin-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover; /* Asegura que la imagen se vea bien */
        }
        .admin-sidebar {
            height: 100vh;
            background-color: #212529;
            color: white;
            padding-top: 1rem;
            position: sticky; /* Fija el sidebar al desplazarse */
            top: 56px; /* Debajo del admin-header */
            min-width: 200px; /* Ancho mínimo para que los enlaces se vean bien */
            flex-shrink: 0; /* Evita que el sidebar se encoja */
        }
        .admin-sidebar a {
            color: white;
            display: block;
            padding: 0.75rem 1rem;
            text-decoration: none;
            transition: background-color 0.2s ease; /* Transición suave al pasar el mouse */
        }
        .admin-sidebar a:hover {
            background-color: #343a40;
        }
        .admin-sidebar a.active {
            background-color: #343a40; /* Estilo para el enlace activo */
        }
        .main-content {
            padding: 2rem;
            flex-grow: 1; /* Permite que el contenido ocupe el espacio restante */
        }
    </style>
</head>
<body>

<div class="admin-header">
    <div class="d-flex align-items-center">
        Panel de Administración
        <a href="index.php" class="btn btn-sm btn-outline-light ms-4">
            <i class="bi bi-house-door me-2"></i>Ir al Sitio
        </a>
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
        <a href="admin_panel.php" class="active"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a href="admin_usuarios.php"><i class="bi bi-people-fill me-2"></i>Usuarios</a>
        <a href="admin_paquetes.php"><i class="bi bi-globe-americas me-2"></i>Paquetes</a>
        <a href="admin_alojamientos.php"><i class="bi bi-building me-2"></i>Alojamientos</a>
        <a href="admin_opiniones.php"><i class="bi bi-chat-dots me-2"></i>Opiniones</a>
        <a href="admin_estadisticas.php"><i class="bi bi-graph-up me-2"></i>Estadísticas</a>
        <a href="admin_compras.php"><i class="bi bi-bag-check me-2"></i>Compras</a>
    </div>

    <div class="main-content w-100">
        <h2>Bienvenido, <?php echo htmlspecialchars($nombre_admin); ?> 👋</h2>
        <p>Este es tu panel de control como administrador. Desde aquí podrás gestionar usuarios, destinos, alojamientos, opiniones y más.</p>
        
        <div class="alert alert-info">
            🚀 **Progreso del Panel:**
            <ul>
                <li>✅ **Dashboard:** Estructura principal lista y métricas dinámicas.</li>
                <li>✅ **Gestión de Usuarios:** Funcionalidades de listar, cambiar rol y eliminar implementadas.</li>
                <li>✅ **Gestión de Paquetes:** Funcionalidades de listar, agregar, editar y eliminar implementadas (con carga de imágenes).</li>
                <li>✅ **Gestión de Compras y Estadísticas:** Funcionalidades de listar y ver estadísticas implementadas.</li>
                <li>⏳ **Alojamientos, Opiniones, Configuración:** Próximas secciones a desarrollar.</li>
            </ul>
        </div>

        <div class="row g-4 mt-4">
            <div class="col-md-4">
                <div class="card text-bg-primary shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people me-2"></i>Usuarios Registrados</h5>
                        <p class="card-text fs-2">
                            <?= htmlspecialchars($total_usuarios) ?>
                        </p>
                        <a href="admin_usuarios.php" class="text-white text-decoration-none">Ver todos los usuarios <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-success shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-globe-americas me-2"></i>Paquetes Disponibles</h5>
                        <p class="card-text fs-2">
                            <?= htmlspecialchars($total_paquetes) ?>
                        </p>
                        <a href="admin_paquetes.php" class="text-white text-decoration-none">Gestionar paquetes <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-info shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-building me-2"></i>Alojamientos Registrados</h5>
                        <p class="card-text fs-2">
                            <?= htmlspecialchars($total_alojamientos) ?>
                        </p>
                        <a href="admin_alojamientos.php" class="text-white text-decoration-none">Gestionar alojamientos <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-warning shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-chat-dots me-2"></i>Opiniones Recibidas</h5>
                        <p class="card-text fs-2">
                            <?= htmlspecialchars($total_opiniones) ?>
                        </p>
                        <a href="admin_opiniones.php" class="text-white text-decoration-none">Gestionar opiniones <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-danger shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-bag me-2"></i>Compras Realizadas</h5>
                        <p class="card-text fs-2">
                            <?= htmlspecialchars($total_compras) ?>
                        </p>
                        <a href="admin_compras.php" class="text-white text-decoration-none">Ver todas las compras <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>