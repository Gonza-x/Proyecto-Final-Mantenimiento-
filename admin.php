<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$errores  = [];
$exito    = '';

/* ==========================
   Cambio de estado de reservas
   ========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {
    $reserva_id   = (int)($_POST['reserva_id'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $motivo       = trim($_POST['motivo'] ?? '');

    $estados_validos = ['Pendiente de Pago', 'Pagado', 'Confirmado', 'Cancelado'];

    if ($reserva_id <= 0 || !in_array($nuevo_estado, $estados_validos, true)) {
        $errores[] = 'Datos inválidos para actualizar el estado de la reserva.';
    } else {
        $stmt = $pdo->prepare("SELECT id, estado FROM reservas WHERE id = ?");
        $stmt->execute([$reserva_id]);
        $reserva_actual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reserva_actual) {
            $errores[] = 'La reserva seleccionada no existe.';
        } elseif ($reserva_actual['estado'] === $nuevo_estado) {
            $errores[] = 'La reserva ya se encuentra en ese estado.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO historial_estados (id_reserva, estado_anterior, nuevo_estado, id_admin, motivo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reserva_actual['id'],
                $reserva_actual['estado'],
                $nuevo_estado,
                $usuario['id'],
                $motivo !== '' ? $motivo : null
            ]);

            $stmt = $pdo->prepare("
                UPDATE reservas
                SET estado = ?
                WHERE id = ?
            ");
            $stmt->execute([$nuevo_estado, $reserva_actual['id']]);

            $exito = 'Estado de la reserva actualizado correctamente.';
        }
    }
}

/* ==========================
   Gestión de usuarios (Admin)
   ========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_usuario') {
    $usuario_id   = (int)($_POST['usuario_id'] ?? 0);
    $nombre_nuevo = trim($_POST['nombre'] ?? '');
    $email_nuevo  = trim($_POST['email'] ?? '');
    $rol_nuevo    = $_POST['rol'] ?? '';
    $activo_nuevo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

    if ($usuario_id <= 0 || $nombre_nuevo === '' || $email_nuevo === '') {
        $errores[] = 'Debes completar el nombre y correo electrónico del usuario.';
    } elseif (!filter_var($email_nuevo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico indicado no es válido.';
    } elseif (!in_array($rol_nuevo, ['usuario', 'admin'], true)) {
        $errores[] = 'Rol de usuario inválido.';
    } elseif (!in_array($activo_nuevo, [0, 1], true)) {
        $errores[] = 'Estado de cuenta inválido.';
    } else {
        // Verificamos que el usuario exista
        $stmt = $pdo->prepare("SELECT id, rol, activo FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario_objetivo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario_objetivo) {
            $errores[] = 'El usuario seleccionado no existe.';
        } else {
            // Protección: no permitir desactivar ni quitar el rol admin al propio usuario logueado
            if ($usuario_id === (int)$usuario['id'] && ($rol_nuevo !== 'admin' || $activo_nuevo !== 1)) {
                $errores[] = 'Por seguridad, no puedes cambiar tu propio rol ni desactivar tu cuenta de administrador.';
            } else {
                // Protección: no dejar el sistema sin administradores activos
                if ($usuario_objetivo['rol'] === 'admin' && ($rol_nuevo !== 'admin' || $activo_nuevo === 0)) {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1");
                    $total_admins_activos = (int)$stmt->fetchColumn();

                    if ($total_admins_activos <= 1) {
                        $errores[] = 'Debe existir al menos un administrador activo en el sistema.';
                    }
                }

                if (empty($errores)) {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios
                        SET nombre = ?, email = ?, rol = ?, activo = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $nombre_nuevo,
                        $email_nuevo,
                        $rol_nuevo,
                        $activo_nuevo,
                        $usuario_id
                    ]);

                    $exito = 'Información del usuario actualizada correctamente.';
                }
            }
        }
    }
}

$stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.nombre  AS usuario_nombre,
        u.email   AS usuario_email,
        d.nombre  AS destino_nombre,
        d.provincia AS destino_provincia
    FROM reservas r
    INNER JOIN usuarios u ON r.id_usuario = u.id
    INNER JOIN destinos d ON r.id_destino = d.id
    ORDER BY r.creado_en DESC
");
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resumen_estados = [
    'Pendiente de Pago' => 0,
    'Pagado'            => 0,
    'Confirmado'        => 0,
    'Cancelado'         => 0,
];

foreach ($reservas as $r) {
    if (isset($resumen_estados[$r['estado']])) {
        $resumen_estados[$r['estado']]++;
    }
}

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

$buscar_usuario = trim($_GET['buscar_usuario'] ?? '');

$sql_usuarios = "SELECT id, nombre, email, rol, activo, creado_en FROM usuarios";
$params_usuarios = [];

if ($buscar_usuario !== '') {
    $sql_usuarios .= " WHERE nombre LIKE ? OR email LIKE ?";
    $like = '%' . $buscar_usuario . '%';
    $params_usuarios = [$like, $like];
}

$sql_usuarios .= " ORDER BY creado_en DESC";

$stmt_usuarios = $pdo->prepare($sql_usuarios);
$stmt_usuarios->execute($params_usuarios);
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

$resumen_usuarios = [
    'total'     => count($usuarios),
    'activos'   => 0,
    'inactivos' => 0,
    'admins'    => 0,
];

foreach ($usuarios as $u) {
    if ((int)$u['activo'] === 1) {
        $resumen_usuarios['activos']++;
    } else {
        $resumen_usuarios['inactivos']++;
    }

    if ($u['rol'] === 'admin') {
        $resumen_usuarios['admins']++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de administración - HOU Panama Tours</title>
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
                <div class="brand-subtitle">Panel de administración</div>
            </div>
        </div>

        <nav class="nav">
            <a href="index.php">Inicio</a>
            <a href="admin.php">Panel de Adminnistrador</a>
            <span class="user-pill"> 👤 <?php echo htmlspecialchars($usuario['nombre']); ?></span>
            <a href="index.php?logout=1" class="nav-cta">Cerrar sesión</a>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="hero-grid">
        <div>
            <p class="hero-kicker">Gestión de reservas</p>
            <h1 class="hero-title">Panel de administración</h1>
            <p class="hero-subtitle">
                Revisa todas las reservas realizadas en la plataforma, controla sus estados
                y consulta el historial de pagos.
            </p>
        </div>
        <div>
            <div class="card">
                <div class="card-content">
                    <h3 class="card-title">Resumen de estados</h3>
                    <div class="card-stats">
                        <div>
                            <div class="stat-value"><?php echo (int)$resumen_estados['Pendiente de Pago']; ?></div>
                            <div class="stat-label">Pendientes de pago</div>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo (int)$resumen_estados['Pagado']; ?></div>
                            <div class="stat-label">Pagadas</div>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo (int)$resumen_estados['Confirmado']; ?></div>
                            <div class="stat-label">Confirmadas</div>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo (int)$resumen_estados['Cancelado']; ?></div>
                            <div class="stat-label">Canceladas</div>
                        </div>
                    </div>
                    <p class="card-description" style="margin-top:0.7rem;">
                        Total de reservas registradas: <strong><?php echo count($reservas); ?></strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <p class="section-kicker">Reservas</p>
        <h2 class="section-title">Listado completo de reservas</h2>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-error">
            <?php foreach ($errores as $e): ?>
                <div>• <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($exito): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($exito); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($reservas)): ?>
        <p style="color:#fff;margin-top:0.5rem;">
            No hay reservas registradas en el sistema.
        </p>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table table-pago">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Correo</th>
                        <th>Destino</th>
                        <th>Provincia / comarca</th>
                        <th>Fecha tour</th>
                        <th>Personas</th>
                        <th>Total (USD)</th>
                        <th>Estado</th>
                        <th>Creado en</th>
                        <th>Cambiar estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td><?php echo htmlspecialchars($r['codigo_reserva']); ?></td>
                        <td><?php echo htmlspecialchars($r['usuario_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($r['usuario_email']); ?></td>
                        <td><?php echo htmlspecialchars($r['destino_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($r['destino_provincia']); ?></td>
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
                        <td><?php echo htmlspecialchars($r['creado_en']); ?></td>
                        <td>
                            <form method="post" style="display:flex;flex-direction:column;gap:0.3rem;min-width:180px;">
                                <input type="hidden" name="accion" value="cambiar_estado">
                                <input type="hidden" name="reserva_id" value="<?php echo (int)$r['id']; ?>">
                                <select name="nuevo_estado" class="form-input" style="padding:0.25rem 0.4rem;font-size:0.8rem;">
                                    <option value="Pendiente de Pago" <?php echo $r['estado']==='Pendiente de Pago'?'selected':''; ?>>Pendiente de Pago</option>
                                    <option value="Pagado" <?php echo $r['estado']==='Pagado'?'selected':''; ?>>Pagado</option>
                                    <option value="Confirmado" <?php echo $r['estado']==='Confirmado'?'selected':''; ?>>Confirmado</option>
                                    <option value="Cancelado" <?php echo $r['estado']==='Cancelado'?'selected':''; ?>>Cancelado</option>
                                </select>
                                <input
                                    type="text"
                                    name="motivo"
                                    placeholder="Motivo (opcional)"
                                    class="form-input"
                                    style="padding:0.25rem 0.4rem;font-size:0.8rem;"
                                >
                                <button type="submit" class="btn btn-primary" style="padding:0.3rem 0.6rem;font-size:0.8rem;">
                                    Actualizar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ==========================
     Sección de gestión de usuarios
     ========================== -->
<section class="section">
    <div class="section-header">
        <p class="section-kicker">Usuarios</p>
        <h2 class="section-title">Gestión de usuarios registrados</h2>
        <p class="section-description">
            Administra la información de los usuarios, controla sus roles de acceso
            y activa o desactiva cuentas según las necesidades operativas.
        </p>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-content">
            <h3 class="card-title">Resumen de usuarios</h3>
            <div class="card-stats">
                <div>
                    <div class="stat-value"><?php echo (int)$resumen_usuarios['total']; ?></div>
                    <div class="stat-label">Usuarios totales</div>
                </div>
                <div>
                    <div class="stat-value"><?php echo (int)$resumen_usuarios['activos']; ?></div>
                    <div class="stat-label">Cuentas activas</div>
                </div>
                <div>
                    <div class="stat-value"><?php echo (int)$resumen_usuarios['inactivos']; ?></div>
                    <div class="stat-label">Cuentas desactivadas</div>
                </div>
                <div>
                    <div class="stat-value"><?php echo (int)$resumen_usuarios['admins']; ?></div>
                    <div class="stat-label">Administradores</div>
                </div>
            </div>
        </div>
    </div>

    <form method="get" style="margin-bottom:1rem;display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
        <input
            type="text"
            name="buscar_usuario"
            value="<?php echo htmlspecialchars($buscar_usuario); ?>"
            placeholder="Buscar por nombre o correo"
            class="form-input"
            style="max-width:260px;"
        >
        <button type="submit" class="btn btn-secondary">Buscar</button>
        <?php if ($buscar_usuario !== ''): ?>
            <a href="admin.php" class="btn btn-ghost">Limpiar búsqueda</a>
        <?php endif; ?>
    </form>

    <?php if (empty($usuarios)): ?>
        <p style="color:#fff;margin-top:0.5rem;">
            No hay usuarios registrados que coincidan con el criterio de búsqueda.
        </p>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Correo electrónico</th>
                        <th>Rol</th>
                        <th>Estado de cuenta</th>
                        <th>Registrado el</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?php echo (int)$u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['rol']); ?></td>
                        <td><?php echo (int)$u['activo'] === 1 ? 'Activa' : 'Desactivada'; ?></td>
                        <td><?php echo htmlspecialchars($u['creado_en']); ?></td>
                        <td>
                            <form method="post" style="display:flex;flex-direction:column;gap:0.3rem;min-width:220px;">
                                <input type="hidden" name="accion" value="actualizar_usuario">
                                <input type="hidden" name="usuario_id" value="<?php echo (int)$u['id']; ?>">

                                <input
                                    type="text"
                                    name="nombre"
                                    value="<?php echo htmlspecialchars($u['nombre']); ?>"
                                    class="form-input"
                                    style="padding:0.25rem 0.4rem;font-size:0.8rem;"
                                    placeholder="Nombre completo"
                                >
                                <input
                                    type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($u['email']); ?>"
                                    class="form-input"
                                    style="padding:0.25rem 0.4rem;font-size:0.8rem;"
                                    placeholder="Correo electrónico"
                                >

                                <div style="display:flex;gap:0.3rem;">
                                    <select name="rol" class="form-input" style="padding:0.25rem 0.4rem;font-size:0.8rem;">
                                        <option value="usuario" <?php echo $u['rol']==='usuario'?'selected':''; ?>>Usuario</option>
                                        <option value="admin" <?php echo $u['rol']==='admin'?'selected':''; ?>>Administrador</option>
                                    </select>

                                    <select name="activo" class="form-input" style="padding:0.25rem 0.4rem;font-size:0.8rem;">
                                        <option value="1" <?php echo (int)$u['activo']===1?'selected':''; ?>>Activa</option>
                                        <option value="0" <?php echo (int)$u['activo']===0?'selected':''; ?>>Desactivada</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary" style="padding:0.3rem 0.6rem;font-size:0.8rem;">
                                    Guardar cambios
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<footer class="footer">
    <p>© <?php echo date('Y'); ?> HOU Panama Tours. Todos los derechos reservados.</p>
</footer>

</body>
</html>
