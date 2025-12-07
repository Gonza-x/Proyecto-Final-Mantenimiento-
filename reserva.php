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

$destino_id = isset($_GET['destino_id']) ? (int)$_GET['destino_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destino_id = isset($_POST['destino_id']) ? (int)$_POST['destino_id'] : $destino_id;
}

if ($destino_id <= 0) {
    die('Destino no válido.');
}

$stmt = $pdo->prepare("
    SELECT id, nombre, provincia, descripcion_larga, precio_base, imagen
    FROM destinos
    WHERE id = ? AND activo = 1
");
$stmt->execute([$destino_id]);
$destino = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$destino) {
    die('No se encontró el destino solicitado.');
}

$errores = [];
$mensaje_exito = '';
$reserva_creada = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre_contacto  = trim($_POST['nombre_contacto'] ?? '');
    $telefono_contacto = trim($_POST['telefono_contacto'] ?? '');
    $fecha_tour       = trim($_POST['fecha_tour'] ?? '');
    $adultos          = (int)($_POST['adultos'] ?? 0);
    $ninos            = (int)($_POST['ninos'] ?? 0);
    $jubilados        = (int)($_POST['jubilados'] ?? 0);
    $comentarios      = trim($_POST['comentarios'] ?? '');

    if ($nombre_contacto === '' || $telefono_contacto === '' || $fecha_tour === '') {
        $errores[] = 'Todos los campos marcados como obligatorios deben completarse.';
    }

    if ($adultos < 1 && $ninos < 1 && $jubilados < 1) {
        $errores[] = 'Debes reservar al menos 1 persona (adulto, niño o jubilado).';
    }

    $precio_base = (float)$destino['precio_base'];
    $total = $precio_base * (
        $adultos +
        ($ninos * 0.5) +
        ($jubilados * 0.75)
    );

    if ($total <= 0) {
        $errores[] = 'El total calculado de la reserva no es válido.';
    }

    if (empty($errores)) {
        $codigo_reserva = 'HOU-' . date('YmdHis') . '-' . mt_rand(1000, 9999);

        $stmt = $pdo->prepare("
            INSERT INTO reservas (
                codigo_reserva,
                id_usuario,
                id_destino,
                nombre_contacto,
                telefono_contacto,
                fecha_tour,
                adultos,
                ninos,
                jubilados,
                precio_base,
                total,
                estado,
                metodo_pago,
                comentarios
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente de Pago', NULL, ?
            )
        ");

        $stmt->execute([
            $codigo_reserva,
            $usuario['id'],
            $destino['id'],
            $nombre_contacto,
            $telefono_contacto,
            $fecha_tour,
            $adultos,
            $ninos,
            $jubilados,
            $precio_base,
            $total,
            $comentarios
        ]);

        $reserva_id = $pdo->lastInsertId();
        header('Location: pago.php?reserva_id=' . $reserva_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservar tour - HOU Panama Tours</title>
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
            <p class="hero-kicker">Reserva tu experiencia</p>
            <h1 class="hero-title"><?php echo htmlspecialchars($destino['nombre']); ?></h1>
            <p class="hero-subtitle">
                <?php echo htmlspecialchars($destino['descripcion_larga']); ?>
            </p>
            <p class="hero-subtitle">
                Provincia / comarca: <strong><?php echo htmlspecialchars($destino['provincia']); ?></strong><br>
                Precio base por adulto: <strong><?php echo number_format($destino['precio_base'], 2); ?> USD</strong>
            </p>
        </div>

        <div class="card">
            <?php if (!empty($destino['imagen'])): ?>
                <div class="card-image">
                    <img src="<?php echo htmlspecialchars($destino['imagen']); ?>"
                         alt="<?php echo htmlspecialchars($destino['nombre']); ?>">
                </div>
            <?php endif; ?>
            <div class="card-content">
                <h3 class="card-title">Detalles del tour</h3>
                <p class="card-description">
                    Completa el formulario para confirmar tu reserva. El total se calculará con base en el número
                    de personas y se mostrará en el siguiente paso de pago.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="form-card">
        <h2 class="form-title">Datos de la reserva</h2>
        <p class="form-subtitle">
            Revisa que la información sea correcta antes de continuar con el pago.
        </p>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error">
                <?php foreach ($errores as $e): ?>
                    <div>• <?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <input type="hidden" name="destino_id" value="<?php echo (int)$destino['id']; ?>">

            <div class="form-field">
                <label class="form-label" for="nombre_contacto">Nombre de contacto *</label>
                <input
                    type="text"
                    id="nombre_contacto"
                    name="nombre_contacto"
                    class="form-input"
                    required
                    value="<?php echo htmlspecialchars($_POST['nombre_contacto'] ?? $usuario['nombre']); ?>"
                >
            </div>

            <div class="form-field">
                <label class="form-label" for="telefono_contacto">Teléfono de contacto *</label>
                <input
                    type="text"
                    id="telefono_contacto"
                    name="telefono_contacto"
                    class="form-input"
                    required
                    value="<?php echo htmlspecialchars($_POST['telefono_contacto'] ?? ''); ?>"
                >
            </div>

            <div class="form-field">
                <label class="form-label" for="fecha_tour">Fecha del tour *</label>
                <input
                    type="date"
                    id="fecha_tour"
                    name="fecha_tour"
                    class="form-input"
                    required
                    value="<?php echo htmlspecialchars($_POST['fecha_tour'] ?? ''); ?>"
                >
            </div>

            <div class="form-field">
                <label class="form-label">Número de personas *</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:0.7rem;">
                    <div>
                        <label class="form-label" for="adultos">Adultos</label>
                        <input
                            type="number"
                            id="adultos"
                            name="adultos"
                            class="form-input"
                            min="0"
                            value="<?php echo htmlspecialchars($_POST['adultos'] ?? '1'); ?>"
                        >
                    </div>
                    <div>
                        <label class="form-label" for="ninos">Niños</label>
                        <input
                            type="number"
                            id="ninos"
                            name="ninos"
                            class="form-input"
                            min="0"
                            value="<?php echo htmlspecialchars($_POST['ninos'] ?? '0'); ?>"
                        >
                    </div>
                    <div>
                        <label class="form-label" for="jubilados">Jubilados</label>
                        <input
                            type="number"
                            id="jubilados"
                            name="jubilados"
                            class="form-input"
                            min="0"
                            value="<?php echo htmlspecialchars($_POST['jubilados'] ?? '0'); ?>"
                        >
                    </div>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="comentarios">Comentarios adicionales</label>
                <textarea
                    id="comentarios"
                    name="comentarios"
                    rows="3"
                    placeholder="Información extra relevante para el guía o el tour."
                ><?php echo htmlspecialchars($_POST['comentarios'] ?? ''); ?></textarea>
            </div>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">
                    Continuar al pago
                </button>
            </div>
        </form>
    </div>
</section>

<footer class="footer">
    <p>© <?php echo date('Y'); ?> HOU Panama Tours. Todos los derechos reservados.</p>
</footer>

</body>
</html>
