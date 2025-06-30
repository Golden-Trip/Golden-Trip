<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CÓDIGO DE SEGURIDAD CRÍTICO ---

// --- FIN CÓDIGO DE SEGURIDAD ---

include 'conexion.php'; // Incluimos la conexión a la base de datos

// Trae datos de la sesión para mostrar en el panel
$nombre_admin = $_SESSION['nombre'] ?? 'Administrador';
$foto_admin = $_SESSION['foto'] ?? '';
$rol_admin = $_SESSION['rol'] ?? '';

$message = ''; // Variable para mensajes de éxito/error

// --- LÓGICA PARA AGREGAR, EDITAR, ELIMINAR PAQUETES ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Función para manejar la carga de imágenes
    function uploadImage($file_input_name, $current_image_name = '') {
        $target_dir = "img/"; // Carpeta donde se guardarán las imágenes
        $uploaded_file_name = '';

        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES[$file_input_name]['tmp_name'];
            $file_name = basename($_FILES[$file_input_name]['name']);
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_file_name = uniqid('pkg_', true) . '.' . $file_extension; // Nombre único para evitar colisiones
            $target_file = $target_dir . $new_file_name;
            $uploadOk = 1;

            // Verificar si el archivo es una imagen real
            $check = getimagesize($file_tmp_name);
            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $GLOBALS['message'] = "El archivo no es una imagen.";
                $uploadOk = 0;
            }

            // Verificar tamaño del archivo (ej. max 5MB)
            if ($_FILES[$file_input_name]["size"] > 5000000) {
                $GLOBALS['message'] = "La imagen es demasiado grande (máx. 5MB).";
                $uploadOk = 0;
            }

            // Permitir ciertos formatos de archivo
            $allowed_extensions = array("jpg", "jpeg", "png", "gif");
            if (!in_array($file_extension, $allowed_extensions)) {
                $GLOBALS['message'] = "Solo se permiten archivos JPG, JPEG, PNG y GIF.";
                $uploadOk = 0;
            }

            // Mover el archivo si todo está OK
            if ($uploadOk == 1) {
                if (move_uploaded_file($file_tmp_name, $target_file)) {
                    // Si hay una imagen actual y es diferente a la nueva, eliminar la antigua
                    if (!empty($current_image_name) && $current_image_name !== $new_file_name && file_exists($target_dir . $current_image_name)) {
                        unlink($target_dir . $current_image_name);
                    }
                    return $new_file_name;
                } else {
                    $GLOBALS['message'] = "Hubo un error al subir la imagen.";
                }
            }
        } elseif (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_NO_FILE) {
            $GLOBALS['message'] = "Error de subida: " . $_FILES[$file_input_name]['error'];
        }
        // Si no se subió un nuevo archivo, o hubo error, devuelve el nombre de la imagen actual
        return $current_image_name;
    }

    switch ($action) {
        case 'add':
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $origen = trim($_POST['origen'] ?? '');
            $destino = trim($_POST['destino'] ?? '');
            $fecha_salida = trim($_POST['fecha_salida'] ?? ''); // Asume formato YYYY-MM-DD
            $imagen = ''; // Se actualizará si se sube una imagen

            if (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($origen) || empty($destino) || empty($fecha_salida)) {
                $message = '<div class="alert alert-danger">Error: Todos los campos obligatorios deben ser completados.</div>';
            } else {
                $imagen = uploadImage('imagen');
                if (!empty($GLOBALS['message'])) { // Si hubo un error en la subida, se muestra
                    $message = $GLOBALS['message'];
                }

                $stmt = $conexion->prepare("INSERT INTO destinos (nombre, descripcion, imagen, precio, origen, destino, fecha_salida) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssdsss", $nombre, $descripcion, $imagen, $precio, $origen, $destino, $fecha_salida);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Paquete agregado exitosamente.</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error al agregar paquete: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $message = '<div class="alert alert-danger">Error al preparar consulta de agregar: ' . $conexion->error . '</div>';
                }
            }
            break;

        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $origen = trim($_POST['origen'] ?? '');
            $destino = trim($_POST['destino'] ?? '');
            $fecha_salida = trim($_POST['fecha_salida'] ?? ''); // Asume formato YYYY-MM-DD
            $imagen_actual = $_POST['imagen_actual'] ?? ''; // Nombre de la imagen actual

            // Primero, obtenemos la imagen actual de la DB en caso de que no se suba una nueva
            $current_img_from_db = '';
            $stmt_img = $conexion->prepare("SELECT imagen FROM destinos WHERE id = ?");
            if ($stmt_img) {
                $stmt_img->bind_param("i", $id);
                $stmt_img->execute();
                $stmt_img->bind_result($current_img_from_db);
                $stmt_img->fetch();
                $stmt_img->close();
            }

            if (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($origen) || empty($destino) || empty($fecha_salida) || $id === 0) {
                $message = '<div class="alert alert-danger">Error: Todos los campos obligatorios deben ser completados para editar.</div>';
            } else {
                $imagen = uploadImage('imagen', $current_img_from_db); // Pasa la imagen actual para que la función la mantenga si no hay nueva subida
                if (!empty($GLOBALS['message'])) { // Si hubo un error en la subida, se muestra
                    $message = $GLOBALS['message'];
                }

                $stmt = $conexion->prepare("UPDATE destinos SET nombre = ?, descripcion = ?, imagen = ?, precio = ?, origen = ?, destino = ?, fecha_salida = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("sssdsssi", $nombre, $descripcion, $imagen, $precio, $origen, $destino, $fecha_salida, $id);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Paquete actualizado exitosamente.</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error al actualizar paquete: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $message = '<div class="alert alert-danger">Error al preparar consulta de editar: ' . $conexion->error . '</div>';
                }
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id === 0) {
                $message = '<div class="alert alert-danger">Error: ID de paquete no válido para eliminar.</div>';
            } else {
                // Opcional: Eliminar la imagen asociada antes de eliminar el registro de la DB
                $stmt_img = $conexion->prepare("SELECT imagen FROM destinos WHERE id = ?");
                if ($stmt_img) {
                    $stmt_img->bind_param("i", $id);
                    $stmt_img->execute();
                    $stmt_img->bind_result($imagen_a_eliminar);
                    $stmt_img->fetch();
                    $stmt_img->close();
                    
                    if (!empty($imagen_a_eliminar) && file_exists("img/" . $imagen_a_eliminar)) {
                        unlink("img/" . $imagen_a_eliminar);
                    }
                }

                $stmt = $conexion->prepare("DELETE FROM destinos WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Paquete eliminado exitosamente.</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error al eliminar paquete: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    $message = '<div class="alert alert-danger">Error al preparar consulta de eliminar: ' . $conexion->error . '</div>';
                }
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Paquetes - Panel de Administración</title>
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
        <a href="admin_paquetes.php" class="active"><i class="bi bi-globe-americas me-2"></i>Paquetes</a>
        <a href="admin_alojamientos.php"><i class="bi bi-building me-2"></i>Alojamientos</a>
        <a href="admin_opiniones.php"><i class="bi bi-chat-dots me-2"></i>Opiniones</a>
        <a href="admin_estadisticas.php"><i class="bi bi-graph-up me-2"></i>Estadísticas</a>
        <a href="admin_compras.php"><i class="bi bi-bag-check me-2"></i>Compras</a>
    </div>

    <div class="main-content w-100">
        <h2><i class="bi bi-globe-americas me-2"></i>Gestión de Paquetes</h2>
        <p>Aquí puedes administrar los destinos y paquetes de viaje disponibles en tu sitio.</p>

        <?php echo $message; // Muestra mensajes de éxito/error ?>

        <div class="mb-4 text-end">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#agregarPaqueteModal">
                <i class="bi bi-plus-circle me-2"></i>Agregar Nuevo Paquete
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                Listado de Paquetes
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Fecha Salida</th>
                                <th>Imagen</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Consulta para obtener todos los paquetes
                            $sql_paquetes = "SELECT id, nombre, descripcion, precio, origen, destino, fecha_salida, imagen FROM destinos ORDER BY id DESC";
                            $result_paquetes = $conexion->query($sql_paquetes);

                            if ($result_paquetes->num_rows > 0) {
                                while ($paquete = $result_paquetes->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($paquete['id']); ?></td>
                                        <td><?php echo htmlspecialchars($paquete['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($paquete['descripcion'], 0, 70)) . (strlen($paquete['descripcion']) > 70 ? '...' : ''); ?></td>
                                        <td>$<?php echo number_format($paquete['precio'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($paquete['origen']); ?></td>
                                        <td><?php echo htmlspecialchars($paquete['destino']); ?></td>
                                        <td><?php echo htmlspecialchars($paquete['fecha_salida']); ?></td>
                                        <td>
                                            <?php if (!empty($paquete['imagen'])): ?>
                                                <img src="img/<?php echo htmlspecialchars($paquete['imagen']); ?>" alt="Imagen de <?php echo htmlspecialchars($paquete['nombre']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editarPaqueteModal" 
                                                data-id="<?php echo $paquete['id']; ?>" 
                                                data-nombre="<?php echo htmlspecialchars($paquete['nombre']); ?>" 
                                                data-descripcion="<?php echo htmlspecialchars($paquete['descripcion']); ?>" 
                                                data-precio="<?php echo htmlspecialchars($paquete['precio']); ?>" 
                                                data-origen="<?php echo htmlspecialchars($paquete['origen']); ?>"
                                                data-destino="<?php echo htmlspecialchars($paquete['destino']); ?>"
                                                data-fecha_salida="<?php echo htmlspecialchars($paquete['fecha_salida']); ?>"
                                                data-imagen="<?php echo htmlspecialchars($paquete['imagen']); ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarPaqueteModal" 
                                                data-id="<?php echo $paquete['id']; ?>" 
                                                data-nombre="<?php echo htmlspecialchars($paquete['nombre']); ?>">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="9" class="text-center">No hay paquetes registrados.</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="agregarPaqueteModal" tabindex="-1" aria-labelledby="agregarPaqueteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="agregarPaqueteModalLabel">Agregar Nuevo Paquete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="admin_paquetes.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="add_nombre" class="form-label">Nombre del Paquete</label>
                                <input type="text" class="form-control" id="add_nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="add_descripcion" name="descripcion" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="add_precio" class="form-label">Precio</label>
                                <input type="number" step="0.01" class="form-control" id="add_precio" name="precio" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_origen" class="form-label">Origen</label>
                                <input type="text" class="form-control" id="add_origen" name="origen" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_destino" class="form-label">Destino</label>
                                <input type="text" class="form-control" id="add_destino" name="destino" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_fecha_salida" class="form-label">Fecha de Salida</label>
                                <input type="date" class="form-control" id="add_fecha_salida" name="fecha_salida" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_imagen" class="form-label">Imagen del Paquete</label>
                                <input type="file" class="form-control" id="add_imagen" name="imagen" accept="image/*">
                                <small class="form-text text-muted">La imagen se guardará en la carpeta `/img`.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar Paquete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editarPaqueteModal" tabindex="-1" aria-labelledby="editarPaqueteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarPaqueteModalLabel">Editar Paquete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="admin_paquetes.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="mb-3">
                                <label for="edit_nombre" class="form-label">Nombre del Paquete</label>
                                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_precio" class="form-label">Precio</label>
                                <input type="number" step="0.01" class="form-control" id="edit_precio" name="precio" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_origen" class="form-label">Origen</label>
                                <input type="text" class="form-control" id="edit_origen" name="origen" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_destino" class="form-label">Destino</label>
                                <input type="text" class="form-control" id="edit_destino" name="destino" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_fecha_salida" class="form-label">Fecha de Salida</label>
                                <input type="date" class="form-control" id="edit_fecha_salida" name="fecha_salida" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_imagen" class="form-label">Cambiar Imagen</label>
                                <input type="file" class="form-control" id="edit_imagen" name="imagen" accept="image/*">
                                <small class="form-text text-muted">Deja en blanco para mantener la imagen actual.</small>
                                <input type="hidden" name="imagen_actual" id="edit_imagen_actual">
                                <div class="mt-2" id="current_image_preview"></div>
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

        <div class="modal fade" id="eliminarPaqueteModal" tabindex="-1" aria-labelledby="eliminarPaqueteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eliminarPaqueteModalLabel">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar el paquete "<strong id="paqueteNombreEliminar"></strong>"?</p>
                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <form action="admin_paquetes.php" method="POST">
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
    // Llenar el modal de EDITAR con datos del paquete seleccionado
    var editarPaqueteModal = document.getElementById('editarPaqueteModal');
    editarPaqueteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que activó el modal
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');
        var descripcion = button.getAttribute('data-descripcion');
        var precio = button.getAttribute('data-precio');
        var origen = button.getAttribute('data-origen');
        var destino = button.getAttribute('data-destino');
        var fecha_salida = button.getAttribute('data-fecha_salida');
        var imagen = button.getAttribute('data-imagen');

        var modalTitle = editarPaqueteModal.querySelector('.modal-title');
        var modalIdInput = editarPaqueteModal.querySelector('#edit_id');
        var modalNombreInput = editarPaqueteModal.querySelector('#edit_nombre');
        var modalDescripcionInput = editarPaqueteModal.querySelector('#edit_descripcion');
        var modalPrecioInput = editarPaqueteModal.querySelector('#edit_precio');
        var modalOrigenInput = editarPaqueteModal.querySelector('#edit_origen');
        var modalDestinoInput = editarPaqueteModal.querySelector('#edit_destino');
        var modalFechaSalidaInput = editarPaqueteModal.querySelector('#edit_fecha_salida');
        var modalImagenActualInput = editarPaqueteModal.querySelector('#edit_imagen_actual');
        var modalCurrentImagePreview = editarPaqueteModal.querySelector('#current_image_preview');

        modalTitle.textContent = 'Editar Paquete: ' + nombre;
        modalIdInput.value = id;
        modalNombreInput.value = nombre;
        modalDescripcionInput.value = descripcion;
        modalPrecioInput.value = precio;
        modalOrigenInput.value = origen;
        modalDestinoInput.value = destino;
        modalFechaSalidaInput.value = fecha_salida;
        modalImagenActualInput.value = imagen; // Para saber qué imagen tiene actualmente

        if (imagen) {
            modalCurrentImagePreview.innerHTML = '<p>Imagen actual:</p><img src="img/' + imagen + '" style="max-width: 100px; height: auto; display: block; margin-top: 5px;">';
        } else {
            modalCurrentImagePreview.innerHTML = '<p>No hay imagen actual.</p>';
        }
    });

    // Llenar el modal de ELIMINAR con datos del paquete seleccionado
    var eliminarPaqueteModal = document.getElementById('eliminarPaqueteModal');
    eliminarPaqueteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Botón que activó el modal
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');

        var modalPaqueteNombre = eliminarPaqueteModal.querySelector('#paqueteNombreEliminar');
        var modalIdInput = eliminarPaqueteModal.querySelector('#eliminar_id');

        modalPaqueteNombre.textContent = nombre;
        modalIdInput.value = id;
    });
</script>
</body>
</html>