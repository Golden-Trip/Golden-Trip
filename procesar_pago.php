<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario']) && !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['usuario'] ?? $_SESSION['email'];

// Obtener el ID del usuario logueado (lo necesitamos para la tabla 'compras')
$id_usuario_actual = null;
$stmt_user = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt_user->bind_param("s", $email);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($usuario_data = $result_user->fetch_assoc()) {
    $id_usuario_actual = $usuario_data['id'];
}
$stmt_user->close();

// Verificar que el carrito no esté vacío
if (empty($_SESSION['carrito'])) {
    echo "<p>Tu carrito está vacío. <a href='index.php'>Volver a la tienda</a></p>";
    exit;
}

// ---------- DATOS DEL FORMULARIO ----------
$nombre_persona     = $_POST['nombre_persona'] ?? '';
$apellido_persona   = $_POST['apellido_persona'] ?? '';
$dni                = $_POST['dni'] ?? '';
$telefono           = $_POST['telefono'] ?? '';

$nombre_tarjeta     = $_POST['nombre'] ?? '';
$metodo_pago        = $_POST['metodo'] ?? '';
$numero_tarjeta     = $_POST['numero'] ?? '';
$total_compra_final = $_POST['total'] ?? 0; // Cambiado el nombre de la variable para evitar confusión con el total por item

// ---------- VALIDACIÓN ----------
if ($nombre_persona === '' || $apellido_persona === '' || $dni === '' || $telefono === '') {
    echo "<p>Faltan datos personales. <a href='pago.php'>Volver al formulario</a></p>";
    exit;
}

if ($nombre_tarjeta === '' || $metodo_pago === '' || $numero_tarjeta === '') {
    echo "<p>Faltan datos de pago. <a href='pago.php'>Volver al formulario</a></p>";
    exit;
}

// ---------- GUARDAR DATOS PERSONALES ----------
// NOTA: Esta sección guarda datos personales si el DNI no existe.
// Si quieres vincular estos datos a un usuario específico y que aparezcan en su perfil,
// como hablamos antes, la tabla `datos_personales` necesitaría una columna `usuario_id`
// y aquí tendrías que insertar o actualizar ese `usuario_id` también.
// Por ahora, solo se guarda por DNI.
$stmt_check = $conexion->prepare("SELECT id_dato_personal FROM datos_personales WHERE dni = ?");
$stmt_check->bind_param("s", $dni);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows === 0) {
    $stmt_insert = $conexion->prepare("INSERT INTO datos_personales (nombre, apellido, dni, telefono) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("ssss", $nombre_persona, $apellido_persona, $dni, $telefono);
    $stmt_insert->execute();
    $stmt_insert->close();
}
$stmt_check->close();

// ---------- GUARDAR DATOS DE PAGO (si tienes una tabla 'pago') ----------
$stmt_pago = $conexion->prepare("INSERT INTO pago (usuario_email, total, metodo_pago, nombre_tarjeta, numero_tarjeta) VALUES (?, ?, ?, ?, ?)");
$stmt_pago->bind_param("sdsss", $email, $total_compra_final, $metodo_pago, $nombre_tarjeta, $numero_tarjeta);

if ($stmt_pago->execute()) {
    $stmt_pago->close(); // Cerrar el statement del pago

    // ---------- NUEVA SECCIÓN: GUARDAR COMPRAS EN LA TABLA 'compras' ----------
    // Asegúrate de que $id_usuario_actual no sea null y que el carrito no esté vacío
    if ($id_usuario_actual && !empty($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item_id => $item_data) {
            // 'item_id' aquí podría ser el ID del destino, o 'item_data['id']' si es un array asociativo
            // Asumimos que $item_data contiene al menos 'id' (destino_id) y 'cantidad'
            $destino_id = $item_data['id'] ?? $item_id; // Usa item_data['id'] si tu carrito lo tiene, sino item_id
            $cantidad_item = $item_data['cantidad'] ?? 1; // Asume 1 si no está definida la cantidad

            // Es CRUCIAL obtener el precio del destino desde la tabla 'destinos'
            // para calcular el 'total' de cada línea de compra.
            $precio_destino = 0;
            $stmt_precio_destino = $conexion->prepare("SELECT precio FROM destinos WHERE id = ?");
            if ($stmt_precio_destino) {
                $stmt_precio_destino->bind_param("i", $destino_id);
                $stmt_precio_destino->execute();
                $result_precio = $stmt_precio_destino->get_result();
                if ($row_precio = $result_precio->fetch_assoc()) {
                    $precio_destino = $row_precio['precio'];
                }
                $stmt_precio_destino->close();
            } else {
                error_log("Error al preparar la consulta para obtener precio del destino: " . $conexion->error);
            }

            $total_item = $cantidad_item * $precio_destino;

            // Preparar la inserción en la tabla 'compras'
            // Nota: Si tienes columnas como 'paquete_comprado_id', 'alojamiento_reservado_id',
            // o 'tipo_compra', deberías incluirlas aquí si aplicaran.
            // Por ahora, solo las columnas básicas necesarias según tu CREATE TABLE de 'compras'.
            $stmt_compra = $conexion->prepare("INSERT INTO compras (usuario_id, destino_id, cantidad, total, tipo_compra) VALUES (?, ?, ?, ?, 'destino')");
            if ($stmt_compra) {
                $stmt_compra->bind_param("iidd", $id_usuario_actual, $destino_id, $cantidad_item, $total_item);

                if (!$stmt_compra->execute()) {
                    // Si hay un error al insertar una compra, lo registramos.
                    // Puedes decidir qué hacer aquí: mostrar un error al usuario,
                    // revertir el pago, etc. Por ahora, solo lo logueamos.
                    error_log("Error al insertar compra para usuario " . $id_usuario_actual . ", destino " . $destino_id . ": " . $stmt_compra->error);
                }
                $stmt_compra->close();
            } else {
                error_log("Error al preparar la consulta de inserción en compras: " . $conexion->error);
            }
        }
    } else {
        // Esto se ejecuta si no hay un usuario logueado o el carrito está vacío después del pago.
        // Podría indicar un flujo inesperado o un error en la sesión.
        error_log("Advertencia: Intento de guardar compra sin usuario logueado o carrito vacío.");
    }

    // Limpiar carrito después de guardar todo
    unset($_SESSION['carrito']);

    echo "<p>✅ Pago procesado correctamente. <a href='index.php'>Volver al inicio</a></p>";
} else {
    echo "<p>Error al guardar los datos de pago: " . htmlspecialchars($stmt_pago->error) . "</p>";
    error_log("Error MySQL al guardar datos de pago: " . $stmt_pago->error); // Registrar en logs
}
?>