<?php
use PHPUnit\Framework\TestCase;

/**
 * CP6-CE – Visualización de Reservas del Usuario
 * Objetivo: Verificar que un usuario autenticado pueda ver todas sus reservas.
 */
class VisualizacionReservasUsuarioTest extends TestCase
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
     * Test: CP6-CE – Ver reservas de un usuario autenticado
     */
    public function testUsuarioPuedeVerSusReservas()
    {
        // Datos de prueba
        $emailUsuario = "carlos.rodriguez@gmail.com";

        // Obtener reservas del usuario
        $stmt = $this->conexion->prepare("
            SELECT codigo_reserva, destino, fecha, estado, precio_total
            FROM reservas
            WHERE email_usuario = ?
        ");
        $stmt->bind_param("s", $emailUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        // Verificar que el usuario tenga reservas
        $this->assertGreaterThan(
            0,
            $result->num_rows,
            "El usuario debe tener al menos una reserva registrada"
        );

        // Verificar estructura de cada reserva
        $reserva = $result->fetch_assoc();

        $this->assertArrayHasKey('codigo_reserva', $reserva);
        $this->assertArrayHasKey('destino', $reserva);
        $this->assertArrayHasKey('fecha', $reserva);
        $this->assertArrayHasKey('estado', $reserva);
        $this->assertArrayHasKey('precio_total', $reserva);
    }
}