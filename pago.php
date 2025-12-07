<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if ($usuario) {
    $_SESSION['user_rol'] = $usuario['rol'];
}

$reserva_id = isset($_GET['reserva_id']) ? (int)$_GET['reserva_id'] : 0;

$reserva = null;
$error   = '';
$exito   = '';

if ($reserva_id > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            d.nombre    AS destino_nombre,
            d.provincia AS destino_provincia,
            d.imagen    AS destino_imagen
        FROM reservas r
        INNER JOIN destinos d ON r.id_destino = d.id
        WHERE r.id = ? AND r.id_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$reserva_id, $usuario['id']]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pago']) && $reserva) {
    if ($reserva['estado'] !== 'Pendiente de Pago') {
        $error = 'Esta reserva ya no se encuentra en estado "Pendiente de Pago".';
    } else {
        $metodo = $_POST['metodo'] ?? 'tarjeta';
        $numero = preg_replace('/\s+/', '', $_POST['numero'] ?? '');
        $titular = trim($_POST['titular'] ?? '');
        $exp = trim($_POST['exp'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');

        if ($numero === '' || $titular === '' || $exp === '' || $cvv === '') {
            $error = 'Todos los campos de la tarjeta son obligatorios.';
        } elseif (!preg_match('/^[0-9]{13,19}$/', $numero)) {
            $error = 'Número de tarjeta inválido.';
        } elseif (!preg_match('/^[0-9]{3}$/', $cvv)) {
            $error = 'CVV inválido.';
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $exp)) {
            $error = 'Fecha de expiración inválida. Usa el formato MM/AA.';
        } else {
            $referencia = 'PAY-' . date('YmdHis') . '-' . substr($numero, -4);

            $stmt = $pdo->prepare("
                INSERT INTO pagos (id_reserva, metodo, referencia, estado)
                VALUES (?, ?, ?, 'Pagado')
            ");
            $stmt->execute([$reserva['id'], $metodo, $referencia]);

            $stmt = $pdo->prepare("
                UPDATE reservas
                SET estado = 'Pagado', metodo_pago = ?
                WHERE id = ? AND id_usuario = ?
            ");
            $stmt->execute([$metodo, $reserva['id'], $usuario['id']]);

            $exito = 'Pago registrado correctamente. Tu reserva ha sido marcada como "Pagado".';
            $stmt = $pdo->prepare("
                SELECT 
                    r.*,
                    d.nombre    AS destino_nombre,
                    d.provincia AS destino_provincia,
                    d.imagen    AS destino_imagen
                FROM reservas r
                INNER JOIN destinos d ON r.id_destino = d.id
                WHERE r.id = ? AND r.id_usuario = ?
                LIMIT 1
            ");
            $stmt->execute([$reserva_id, $usuario['id']]);
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesar pago - HOU Panama Tours</title>
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
            <a href="mis-reservas.php">Mis reservas</a>
            <?php if (($usuario['rol'] ?? 'usuario') === 'admin'): ?>
                <a href="admin.php">Panel admin</a>
            <?php endif; ?>
            <span class="user-pill">👤 <?php echo htmlspecialchars($usuario['nombre']); ?></span>
            <a href="index.php?logout=1" class="nav-cta">Cerrar sesión</a>
        </nav>
    </div>
</header>

<main class="auth-layout" style="max-width:1000px;">
    <section class="card auth-card" style="width:100%;">
        <div class="auth-header">
            <p class="section-kicker">Procesar pago</p>
            <h1 class="section-title">Procesar pago</h1>
            <p class="card-description">
                Revisa los datos de tu reserva y registra el pago realizado.
            </p>
        </div>

        <?php if (!$reserva): ?>
            <div class="auth-alert auth-alert-error">
                No se encontró la reserva seleccionada o no pertenece al usuario actual.
            </div>
            <a href="mis-reservas.php" class="btn btn-ghost" style="margin-top:1rem;">
                Volver a mis reservas
            </a>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div class="auth-alert auth-alert-success">
                    <?php echo htmlspecialchars($exito); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:1.5rem;">
                <?php if (!empty($reserva['destino_imagen'])): ?>
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($reserva['destino_imagen']); ?>"
                             alt="<?php echo htmlspecialchars($reserva['destino_nombre']); ?>">
                    </div>
                <?php endif; ?>
                <div class="card-content">
                    <h3 class="card-title">
                        <?php echo htmlspecialchars($reserva['destino_nombre']); ?>
                    </h3>
                    <p class="card-description">
                        Provincia / comarca:
                        <strong><?php echo htmlspecialchars($reserva['destino_provincia']); ?></strong><br>
                        Fecha del tour:
                        <strong><?php echo htmlspecialchars($reserva['fecha_tour']); ?></strong><br>
                        Total a pagar:
                        <strong><?php echo number_format($reserva['total'], 2); ?> USD</strong><br>
                        Estado actual:
                        <strong><?php echo htmlspecialchars($reserva['estado']); ?></strong>
                    </p>
                </div>
            </div>

            <form method="post" class="auth-form">
                <div class="form-row">
                    <label>Método de pago</label>
                    <select name="metodo" class="form-input">
                        <option value="tarjeta">Tarjeta de crédito / débito</option>
                        <option value="transferencia">Transferencia bancaria</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>Número de tarjeta</label>
                    <input type="text" name="numero" maxlength="19"
                           placeholder="XXXX XXXX XXXX XXXX" required>
                </div>

                <div class="form-row">
                    <label>Nombre del titular</label>
                    <input type="text" name="titular"
                           placeholder="Nombre como aparece en la tarjeta" required>
                </div>

                <div class="form-row">
                    <label>Fecha de expiración de la tarjeta</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.7rem;">
                        <input type="text" name="exp" maxlength="5"
                               placeholder="MM/AA" required>
                        <input type="text" name="cvv" maxlength="3"
                               placeholder="CVV" required>
                    </div>
                </div>

                <div class="auth-actions">
                    <button type="submit" name="procesar_pago" class="btn btn-primary">
                        Pagar ahora
                    </button>
                </div>
            </form>

            <div style="margin-top:1rem;">
                <a href="mis-reservas.php" class="btn btn-ghost">Volver a mis reservas</a>
            </div>

        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <p>© <?php echo date('Y'); ?> HOU Panama Tours. Todos los derechos reservados.</p>
</footer>

</body>
</html>
