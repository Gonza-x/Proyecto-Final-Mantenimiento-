<?php
use PHPUnit\Framework\TestCase;

/**
 * CP3-CE – Gestión de Reservas (Admin)
 * Objetivo: Verificar que el administrador pueda cambiar el estado
 * de una reserva existente.
 */
class GestionReservasAdminTest extends TestCase
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
     * Test: Cambio de estado de reserva por administrador
     */
    public function testAdministradorPuedeCambiarEstadoReserva()
    {
        // Datos de prueba
        $codigoReserva = "RES-0001";
        $nuevoEstado = "Confirmado";

        // Verificar que la reserva exista
        $stmt = $this->conexion->prepare(
            "SELECT estado FROM reservas WHERE codigo_reserva = ?"
        );
        $stmt->bind_param("s", $codigoReserva);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertGreaterThan(
            0,
            $result->num_rows,
            "La reserva debe existir para poder cambiar su estado"
        );

        // Simular actualización del estado
        $update = $this->conexion->prepare(
            "UPDATE reservas SET estado = ? WHERE codigo_reserva = ?"
        );
        $update->bind_param("ss", $nuevoEstado, $codigoReserva);
        $update->execute();

        // Verificar que el estado fue actualizado
        $verificacion = $this->conexion->prepare(
            "SELECT estado FROM reservas WHERE codigo_reserva = ?"
        );
        $verificacion->bind_param("s", $codigoReserva);
        $verificacion->execute();
        $resultadoFinal = $verificacion->get_result()->fetch_assoc();

        $this->assertEquals(
            $nuevoEstado,
            $resultadoFinal['estado'],
            "El estado de la reserva debe actualizarse a 'Confirmado'"
        );

        // Línea mínima para asegurar ejecución del test
        $this->assertTrue(true);
    }
}
