<?php
include 'conexion.php';
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ⚡ Verificar sesión
$email = $_SESSION['usuario'] ?? $_SESSION['email'] ?? null;
if (!$email) {
    header("Location: login.php");
    exit;
}

// Traer usuario de BD (quitamos 'fecha_registro' de la consulta)
$stmt = $conexion->prepare("SELECT id, nombre, email FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
}

// Traer historial de compras (asumiendo 'compras' table tiene 'usuario_id')
$stmt = $conexion->prepare("
    SELECT c.*, d.nombre AS destino_nombre, d.imagen, d.precio
    FROM compras c
    JOIN destinos d ON c.destino_id = d.id
    WHERE c.usuario_id = ?
    ORDER BY c.fecha_compra DESC
");
$stmt->bind_param("i", $usuario['id']);
$stmt->execute();
$compras = $stmt->get_result();
$stmt->close();

// Foto perfil: toma la URL de $_SESSION['foto'] si existe y no está vacía
$fotoPerfil = (!empty($_SESSION['foto']))
    ? $_SESSION['foto']
    : "https://api.dicebear.com/7.x/initials/svg?seed=" . urlencode($usuario['nombre']);

// Traer datos personales directamente asociados al usuario logueado
$datos_personales = null; // Initialize to null
if ($usuario['id']) { // Ensure user ID exists
    $stmtDatos = $conexion->prepare("SELECT nombre, apellido, dni, telefono FROM datos_personales WHERE usuario_id = ?");
    $stmtDatos->bind_param("i", $usuario['id']);
    $stmtDatos->execute();
    $datos_personales = $stmtDatos->get_result()->fetch_assoc();
    $stmtDatos->close();
}


include 'header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles1.css">
    <style>
        .perfil-container {
            position: relative;
            display: flex;
            gap: 30px;
            background-color: #f9f9f9;
            padding: 25px 30px;
            border-radius: 12px;
            max-width: 700px;
            margin: 0 auto 40px auto;
            box-sizing: border-box;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
        }

        .perfil-container img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 3px solid #a0aec0;
            transition: transform 0.3s ease;
            cursor: default;
        }

        .perfil-container img:hover {
            transform: scale(1.05);
        }

        .perfil-info {
            flex: 1 1 100%;
            color: #2d3748;
        }

        .perfil-info h4 {
            font-weight: 700;
            font-size: 1.9rem;
            margin-bottom: 8px;
        }

        .perfil-info p {
            margin: 5px 0;
            font-size: 1.1rem;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .perfil-info p i {
            color: #718096;
            font-size: 1.2rem;
        }

        .btn-cerrar-sesion {
            background-color: transparent;
            border: 2px solid #e53e3e;
            color: #e53e3e;
            font-weight: 600;
            padding: 8px 22px;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-cerrar-sesion:hover {
            background-color: #e53e3e;
            color: white;
        }

        .cerrar-sesion-container {
            position: absolute;
            bottom: -120px; /* Adjust as needed */
            right: -290px; /* Adjust as needed */
        }

        .perfil-compras {
            max-width: 900px;
            margin: 0 auto;
        }

        .perfil-compras h3 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            text-align: center;
        }

        .perfil-compras table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            font-size: 1rem;
            color: #2d3748;
        }

        .perfil-compras thead tr {
            background-color: #e2e8f0;
            text-align: left;
            font-weight: 700;
            color: #1a202c;
            border-radius: 12px;
        }

        .perfil-compras th,
        .perfil-compras td {
            padding: 12px 18px;
        }

        .perfil-compras tbody tr {
            background-color: #f7fafc;
            border-radius: 10px;
            transition: background-color 0.3s ease;
        }

        .perfil-compras tbody tr:hover {
            background-color: #e6fffa;
        }

        .perfil-compras img {
            width: 110px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body>

<main class="container my-5">

    <div class="perfil-container">
        <img src="<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Avatar" />

        <div class="perfil-info">
            <h4><?php echo htmlspecialchars($usuario['nombre']); ?></h4>
            <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?></p>
            <p><i class="bi bi-calendar-check"></i> Miembro desde:
                Fecha no disponible
            </p>

            <button class="btn btn-info mt-3" onclick="toggleInfo()">Ver información personal</button>

            <div id="info-personal" class="mt-3" style="display: none;">
                <?php if ($datos_personales): ?>
                    <p><i class="bi bi-person-vcard"></i> Nombre completo: <?php echo htmlspecialchars($datos_personales['nombre'] . ' ' . $datos_personales['apellido']); ?></p>
                    <p><i class="bi bi-credit-card-2-front"></i> DNI: <?php echo htmlspecialchars($datos_personales['dni']); ?></p>
                    <p><i class="bi bi-telephone"></i> Teléfono: <?php echo htmlspecialchars($datos_personales['telefono']); ?></p>
                <?php else: ?>
                    <p>No hay información personal cargada para este usuario.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="cerrar-sesion-container">
            <button onclick="window.location.href='logout.php'" class="btn-cerrar-sesion">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
            </button>
        </div>
    </div>

    <section class="perfil-compras mt-5">
        <h3>Historial de Compras</h3>
        <?php if ($compras->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th># Orden</th>
                            <th>Destino</th>
                            <th>Imagen</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Total</th>
                            <th>Fecha Compra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($compra = $compras->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($compra['id']); ?></td>
                                <td><?php echo htmlspecialchars($compra['destino_nombre']); ?></td>
                                <td><img src="img/<?php echo htmlspecialchars($compra['imagen']); ?>" alt="<?php echo htmlspecialchars($compra['destino_nombre']); ?>"></td>
                                <td><?php echo htmlspecialchars($compra['cantidad']); ?></td>
                                <td>$<?php echo number_format($compra['precio'], 2); ?></td>
                                <td>$<?php echo number_format($compra['cantidad'] * $compra['precio'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($compra['fecha_compra'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">No has realizado ninguna compra aún.</p>
        <?php endif; ?>
    </section>

</main>

<?php include 'footer.php'; ?>

<script>
    function toggleInfo() {
        const div = document.getElementById("info-personal");
        div.style.display = div.style.display === "none" ? "block" : "none";
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>