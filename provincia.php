<?php
require_once 'config.php';

$usuario = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        $_SESSION['user_rol'] = $usuario['rol'];
    }
}

$slug = $_GET['slug'] ?? '';
$provincia = null;
$destinos = [];

if ($slug !== '') {
    $stmt = $pdo->prepare("
        SELECT provincia AS nombre_provincia
        FROM destinos
        WHERE provincia = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $provincia = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT id, nombre, descripcion_corta, descripcion_larga, precio_base, imagen
        FROM destinos
        WHERE provincia = ? AND activo = 1
        ORDER BY nombre
    ");
    $stmt->execute([$slug]);
    $destinos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HOU Panama Tours - Provincia</title>
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
            <p class="hero-kicker">Explora por provincia / comarca</p>
            <h1 class="hero-title">
                <?php
                if ($provincia) {
                    echo htmlspecialchars($provincia['nombre_provincia']);
                } else {
                    echo 'Provincia no encontrada';
                }
                ?>
            </h1>
            <p class="hero-subtitle">
                <?php if ($provincia): ?>
                    Descubre los mejores tours y experiencias disponibles en esta zona.
                <?php else: ?>
                    Lo sentimos, no pudimos cargar la información de esta provincia o comarca.
                <?php endif; ?>
            </p>
            <div class="hero-actions">
                <a href="index.php" class="btn btn-ghost">← Volver al inicio</a>
                <?php if ($usuario): ?>
                    <a href="mis-reservas.php" class="btn btn-primary">Ver mis reservas</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Crear cuenta / Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <p class="section-kicker">Destinos disponibles</p>
        <h2 class="section-title">
            Tours y actividades en
            <?php echo htmlspecialchars($provincia['nombre_provincia'] ?? $slug ?: 'esta provincia'); ?>
        </h2>
    </div>

    <?php if (!empty($destinos)): ?>
        <div class="card-grid">
            <?php foreach ($destinos as $d): ?>
                <div class="card">
                    <?php if (!empty($d['imagen'])): ?>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($d['imagen']); ?>"
                                 alt="<?php echo htmlspecialchars($d['nombre']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <h3 class="card-title">
                            <?php echo htmlspecialchars($d['nombre']); ?>
                        </h3>
                        <p class="card-description">
                            <?php echo htmlspecialchars($d['descripcion_corta']); ?>
                        </p>
                        <p class="card-description" style="margin-top:0.5rem;">
                            <?php echo htmlspecialchars($d['descripcion_larga']); ?>
                        </p>
                        <p class="card-description" style="margin-top:0.5rem;font-weight:600;">
                            Desde <?php echo number_format($d['precio_base'], 2); ?> USD
                        </p>
                        <div class="hero-actions" style="margin-top:1rem;">
                            <form method="get" action="reserva.php">
                                <input type="hidden" name="destino_id" value="<?php echo (int)$d['id']; ?>">
                                <button class="btn btn-primary" type="submit">Reservar ahora</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#fff;margin-top:1rem;">
            No hay tours configurados para esta provincia/comarca.
        </p>
    <?php endif; ?>
</section>

<footer class="footer">
    <p>© <?php echo date('Y'); ?> HOU Panama Tours. Todos los derechos reservados.</p>
</footer>

</body>
</html>
