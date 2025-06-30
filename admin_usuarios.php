<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CÓDIGO DE SEGURIDAD CRÍTICO ---
// Asegúrate de que solo los administradores puedan acceder a esta página

// --- FIN CÓDIGO DE SEGURIDAD ---

include 'conexion.php';

// Trae datos de la sesión para mostrar en el panel
$nombre_admin = $_SESSION['nombre'] ?? 'Administrador';
$foto_admin = $_SESSION['foto'] ?? '';
$rol_admin = $_SESSION['rol'] ?? '';

// --- Lógica para Cambiar Rol ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $nuevoRol = $_POST['rol'] ?? '';
    $usuarioId = intval($_POST['usuario_id'] ?? 0);

    // Validación extra: No permitir que un admin cambie su propio rol
    if ($usuarioId === $_SESSION['usuario_id'] && $nuevoRol !== 'admin') {
        $_SESSION['message'] = '<div class="alert alert-danger">No puedes cambiar tu propio rol de administrador.</div>';
    } elseif ($usuarioId > 0 && in_array($nuevoRol, ['admin', 'usuario'])) {
        $stmt = $conexion->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $nuevoRol, $usuarioId);
            if ($stmt->execute()) {
                // Si el rol del usuario actual fue cambiado, actualiza la sesión
                if ($usuarioId === $_SESSION['usuario_id']) {
                    $_SESSION['rol'] = $nuevoRol;
                }
                $_SESSION['message'] = '<div class="alert alert-success">Rol de usuario actualizado exitosamente.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Error al actualizar rol: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Error al preparar consulta de rol: ' . $conexion->error . '</div>';
        }
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Datos de rol o ID inválidos.</div>';
    }
    header("Location: admin_usuarios.php");
    exit;
}

// --- Lógica para Eliminar Usuario ---
if (isset($_GET['eliminar'])) {
    $id_a_eliminar = intval($_GET['eliminar']);

    // Validación: No permitir que un admin se elimine a sí mismo
    if ($id_a_eliminar === $_SESSION['usuario_id']) {
        $_SESSION['message'] = '<div class="alert alert-danger">No puedes eliminar tu propia cuenta de administrador.</div>';
    } elseif ($id_a_eliminar > 0) {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_a_eliminar);
            if ($stmt->execute()) {
                $_SESSION['message'] = '<div class="alert alert-success">Usuario eliminado exitosamente.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Error al eliminar usuario: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Error al preparar consulta de eliminación: ' . $conexion->error . '</div>';
        }
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">ID de usuario no válido para eliminar.</div>';
    }
    header("Location: admin_usuarios.php");
    exit;
}

// --- Mensajes de la sesión (después de redirecciones) ---
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Limpiar el mensaje para que no se muestre de nuevo
}

// Obtener todos los usuarios
// Usar sentencia preparada aunque no haya parámetros para consistencia y seguridad
$usuarios = $conexion->query("SELECT id, nombre, email, rol, foto FROM usuarios ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Panel de Administración</title>
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
        /* Estilos para las fotos de perfil en la tabla */
        .table img { object-fit: cover; }
        .table .bi-person-circle { vertical-align: middle; }
    </style>
</head>
<body>

<div class="admin-header">
    <div>
        Panel de Administración
    </div>
    <div>
        <?php
        $admin_foto_src = '';
        if ($foto_admin) {
            // Comprobar si es una URL completa (e.g., de Google)
            if (strpos($foto_admin, 'http://') === 0 || strpos($foto_admin, 'https://') === 0) {
                $admin_foto_src = htmlspecialchars($foto_admin);
            } else {
                // Si no es una URL completa, asume que es un archivo local en 'img/'
                $admin_foto_src = 'img/' . htmlspecialchars($foto_admin);
            }
        } else {
            // URL de una imagen de marcador de posición si no hay foto
            $admin_foto_src = 'https://via.placeholder.com/40'; // O una imagen por defecto local
        }
        ?>
        <img src="<?php echo $admin_foto_src; ?>" alt="Avatar" class="me-2">
        <span><?php echo htmlspecialchars($nombre_admin); ?> (<?php echo htmlspecialchars($rol_admin); ?>)</span>
        <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Cerrar Sesión</a>
    </div>
</div>

<div class="d-flex">
    <div class="admin-sidebar">
        <a href="admin_panel.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a href="admin_usuarios.php" class="active"><i class="bi bi-people-fill me-2"></i>Usuarios</a>
        <a href="admin_paquetes.php"><i class="bi bi-globe-americas me-2"></i>Paquetes</a>
        <a href="admin_alojamientos.php"><i class="bi bi-building me-2"></i>Alojamientos</a>
        <a href="admin_opiniones.php"><i class="bi bi-chat-dots me-2"></i>Opiniones</a>
        <a href="admin_estadisticas.php"><i class="bi bi-graph-up me-2"></i>Estadísticas</a>
        <a href="admin_compras.php"><i class="bi bi-bag-check me-2"></i>Compras</a>
    </div>

    <div class="main-content w-100">
        <h2><i class="bi bi-people-fill me-2"></i>Gestión de Usuarios</h2>
        <p>Aquí puedes administrar los usuarios registrados en tu plataforma, cambiar sus roles o eliminarlos.</p>

        <?php echo $message; // Muestra mensajes de éxito/error ?>

        <?php if ($usuarios->num_rows === 0): ?>
            <div class="alert alert-warning mt-4">No hay usuarios registrados.</div>
        <?php else: ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white">
                    Listado de Usuarios
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($u = $usuarios->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $user_foto_src = '';
                                            if ($u['foto']) {
                                                if (strpos($u['foto'], 'http://') === 0 || strpos($u['foto'], 'https://') === 0) {
                                                    $user_foto_src = htmlspecialchars($u['foto']);
                                                } else {
                                                    $user_foto_src = 'img/' . htmlspecialchars($u['foto']);
                                                }
                                            } else {
                                                $user_foto_src = 'https://via.placeholder.com/40'; // O una imagen por defecto local
                                            }
                                            ?>
                                            <img src="<?php echo $user_foto_src; ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;" alt="Foto de <?= htmlspecialchars($u['nombre']) ?>">
                                        </td>
                                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                                <select name="rol" class="form-select form-select-sm w-auto">
                                                    <option value="usuario" <?= $u['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                                    <option value="admin" <?= $u['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                                <button type="submit" name="cambiar_rol" class="btn btn-sm btn-outline-primary">
                                                    Actualizar Rol
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarUsuarioModal" data-id="<?= $u['id'] ?>" data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="modal fade" id="eliminarUsuarioModal" tabindex="-1" aria-labelledby="eliminarUsuarioModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eliminarUsuarioModalLabel">Confirmar Eliminación de Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar al usuario "<strong id="usuarioNombreEliminar"></strong>"?</p>
                        <p class="text-danger">Esta acción es irreversible y eliminará todos los datos asociados al usuario.</p>
                    </div>
                    <div class="modal-footer">
                        <form action="admin_usuarios.php" method="GET">
                            <input type="hidden" name="eliminar" id="eliminar_usuario_id">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Eliminar Usuario</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Llenar el modal de ELIMINAR con datos del usuario seleccionado
    var eliminarUsuarioModal = document.getElementById('eliminarUsuarioModal');
    eliminarUsuarioModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que activó el modal
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');

        var modalUsuarioNombre = eliminarUsuarioModal.querySelector('#usuarioNombreEliminar');
        var modalIdInput = eliminarUsuarioModal.querySelector('#eliminar_usuario_id');

        modalUsuarioNombre.textContent = nombre;
        modalIdInput.value = id;
    });
</script>
</body>
</html>