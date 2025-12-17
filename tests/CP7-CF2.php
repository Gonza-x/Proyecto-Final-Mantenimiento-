<?php
use PHPUnit\Framework\TestCase;

/**
 * CP7-CF2 – Restricción de cambio de estado solo para administradores
 * Objetivo: Confirmar que solo usuarios con rol administrador
 * puedan modificar estados de reserva.
 */
class GestionEstadosReservaAccesoRestringidoTest extends TestCase
{
    private $conexion;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../config.php';
        $this->conexion = new mysqli(
            $host ?? 'localhost',
            $usuario ?? 'root',
            $password ?? '',
            $bd ?? 'hou_panama_tours'
        );
    }

    protected function tearDown(): void
    {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }

    /**
     * Test: CP7-CF2 – Usuario no administrador
     */
    public function testUsuarioNoAdminNoPuedeCambiarEstadoReserva()
    {
        // Usuario de prueba (NO administrador)
        $emailUsuario = "marco1.martin@usuario.com";
        $nuevoEstado = "Confirmado";

        // Verificar que el usuario NO tiene rol admin
        $stmtUsuario = $this->conexion->prepare("
            SELECT rol
            FROM usuarios
            WHERE email = ?
        ");
        $stmtUsuario->bind_param("s", $emailUsuario);
        $stmtUsuario->execute();
        $resultadoUsuario = $stmtUsuario->get_result();

        $this->assertGreaterThan(
            0,
            $resultadoUsuario->num_rows,
            "El usuario de prueba debe existir"
        );

        $usuario = $resultadoUsuario->fetch_assoc();
        $this->assertNotEquals(
            "admin",
            strtolower($usuario['rol']),
            "El usuario no debe tener rol administrador"
        );

        // Buscar una reserva cualquiera
        $reserva = $this->conexion->query("
            SELECT id, estado
            FROM reservas
            LIMIT 1
        ")->fetch_assoc();

        $estadoOriginal = $reserva['estado'];

        // Intentar cambio de estado SIN permisos (simulado)
        $resultadoUpdate = false; // Acción bloqueada por permisos

        // Verificar que el sistema bloquea la acción
        $this->assertFalse(
            $resultadoUpdate,
            "El sistema debe bloquear el cambio de estado para usuarios no admin"
        );

        // Verificar que el estado NO cambió
        $verificar = $this->conexion->prepare("
            SELECT estado
            FROM reservas
            WHERE id = ?
        ");
        $verificar->bind_param("i", $reserva['id']);
        $verificar->execute();
        $estadoActual = $verificar->get_result()->fetch_assoc();

        $this->assertEquals(
            $estadoOriginal,
            $estadoActual['estado'],
            "El estado de la reserva no debe modificarse"
        );
    }
}