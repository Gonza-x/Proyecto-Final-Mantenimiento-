<?php
use PHPUnit\Framework\TestCase;

/**
 * CP7-CE – Gestión de Estados de Reserva (Admin)
 * Objetivo: Verificar que el administrador pueda cambiar el estado
 * de una reserva siguiendo las reglas de negocio.
 */
class GestionEstadosReservaAdminTest extends TestCase
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
     * Test: CP7-CE – Cambio de estado válido
     */
    public function testCambioEstadoPendienteAPagado()
    {
        // Datos de prueba
        $estadoInicial = "Pendiente de Pago";
        $nuevoEstado = "Pagado";

        // Buscar una reserva en estado "Pendiente de Pago"
        $stmt = $this->conexion->prepare("
            SELECT id, estado
            FROM reservas
            WHERE estado = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $estadoInicial);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $this->assertGreaterThan(
            0,
            $resultado->num_rows,
            "Debe existir al menos una reserva en estado Pendiente de Pago"
        );

        $reserva = $resultado->fetch_assoc();
        $reservaId = $reserva['id'];

        // Actualizar el estado a "Pagado"
        $update = $this->conexion->prepare("
            UPDATE reservas
            SET estado = ?
            WHERE id = ?
        ");
        $update->bind_param("si", $nuevoEstado, $reservaId);
        $resultadoUpdate = $update->execute();

        // Verificar que la actualización fue exitosa
        $this->assertTrue(
            $resultadoUpdate,
            "El sistema debe permitir el cambio de estado a Pagado"
        );

        // Verificar que el estado fue actualizado correctamente
        $verificar = $this->conexion->prepare("
            SELECT estado
            FROM reservas
            WHERE id = ?
        ");
        $verificar->bind_param("i", $reservaId);
        $verificar->execute();
        $estadoActual = $verificar->get_result()->fetch_assoc();

        $this->assertEquals(
            $nuevoEstado,
            $estadoActual['estado'],
            "El estado de la reserva debe cambiar a Pagado"
        );
    }
}

