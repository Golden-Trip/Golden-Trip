<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CÓDIGO DE SEGURIDAD CRÍTICO ---
// Si el usuario NO está logueado O su rol NO es 'admin', lo redirigimos

// --- FIN CÓDIGO DE SEGURIDAD ---

include 'conexion.php';

// Trae datos de la sesión para mostrar en el panel
$nombre_admin = $_SESSION['nombre'] ?? 'Administrador';
$foto_admin = $_SESSION['foto'] ?? '';
$rol_admin = $_SESSION['rol'] ?? '';

$message = ''; // Variable para mensajes de éxito/error

// --- Lógica para EDITAR/ELIMINAR Opiniones ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $estrellas = intval($_POST['estrellas'] ?? 0);
            $comentario = trim($_POST['comentario'] ?? '');
            
            // Validaciones básicas
            if ($id === 0 || $estrellas < 1 || $estrellas > 5 || empty($comentario)) {
                $_SESSION['message'] = '<div class="alert alert-danger">Error: Todos los campos obligatorios (ID, Estrellas, Comentario) deben ser válidos para editar.</div>';
            } else {
                $stmt = $conexion->prepare("UPDATE opiniones SET estrellas=?, comentario=? WHERE id=?");
                if ($stmt) {
                    $stmt->bind_param("isi", $estrellas, $comentario, $id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = '<div class="alert alert-success">Opinión actualizada exitosamente.</div>';
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger">Error al actualizar opinión: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Error al preparar consulta de editar: ' . $conexion->error . '</div>';
                }
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id === 0) {
                $_SESSION['message'] = '<div class="alert alert-danger">Error: ID de opinión no válido para eliminar.</div>';
            } else {
                $stmt = $conexion->prepare("DELETE FROM opiniones WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = '<div class="alert alert-success">Opinión eliminada exitosamente.</div>';
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger">Error al eliminar opinión: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Error al preparar consulta de eliminar: ' . $conexion->error . '</div>';
                }
            }
            break;
    }
    header("Location: admin_opiniones.php"); // Siempre redirigir después de POST
    exit;
}

// --- Mensajes de la sesión (después de redirecciones) ---
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Limpiar el mensaje para que no se muestre de nuevo
}

// Listar opiniones con los nombres del alojamiento y destino
$opiniones = $conexion->query("
    SELECT o.id, o.usuario, o.estrellas, o.comentario, o.fecha,
           a.nombre AS nombre_alojamiento,
           d.nombre AS nombre_destino
    FROM opiniones o
    LEFT JOIN alojamiento a ON o.id_alojamiento = a.id
    LEFT JOIN destinos d ON o.destino_id = d.id
    ORDER BY o.fecha DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Opiniones - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 56px; }
        .admin-header { background-color: #343a40; color: white; padding: 1rem; display: flex; align-items: center; justify-content: space-between; position: fixed; width: 100%; top: 0; left: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .admin-header img { width: 40px; height: 40px; border-radius: 50%; }
        .admin-sidebar { height: 100vh; background-color: #212529; color: white; padding-top: 1rem; position: sticky; top: 56px; min-width: 200px; flex-shrink: 0; }
        .admin-sidebar a { color: white; display: block; padding: 0.75rem 1rem; text-decoration: none; transition: background-color 0.2s ease; }
        .admin-sidebar a:hover { background-color: #343a40; }
        .admin-sidebar a.active { background-color: #343a40; } /* Estilo para el enlace activo */
        .main-content { padding: 2rem; flex-grow: 1; }
        .table-responsive { overflow-x: auto; }
        /* Estilos específicos para esta página */
        .truncated-comment {
            max-height: 60px; /* Altura máxima para el comentario */
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Número de líneas a mostrar */
            -webkit-box-orient: vertical;
        }
    </style>
</head>
<body>

<div class="admin-header">
    <div>
        Panel de Administración
    </div>
    <div>
        <img src="<?php echo htmlspecialchars($foto_admin); ?>" alt="Avatar" class="me-2">
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
        <a href="admin_opiniones.php" class="active"><i class="bi bi-chat-dots me-2"></i>Opiniones</a>
        <a href="admin_estadisticas.php"><i class="bi bi-graph-up me-2"></i>Estadísticas</a>
        <a href="admin_compras.php"><i class="bi bi-bag-check me-2"></i>Compras</a>
    </div>

    <div class="main-content w-100">
        <h2><i class="bi bi-chat-dots me-2"></i>Gestión de Opiniones</h2>
        <p>Aquí puedes revisar, editar o eliminar las opiniones dejadas por los usuarios.</p>

        <?php echo $message; // Muestra mensajes de éxito/error ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                Listado de Opiniones
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Alojamiento</th>
                                <th>Destino</th>
                                <th>Estrellas</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($opiniones->num_rows > 0): ?>
                                <?php while ($o = $opiniones->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($o['id']) ?></td>
                                        <td><?= htmlspecialchars($o['usuario']) ?></td>
                                        <td><?= htmlspecialchars($o['nombre_alojamiento'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($o['nombre_destino'] ?? 'N/A') ?></td>
                                        <td><?= str_repeat('⭐', $o['estrellas']) ?></td>
                                        <td>
                                            <div class="truncated-comment" title="<?= htmlspecialchars($o['comentario']) ?>">
                                                <?= htmlspecialchars($o['comentario']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($o['fecha']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editarOpinionModal" 
                                                data-id="<?= $o['id'] ?>" 
                                                data-estrellas="<?= htmlspecialchars($o['estrellas']) ?>" 
                                                data-comentario="<?= htmlspecialchars($o['comentario']) ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarOpinionModal" 
                                                data-id="<?= $o['id'] ?>" 
                                                data-usuario="<?= htmlspecialchars($o['usuario']) ?>"
                                                data-comentario-preview="<?= substr(htmlspecialchars($o['comentario']), 0, 50) . (strlen($o['comentario']) > 50 ? '...' : '') ?>">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay opiniones registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editarOpinionModal" tabindex="-1" aria-labelledby="editarOpinionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarOpinionModalLabel">Editar Opinión</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="admin_opiniones.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit_estrellas" class="form-label">Calificación (Estrellas)</label>
                                    <input type="number" class="form-control" id="edit_estrellas" name="estrellas" min="1" max="5" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="edit_comentario" class="form-label">Comentario</label>
                                    <textarea class="form-control" id="edit_comentario" name="comentario" rows="5" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="eliminarOpinionModal" tabindex="-1" aria-labelledby="eliminarOpinionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eliminarOpinionModalLabel">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar la opinión de <strong id="opinionUsuarioEliminar"></strong>?</p>
                        <p>Comentario: "<em id="opinionComentarioPreview"></em>"</p>
                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <form action="admin_opiniones.php" method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" id="eliminar_id">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Llenar el modal de EDITAR con datos de la opinión seleccionada
    var editarOpinionModal = document.getElementById('editarOpinionModal');
    editarOpinionModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que activó el modal
        var id = button.getAttribute('data-id');
        var estrellas = button.getAttribute('data-estrellas');
        var comentario = button.getAttribute('data-comentario');

        var modalTitle = editarOpinionModal.querySelector('.modal-title');
        var modalIdInput = editarOpinionModal.querySelector('#edit_id');
        var modalEstrellasInput = editarOpinionModal.querySelector('#edit_estrellas');
        var modalComentarioInput = editarOpinionModal.querySelector('#edit_comentario');

        modalTitle.textContent = 'Editar Opinión ID: ' + id;
        modalIdInput.value = id;
        modalEstrellasInput.value = estrellas;
        modalComentarioInput.value = comentario;
    });

    // Llenar el modal de ELIMINAR con datos de la opinión seleccionada
    var eliminarOpinionModal = document.getElementById('eliminarOpinionModal');
    eliminarOpinionModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que activó el modal
        var id = button.getAttribute('data-id');
        var usuario = button.getAttribute('data-usuario');
        var comentarioPreview = button.getAttribute('data-comentario-preview');

        var modalUsuario = eliminarOpinionModal.querySelector('#opinionUsuarioEliminar');
        var modalComentarioPreview = eliminarOpinionModal.querySelector('#opinionComentarioPreview');
        var modalIdInput = eliminarOpinionModal.querySelector('#eliminar_id');

        modalUsuario.textContent = usuario;
        modalComentarioPreview.textContent = comentarioPreview;
        modalIdInput.value = id;
    });
</script>
</body>
</html>