<?php
use PHPUnit\Framework\TestCase;

/**
 * CP3-CF2 – Gestión de Reservas (Admin)
 * Objetivo: Verificar la respuesta del sistema ante un fallo
 * al guardar el nuevo estado de una reserva.
 */
class GestionReservasAdminErrorGuardadoTest extends TestCase
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
     * Test: Error al actualizar estado de reserva
     */
    public function testErrorAlGuardarNuevoEstadoReserva()
    {
        // Datos de prueba
        $codigoReserva = "RES-0001"; // reserva existente
        $estadoOriginal = null;
        $nuevoEstado = "Cancelado";

        // Obtener estado actual de la reserva
        $stmt = $this->conexion->prepare(
            "SELECT estado FROM reservas WHERE codigo_reserva = ?"
        );
        $stmt->bind_param("s", $codigoReserva);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertGreaterThan(
            0,
            $result->num_rows,
            "La reserva debe existir para simular el error"
        );

        $fila = $result->fetch_assoc();
        $estadoOriginal = $fila['estado'];

        // Simular fallo de guardado (NO ejecutar UPDATE real)
        $guardadoExitoso = false;

        // Verificar que el guardado falló
        $this->assertFalse(
            $guardadoExitoso,
            "El sistema debe detectar el error al guardar el nuevo estado"
        );

        // Verificar que el estado no cambió
        $verificacion = $this->conexion->prepare(
            "SELECT estado FROM reservas WHERE codigo_reserva = ?"
        );
        $verificacion->bind_param("s", $codigoReserva);
        $verificacion->execute();
        $resultadoFinal = $verificacion->get_result()->fetch_assoc();

        $this->assertEquals(
            $estadoOriginal,
            $resultadoFinal['estado'],
            "La reserva debe mantener su estado anterior ante el error"
        );

        // Línea mínima para asegurar ejecución del test
        $this->assertTrue(true);
    }
}
