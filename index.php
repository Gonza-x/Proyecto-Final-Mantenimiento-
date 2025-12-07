<?php
require_once 'config.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$usuario = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        $_SESSION['user_rol'] = $usuario['rol'];
    }
}

$COMARCAS = ['Emberá-Wounaan', 'Guna Yala', 'Ngäbe-Buglé'];
$placeholders = implode(',', array_fill(0, count($COMARCAS), '?'));

$provincias = [];
$stmt = $pdo->prepare("
    SELECT 
        provincia AS nombre,
        MIN(imagen) AS imagen,
        MIN(descripcion_corta) AS descripcion
    FROM destinos
    WHERE activo = 1
      AND provincia NOT IN ($placeholders)
    GROUP BY provincia
    ORDER BY provincia
");
$stmt->execute($COMARCAS);
$provincias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$comarcas = [];
$stmt = $pdo->prepare("
    SELECT 
        provincia AS nombre,
        MIN(imagen) AS imagen,
        MIN(descripcion_corta) AS descripcion
    FROM destinos
    WHERE activo = 1
      AND provincia IN ($placeholders)
    GROUP BY provincia
    ORDER BY provincia
");
$stmt->execute($COMARCAS);
$comarcas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HOU Panama Tours - Descubre Panamá</title>
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
            <p class="hero-kicker">Explora Panamá</p>
            <h1 class="hero-title">Explora Panamá con expertos locales</h1>
            <p class="hero-subtitle">
                Reservas 100% online y experiencias auténticas
                en todas las provincias y comarcas del país.
            </p>

            <div class="hero-actions">
                <a href="#provincias" class="btn btn-primary">
                    Ver provincias disponibles
                </a>

                <?php if ($usuario): ?>
                    <a href="mis-reservas.php" class="btn btn-ghost">
                        Ver mis reservas
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-ghost">
                        Crear cuenta / Iniciar sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-content">
                    <h3 class="card-title">Tu próxima aventura te espera</h3>
                    <p class="card-description">
                        Elige una provincia o comarca, revisa los tours disponibles y reserva en pocos pasos.
                    </p>
                    <div class="card-stats">
                        <div>
                            <div class="stat-value"><?php echo count($provincias); ?></div>
                            <div class="stat-label">Provincias con tours</div>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo count($comarcas); ?></div>
                            <div class="stat-label">Comarcas con tours</div>
                        </div>
                        <div>
                            <div class="stat-value">100%</div>
                            <div class="stat-label">Reservas online</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="provincias" class="section">
    <div class="section-header">
        <p class="section-kicker">Explora por provincia</p>
        <h2 class="section-title">
            Selecciona una provincia para ver todos sus tours disponibles
        </h2>
    </div>

    <?php if (!empty($provincias)): ?>
        <div class="card-grid">
            <?php foreach ($provincias as $prov): ?>
                <a class="card card-link" href="provincia.php?slug=<?php echo urlencode($prov['nombre']); ?>">
                    <?php if (!empty($prov['imagen'])): ?>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($prov['imagen']); ?>"
                                 alt="<?php echo htmlspecialchars($prov['nombre']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <h3 class="card-title">
                             <?php echo htmlspecialchars($prov['nombre']); ?>
                        </h3>
                        <p class="card-description">
                            <?php echo htmlspecialchars($prov['descripcion'] ?? 'Ver tours disponibles en esta provincia.'); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#fff;margin-top:1rem;">
            No hay tours registrados en provincias por el momento.
        </p>
    <?php endif; ?>
</section>

<section id="comarcas" class="section">
    <div class="section-header">
        <p class="section-kicker">Explora por comarca</p>
        <h2 class="section-title">
            Selecciona una comarca para ver todos sus tours disponibles
        </h2>
    </div>

    <?php if (!empty($comarcas)): ?>
        <div class="card-grid">
            <?php foreach ($comarcas as $com): ?>
                <a class="card card-link" href="provincia.php?slug=<?php echo urlencode($com['nombre']); ?>">
                    <?php if (!empty($com['imagen'])): ?>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($com['imagen']); ?>"
                                 alt="<?php echo htmlspecialchars($com['nombre']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <h3 class="card-title">
                             <?php echo htmlspecialchars($com['nombre']); ?>
                        </h3>
                        <p class="card-description">
                            <?php echo htmlspecialchars($com['descripcion'] ?? 'Ver tours disponibles en esta comarca.'); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#fff;margin-top:1rem;">
            No hay tours registrados en comarcas por el momento.
        </p>
    <?php endif; ?>
</section>

<footer class="footer">
    <p>© <?php echo date('Y'); ?> HOU Panama Tours. Todos los derechos reservados.</p>
</footer>

</body>
</html>
