<?php
require_once 'config.php';

$login_error = '';
$register_error = '';
$success_message = '';
$blocked_seconds_left = 0;

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['blocked_until'])) $_SESSION['blocked_until'] = null;

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success_message = 'Cuenta creada correctamente. Ahora puedes iniciar sesión.';
}

$activeTab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $activeTab = 'register';

        $nombre   = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if ($nombre === '' || $email === '' || $password === '' || $confirm === '') {
            $register_error = 'Todos los campos son obligatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $register_error = 'El correo electrónico no es válido.';
        } elseif (strlen($password) < 6) {
            $register_error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $confirm) {
            $register_error = 'Las contraseñas no coinciden.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $register_error = 'Ya existe un usuario con ese correo.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO usuarios (nombre, email, password_hash, rol, activo) 
                     VALUES (?, ?, ?, 'usuario', 1)"
                );
                $stmt->execute([$nombre, $email, $hash]);

                header('Location: login.php?registered=1');
                exit;
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $activeTab = 'login';

        $now = time();

        // Si está bloqueado, calcular segundos restantes
        if (!empty($_SESSION['blocked_until']) && $now < $_SESSION['blocked_until']) {
            $blocked_seconds_left = $_SESSION['blocked_until'] - $now;
        } else {
            // Reset de bloqueo
            $_SESSION['blocked_until'] = null;
            $_SESSION['login_attempts'] = 0;

            $email    = trim($_POST['loginEmail'] ?? '');
            $password = $_POST['loginPassword'] ?? '';

            if ($email === '' || $password === '') {
                $login_error = 'Debes ingresar correo y contraseña.';
            } else {
                $stmt = $pdo->prepare("SELECT id, nombre, email, password_hash, rol, activo 
                FROM usuarios WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $esValido = false;

            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                $esValido = true;
            } else {
                if (hash('sha256', $password) === $user['password_hash']) {
                $esValido = true;
        }
    }
}

if (!$user || !$esValido) {
    $_SESSION['login_attempts']++;

    if ($_SESSION['login_attempts'] >= 3) {
        // Bloqueo de 60 segundos
        $_SESSION['blocked_until'] = $now + 60;
        $blocked_seconds_left = 60;
        $login_error = 'Demasiados intentos fallidos. Tu cuenta se ha bloqueado durante 60 segundos.';
    } else {
        $login_error = 'Correo o contraseña incorrectos.';
    }
} elseif ((int)$user['activo'] === 0) {
    $login_error = 'Tu usuario está inactivo. Contacta con el administrador.';
} else {
    // Login correcto
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['user_rol'] = $user['rol'];
    $_SESSION['login_attempts'] = 0;
    $_SESSION['blocked_until']  = null;

    header('Location: index.php');
    exit;
}

                
            }
        }
    }
}

if (!empty($_SESSION['blocked_until'])) {
    $now = time();
    if ($now < $_SESSION['blocked_until']) {
        $blocked_seconds_left = $_SESSION['blocked_until'] - $now;
    } else {
        $_SESSION['blocked_until'] = null;
        $_SESSION['login_attempts'] = 0;
        $blocked_seconds_left = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HOU Panama Tours - Iniciar sesión</title>
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
            <a href="login.php" class="nav-cta">Iniciar sesión</a>
        </nav>
    </div>
</header>

<main class="auth-layout">
    <section class="card auth-card">
        <div class="auth-header">
            <p class="section-kicker">Accede a tu cuenta</p>
            <h1 class="section-title">Iniciar sesión o registrarse</h1>
            <p class="card-description">
                Usa tu correo electrónico para crear una cuenta nueva o acceder a tus reservas.
            </p>
        </div>

        <div class="auth-tabs">
            <button type="button"
                    class="tab-btn <?php echo $activeTab === 'login' ? 'active' : ''; ?>"
                    data-tab="login">
                Iniciar sesión
            </button>
            <button type="button"
                    class="tab-btn <?php echo $activeTab === 'register' ? 'active' : ''; ?>"
                    data-tab="register">
                Registrarse
            </button>
        </div>

        <?php if ($success_message): ?>
            <div class="auth-alert auth-alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($login_error): ?>
            <div class="auth-alert auth-alert-error" id="login-error">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($register_error): ?>
            <div class="auth-alert auth-alert-error" id="register-error">
                <?php echo htmlspecialchars($register_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($blocked_seconds_left > 0): ?>
            <div class="auth-alert auth-alert-warning blocked-message">
                Tu cuenta está bloqueada. Espera
                <span id="countdown"><?php echo (int)$blocked_seconds_left; ?></span> segundos.
            </div>
        <?php endif; ?>

        <div class="tab-content <?php echo $activeTab === 'login' ? 'active' : ''; ?>" id="tab-login">
            <form method="post" class="auth-form">
                <input type="hidden" name="action" value="login">

                <div class="form-row">
                    <label for="loginEmail">Correo electrónico</label>
                    <input type="email" id="loginEmail" name="loginEmail"
                           placeholder="tu@correo.com" required>
                </div>

                <div class="form-row">
                    <label for="loginPassword">Contraseña</label>
                    <input type="password" id="loginPassword" name="loginPassword"
                           placeholder="••••••••" required>
                </div>

                <div class="auth-actions">
                    <button type="submit" class="btn btn-primary" <?php echo $blocked_seconds_left > 0 ? 'disabled' : ''; ?>>
                        Iniciar sesión
                    </button>
                </div>
            </form>
        </div>

        <div class="tab-content <?php echo $activeTab === 'register' ? 'active' : ''; ?>" id="tab-register">
            <form method="post" class="auth-form">
                <input type="hidden" name="action" value="register">

                <div class="form-row">
                    <label for="name">Nombre completo</label>
                    <input type="text" id="name" name="name"
                           placeholder="Nombre y apellido" required>
                </div>

                <div class="form-row">
                    <label for="regEmail">Correo electrónico</label>
                    <input type="email" id="regEmail" name="email"
                           placeholder="tu@correo.com" required>
                </div>

                <div class="form-row">
                    <label for="regPass">Contraseña</label>
                    <input type="password" id="regPass" name="password"
                           placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-row">
                    <label for="regConfirm">Confirmar contraseña</label>
                    <input type="password" id="regConfirm" name="confirm"
                           placeholder="Repite la contraseña" required>
                </div>

                <div class="auth-actions">
                    <button type="submit" class="btn btn-primary">
                        Crear cuenta
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© <?php echo date('Y'); ?> HOU Panama Tours. Todos los derechos reservados.</p>
</footer>

<script>
// Tabs
const tabButtons = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const target = btn.dataset.tab;

        tabButtons.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById('tab-' + target).classList.add('active');
    });
});

// Cuenta regresiva de bloqueo
let blockedSeconds = <?php echo (int)$blocked_seconds_left; ?>;
if (blockedSeconds > 0) {
    const countdown = document.getElementById('countdown');
    const interval = setInterval(() => {
        blockedSeconds--;
        if (blockedSeconds <= 0) {
            clearInterval(interval);
            if (countdown) countdown.textContent = '0';
            const blockedBox = document.querySelector('.blocked-message');
            if (blockedBox) blockedBox.style.display = 'none';
        } else {
            if (countdown) countdown.textContent = blockedSeconds;
        }
    }, 1000);
}
</script>

</body>
</html>
