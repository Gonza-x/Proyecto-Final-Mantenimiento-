<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$usuario = null;
$stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if ($usuario) {
    $_SESSION['user_rol'] = $usuario['rol'];
}

$reservas = [];
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        d.nombre   AS destino_nombre,
        d.provincia,
        d.imagen
    FROM reservas r
    INNER JOIN destinos d ON r.id_destino = d.id
    WHERE r.id_usuario = ?
    ORDER BY r.creado_en DESC
");
$stmt->execute([$usuario['id']]);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

function clase_estado($estado) {
    switch ($estado) {
        case 'Pagado':
            return 'status-pagado';
        case 'Confirmado':
            return 'status-confirmado';
        case 'Cancelado':
            return 'status-cancelado';
        default:
            return 'status-pendiente';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis reservas - HOU Panama Tours</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <div class="header-inner">
        <div class="brand" onclick="window.location.href='index.php'" style="cursor:pointer;">
            <div class="brand-logo">
                <img src="Imagenes/empresas_hou.jpg"
                alt="HOU Panama Tours"
                style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            </div>
            <div>
                <div class="brand-title">HOU Panama Tours</div>
                <div class="brand-subtitle">Explora Panamá con expertos locales</div>
            </div>
        </div>

        <nav class="nav">
            <a href="index.php">Inicio</a>
            <?php if ($usuario): ?>
            <?php if (($usuario['rol'] ?? 'usuario') !== 'admin'): ?>
                <a href="mis-reservas.php">Mis reservas</a>
                <?php endif; ?>
                <?php if (($usuario['rol'] ?? 'usuario') === 'admin'): ?>
                    <a href="admin.php">Panel de Adminnistrador</a>
                <?php endif; ?>
                <span class="user-pill">👤 <?php echo htmlspecialchars($usuario['nombre']); ?></span>
                <a href="index.php?logout=1" class="nav-cta">Cerrar sesión</a>
            <?php else: ?>
            <a href="login.php" class="nav-cta">Iniciar sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="hero-grid">
        <div>
            <p class="hero-kicker">Historial de reservas</p>
            <h1 class="hero-title">Mis reservas</h1>
            <p class="hero-subtitle">
                Aquí puedes revisar el estado de tus reservas, ver los detalles de cada tour
                y continuar con el pago si aún está pendiente.
            </p>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <p class="section-kicker">Resumen</p>
        <h2 class="section-title">Reservas realizadas con tu cuenta</h2>
    </div>

    <?php if (empty($reservas)): ?>
        <p style="color:#fff;margin-top:0.5rem;">
            Aún no tienes reservas registradas. Explora los tours disponibles y haz tu primera reserva.
        </p>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Destino</th>
                        <th>Provincia / Comarca</th>
                        <th>Fecha del tour</th>
                        <th>Personas</th>
                        <th>Total (USD)</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['codigo_reserva']); ?></td>
                        <td><?php echo htmlspecialchars($r['destino_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($r['provincia']); ?></td>
                        <td><?php echo htmlspecialchars($r['fecha_tour']); ?></td>
                        <td>
                            <?php
                            echo 'Adultos: ' . (int)$r['adultos'];
                            if ($r['ninos'] > 0) {
                                echo ' / Niños: ' . (int)$r['ninos'];
                            }
                            if ($r['jubilados'] > 0) {
                                echo ' / Jubilados: ' . (int)$r['jubilados'];
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($r['total'], 2); ?></td>
                        <td>
                            <span class="table-status <?php echo clase_estado($r['estado']); ?>">
                                <?php echo htmlspecialchars($r['estado']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['estado'] === 'Pendiente de Pago'): ?>
                                <a class="btn btn-primary" href="pago.php?reserva_id=<?php echo (int)$r['id']; ?>">
                                    Pagar
                                </a>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:#4b5563;">Sin acciones</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
                <?php endif; ?>
        </div>
</section>

<footer class="footer">
    <p>© <?php echo date('Y'); ?> HOU Panama Tours. Todos los derechos reservados.</p>
</footer>

</body>
</html>
