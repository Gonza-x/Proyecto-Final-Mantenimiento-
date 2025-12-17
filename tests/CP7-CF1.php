<?php
use PHPUnit\Framework\TestCase;

/**
 * CP7-CF1 – Transición de estado no permitida (Admin)
 * Objetivo: Verificar que el sistema bloquee el cambio de estado
 * de "Cancelado" a "Confirmado".
 */
class GestionEstadosReservaTransicionInvalidaTest extends TestCase
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
     * Test: CP7-CF1 – Bloqueo de transición no válida
     */
    public function testNoPermitirCambioDeCanceladoAConfirmado()
    {
        $estadoInicial = "Cancelado";
        $estadoNoPermitido = "Confirmado";

        // Buscar reserva en estado Cancelado
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
            "Debe existir al menos una reserva en estado Cancelado"
        );

        $reserva = $resultado->fetch_assoc();
        $reservaId = $reserva['id'];

        // Intentar cambio de estado NO permitido
        $update = $this->conexion->prepare("
            UPDATE reservas
            SET estado = ?
            WHERE id = ?
        ");
        $update->bind_param("si", $estadoNoPermitido, $reservaId);
        $resultadoUpdate = $update->execute();

        // Forzar validación de regla de negocio (simulada)
        $this->assertFalse(
            $resultadoUpdate && $estadoInicial === "Cancelado",
            "El sistema debe bloquear la transición de Cancelado a Confirmado"
        );

        // Verificar que el estado se mantiene como Cancelado
        $verificar = $this->conexion->prepare("
            SELECT estado
            FROM reservas
            WHERE id = ?
        ");
        $verificar->bind_param("i", $reservaId);
        $verificar->execute();
        $estadoActual = $verificar->get_result()->fetch_assoc();

        $this->assertEquals(
            $estadoInicial,
            $estadoActual['estado'],
            "El estado de la reserva debe mantenerse como Cancelado"
        );
    }
}
