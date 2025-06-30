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

// Función para manejar la carga de imágenes (reutilizada de admin_paquetes.php)
function uploadImage($file_input_name, $current_image_name = '') {
    $target_dir = "img/alojamientos/"; // Carpeta específica para imágenes de alojamientos
    $uploaded_file_name = '';

    // Asegúrate de que el directorio exista
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // Crea el directorio si no existe con permisos 0755
    }

    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES[$file_input_name]['tmp_name'];
        $file_name = basename($_FILES[$file_input_name]['name']);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid('alj_', true) . '.' . $file_extension; // Nombre único para evitar colisiones
        $target_file = $target_dir . $new_file_name;
        $uploadOk = 1;

        // Verificar si el archivo es una imagen real
        $check = getimagesize($file_tmp_name);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $GLOBALS['message'] = '<div class="alert alert-danger">El archivo no es una imagen.</div>';
            $uploadOk = 0;
        }

        // Verificar tamaño del archivo (ej. max 5MB)
        if ($_FILES[$file_input_name]["size"] > 5000000) {
            $GLOBALS['message'] = '<div class="alert alert-danger">La imagen es demasiado grande (máx. 5MB).</div>';
            $uploadOk = 0;
        }

        // Permitir ciertos formatos de archivo
        $allowed_extensions = array("jpg", "jpeg", "png", "gif");
        if (!in_array($file_extension, $allowed_extensions)) {
            $GLOBALS['message'] = '<div class="alert alert-danger">Solo se permiten archivos JPG, JPEG, PNG y GIF.</div>';
            $uploadOk = 0;
        }

        // Mover el archivo si todo está OK
        if ($uploadOk == 1) {
            if (move_uploaded_file($file_tmp_name, $target_file)) {
                // Si hay una imagen actual y es diferente a la nueva, eliminar la antigua
                if (!empty($current_image_name) && file_exists($target_dir . $current_image_name)) {
                    unlink($target_dir . $current_image_name);
                }
                return $new_file_name;
            } else {
                $GLOBALS['message'] = '<div class="alert alert-danger">Hubo un error al subir la imagen.</div>';
            }
        }
    } elseif (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_NO_FILE) {
        $GLOBALS['message'] = '<div class="alert alert-danger">Error de subida: ' . $_FILES[$file_input_name]['error'] . '</div>';
    }
    // Si no se subió un nuevo archivo, o hubo error, devuelve el nombre de la imagen actual
    return $current_image_name;
}


// --- Lógica para AGREGAR/EDITAR/ELIMINAR Alojamientos ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'add':
            $nombre = trim($_POST['nombre'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            // Las estrellas no se reciben de POST
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio_noche = floatval($_POST['precio_noche'] ?? 0);
            $id_destino = intval($_POST['id_destino'] ?? 0);
            $imagen = ''; // Se actualizará si se sube una imagen

            // Validación ajustada sin estrellas
            if (empty($nombre) || empty($direccion) || $precio_noche <= 0 || $id_destino <= 0) {
                $message = '<div class="alert alert-danger">Error: Todos los campos obligatorios deben ser completados y válidos (excluyendo las estrellas).</div>';
            } else {
                $imagen = uploadImage('imagen_alojamiento'); // 'imagen_alojamiento' es el nombre del input file
                if (!empty($GLOBALS['message'])) { // Si hubo un error en la subida de imagen
                    $message = $GLOBALS['message']; // Asigna el mensaje de error de la función
                }

                // Consulta de INSERT ajustada sin 'estrellas'
                $stmt = $conexion->prepare("INSERT INTO alojamiento (nombre, direccion, descripcion, imagen, precio_noche, id_destino) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssdsi", $nombre, $direccion, $descripcion, $imagen, $precio_noche, $id_destino);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = '<div class="alert alert-success">Alojamiento agregado exitosamente.</div>';
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger">Error al agregar alojamiento: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Error al preparar consulta de agregar: ' . $conexion->error . '</div>';
                }
            }
            break;

        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            // Las estrellas no se reciben de POST
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio_noche = floatval($_POST['precio_noche'] ?? 0);
            $id_destino = intval($_POST['id_destino'] ?? 0);
            $imagen_actual = $_POST['imagen_actual'] ?? ''; // Nombre de la imagen actual (hidden field)

            // Obtener la imagen actual de la DB para pasar a uploadImage
            $current_img_from_db = '';
            $stmt_img = $conexion->prepare("SELECT imagen FROM alojamiento WHERE id = ?");
            if ($stmt_img) {
                $stmt_img->bind_param("i", $id);
                $stmt_img->execute();
                $stmt_img->bind_result($current_img_from_db);
                $stmt_img->fetch();
                $stmt_img->close();
            }

            // Validación ajustada sin estrellas
            if ($id === 0 || empty($nombre) || empty($direccion) || $precio_noche <= 0 || $id_destino <= 0) {
                $message = '<div class="alert alert-danger">Error: Todos los campos obligatorios deben ser completados para editar.</div>';
            } else {
                $imagen = uploadImage('imagen_alojamiento', $current_img_from_db); // 'imagen_alojamiento' es el input file
                if (!empty($GLOBALS['message'])) { // Si hubo un error en la subida de imagen
                    $message = $GLOBALS['message'];
                }

                // Consulta de UPDATE ajustada sin 'estrellas'
                $stmt = $conexion->prepare("UPDATE alojamiento SET nombre=?, direccion=?, descripcion=?, imagen=?, precio_noche=?, id_destino=? WHERE id=?");
                if ($stmt) {
                    $stmt->bind_param("ssdsii", $nombre, $direccion, $descripcion, $imagen, $precio_noche, $id_destino, $id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = '<div class="alert alert-success">Alojamiento actualizado exitosamente.</div>';
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger">Error al actualizar alojamiento: ' . $stmt->error . '</div>';
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
                $_SESSION['message'] = '<div class="alert alert-danger">Error: ID de alojamiento no válido para eliminar.</div>';
            } else {
                // Eliminar la imagen asociada antes de eliminar el registro de la DB
                $stmt_img = $conexion->prepare("SELECT imagen FROM alojamiento WHERE id = ?");
                if ($stmt_img) {
                    $stmt_img->bind_param("i", $id);
                    $stmt_img->execute();
                    $stmt_img->bind_result($imagen_a_eliminar);
                    $stmt_img->fetch();
                    $stmt_img->close();
                    
                    if (!empty($imagen_a_eliminar) && file_exists("img/alojamientos/" . $imagen_a_eliminar)) {
                        unlink("img/alojamientos/" . $imagen_a_eliminar);
                    }
                }

                $stmt = $conexion->prepare("DELETE FROM alojamiento WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = '<div class="alert alert-success">Alojamiento eliminado exitosamente.</div>';
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger">Error al eliminar alojamiento: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Error al preparar consulta de eliminar: ' . $conexion->error . '</div>';
                }
            }
            break;
    }
    header("Location: admin_alojamientos.php"); // Siempre redirigir después de POST
    exit;
}

// --- Mensajes de la sesión (después de redirecciones) ---
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Limpiar el mensaje para que no se muestre de nuevo
}

// Listar destinos para el select en los modales
$destinos = $conexion->query("SELECT id, nombre FROM destinos ORDER BY nombre");

// --- Consulta para listar alojamientos (SIN LA PARTE DE RESERVAS y sin necesitar 'estrellas' en el SELECT) ---
// Aunque no lo editemos, el campo 'estrellas' seguirá viniendo en 'a.*' si lo guardas en la DB.
// Pero ya no lo mostraremos en la tabla.
$alojamientos = $conexion->query("
    SELECT a.*, d.nombre AS nombre_destino
    FROM alojamiento a
    JOIN destinos d ON a.id_destino = d.id
    ORDER BY a.id DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Alojamientos - Panel de Administración</title>
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
        .img-preview { max-width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
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
        <a href="admin_alojamientos.php" class="active"><i class="bi bi-building me-2"></i>Alojamientos</a>
        <a href="admin_opiniones.php"><i class="bi bi-chat-dots me-2"></i>Opiniones</a>
        <a href="admin_estadisticas.php"><i class="bi bi-graph-up me-2"></i>Estadísticas</a>
        <a href="admin_compras.php"><i class="bi bi-bag-check me-2"></i>Compras</a>
    </div>

    <div class="main-content w-100">
        <h2><i class="bi bi-building me-2"></i>Gestión de Alojamientos</h2>
        <p>Aquí puedes administrar los hoteles, hostales y otros tipos de alojamientos disponibles.</p>

        <?php echo $message; // Muestra mensajes de éxito/error ?>

        <div class="mb-4 text-end">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#agregarAlojamientoModal">
                <i class="bi bi-plus-circle me-2"></i>Agregar Nuevo Alojamiento
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                Listado de Alojamientos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Destino</th>
                                <th>Precio/Noche</th>
                                <th>Imagen</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($alojamientos->num_rows > 0): ?>
                                <?php while ($a = $alojamientos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['id']) ?></td>
                                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                                        <td><?= htmlspecialchars($a['nombre_destino']) ?></td>
                                        <td>USD <?= number_format($a['precio_noche'], 2) ?></td>
                                        <td>
                                            <?php if (!empty($a['imagen'])): ?>
                                                <img src="img/alojamientos/<?= htmlspecialchars($a['imagen']) ?>" alt="Imagen de <?= htmlspecialchars($a['nombre']) ?>" class="img-preview">
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editarAlojamientoModal" 
                                                data-id="<?= $a['id'] ?>" 
                                                data-nombre="<?= htmlspecialchars($a['nombre']) ?>" 
                                                data-direccion="<?= htmlspecialchars($a['direccion']) ?>" 
                                                data-descripcion="<?= htmlspecialchars($a['descripcion']) ?>" 
                                                data-imagen="<?= htmlspecialchars($a['imagen']) ?>" 
                                                data-precio_noche="<?= htmlspecialchars($a['precio_noche']) ?>" 
                                                data-id_destino="<?= htmlspecialchars($a['id_destino']) ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarAlojamientoModal" 
                                                data-id="<?= $a['id'] ?>" 
                                                data-nombre="<?= htmlspecialchars($a['nombre']) ?>">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay alojamientos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="agregarAlojamientoModal" tabindex="-1" aria-labelledby="agregarAlojamientoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="agregarAlojamientoModalLabel">Agregar Nuevo Alojamiento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="admin_alojamientos.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="add_nombre" class="form-label">Nombre del Alojamiento</label>
                                    <input type="text" class="form-control" id="add_nombre" name="nombre" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_direccion" class="form-label">Dirección</label>
                                    <input type="text" class="form-control" id="add_direccion" name="direccion" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_precio_noche" class="form-label">Precio por Noche (USD)</label>
                                    <input type="number" step="0.01" class="form-control" id="add_precio_noche" name="precio_noche" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_id_destino" class="form-label">Destino</label>
                                    <select class="form-select" id="add_id_destino" name="id_destino" required>
                                        <option value="">Seleccione un destino</option>
                                        <?php 
                                        // Resetear el puntero para el select de "Agregar"
                                        $destinos->data_seek(0); 
                                        while ($dest = $destinos->fetch_assoc()): ?>
                                            <option value="<?= $dest['id'] ?>"><?= htmlspecialchars($dest['nombre']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="add_descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="add_descripcion" name="descripcion" rows="3" required></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label for="add_imagen_alojamiento" class="form-label">Imagen del Alojamiento</label>
                                    <input type="file" class="form-control" id="add_imagen_alojamiento" name="imagen_alojamiento" accept="image/*">
                                    <small class="form-text text-muted">La imagen se guardará en la carpeta `/img/alojamientos/`.</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar Alojamiento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editarAlojamientoModal" tabindex="-1" aria-labelledby="editarAlojamientoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarAlojamientoModalLabel">Editar Alojamiento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="admin_alojamientos.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" id="edit_id">
                            <input type="hidden" name="imagen_actual" id="edit_imagen_actual"> <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit_nombre" class="form-label">Nombre del Alojamiento</label>
                                    <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_direccion" class="form-label">Dirección</label>
                                    <input type="text" class="form-control" id="edit_direccion" name="direccion" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_precio_noche" class="form-label">Precio por Noche (USD)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_precio_noche" name="precio_noche" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_id_destino" class="form-label">Destino</label>
                                    <select class="form-select" id="edit_id_destino" name="id_destino" required>
                                        <option value="">Seleccione un destino</option>
                                        <?php 
                                        // Resetear el puntero para el select de "Editar"
                                        $destinos->data_seek(0); 
                                        while ($dest = $destinos->fetch_assoc()): ?>
                                            <option value="<?= $dest['id'] ?>"><?= htmlspecialchars($dest['nombre']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="edit_descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label for="edit_imagen_alojamiento" class="form-label">Cambiar Imagen</label>
                                    <input type="file" class="form-control" id="edit_imagen_alojamiento" name="imagen_alojamiento" accept="image/*">
                                    <small class="form-text text-muted">Deja en blanco para mantener la imagen actual.</small>
                                    <div class="mt-2" id="current_image_preview"></div>
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

        <div class="modal fade" id="eliminarAlojamientoModal" tabindex="-1" aria-labelledby="eliminarAlojamientoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eliminarAlojamientoModalLabel">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar el alojamiento "<strong id="alojamientoNombreEliminar"></strong>"?</p>
                        <p class="text-danger">Esta acción no se puede deshacer y también eliminará la imagen asociada.</p>
                    </div>
                    <div class="modal-footer">
                        <form action="admin_alojamientos.php" method="POST">
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
    // Llenar el modal de EDITAR con datos del alojamiento seleccionado
    var editarAlojamientoModal = document.getElementById('editarAlojamientoModal');
    editarAlojamientoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que activó el modal
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');
        var direccion = button.getAttribute('data-direccion');
        // var estrellas = button.getAttribute('data-estrellas'); // Eliminado
        var descripcion = button.getAttribute('data-descripcion');
        var imagen = button.getAttribute('data-imagen');
        var precio_noche = button.getAttribute('data-precio_noche');
        var id_destino = button.getAttribute('data-id_destino');

        var modalTitle = editarAlojamientoModal.querySelector('.modal-title');
        var modalIdInput = editarAlojamientoModal.querySelector('#edit_id');
        var modalNombreInput = editarAlojamientoModal.querySelector('#edit_nombre');
        var modalDireccionInput = editarAlojamientoModal.querySelector('#edit_direccion');
        // var modalEstrellasInput = editarAlojamientoModal.querySelector('#edit_estrellas'); // Eliminado
        var modalDescripcionInput = editarAlojamientoModal.querySelector('#edit_descripcion');
        var modalImagenActualInput = editarAlojamientoModal.querySelector('#edit_imagen_actual');
        var modalPrecioNocheInput = editarAlojamientoModal.querySelector('#edit_precio_noche');
        var modalIdDestinoSelect = editarAlojamientoModal.querySelector('#edit_id_destino');
        var modalCurrentImagePreview = editarAlojamientoModal.querySelector('#current_image_preview');

        modalTitle.textContent = 'Editar Alojamiento: ' + nombre;
        modalIdInput.value = id;
        modalNombreInput.value = nombre;
        modalDireccionInput.value = direccion;
        // if (modalEstrellasInput) { modalEstrellasInput.value = estrellas; } // Eliminado
        modalDescripcionInput.value = descripcion;
        modalImagenActualInput.value = imagen; // Para saber qué imagen tiene actualmente
        modalPrecioNocheInput.value = precio_noche;
        modalIdDestinoSelect.value = id_destino; // Seleccionar la opción correcta

        if (imagen) {
            modalCurrentImagePreview.innerHTML = '<p>Imagen actual:</p><img src="img/alojamientos/' + imagen + '" class="img-preview d-block" style="width: 100px; height: auto;">';
        } else {
            modalCurrentImagePreview.innerHTML = '<p>No hay imagen actual.</p>';
        }
    });

    // Llenar el modal de ELIMINAR con datos del alojamiento seleccionado
    var eliminarAlojamientoModal = document.getElementById('eliminarAlojamientoModal');
    eliminarAlojamientoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que activó el modal
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');

        var modalAlojamientoNombre = eliminarAlojamientoModal.querySelector('#alojamientoNombreEliminar');
        var modalIdInput = eliminarAlojamientoModal.querySelector('#eliminar_id');

        modalAlojamientoNombre.textContent = nombre;
        modalIdInput.value = id;
    });
</script>
</body>
</html>