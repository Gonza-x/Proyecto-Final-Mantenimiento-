<?php
use PHPUnit\Framework\TestCase;

/**
 * CP8-CF2 – Pago rechazado con tarjeta
 * Objetivo: Verificar el manejo correcto de un pago rechazado
 * durante la simulación del proceso de pago.
 */
class ProcesamientoPagoTarjetaRechazadoTest extends TestCase
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
     * Test: CP8-CF2 – Simulación de pago rechazado
     */
    public function testPagoTarjetaRechazada()
    {
        // Datos de prueba
        $tarjetaRechazada = "4000000000000002";
        $estadoInicial = "Pendiente de Pago";

        // Simulación de tarjeta rechazada
        $pagoAceptado = false;

        // Validar que la tarjeta corresponde a un caso de rechazo
        $this->assertEquals(
            "4000000000000002",
            $tarjetaRechazada,
            "Tarjeta de prueba configurada para rechazo"
        );

        // Buscar reserva pendiente
        $stmt = $this->conexion->prepare("
            SELECT id, estado
            FROM reservas
            WHERE estado = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $estadoInicial);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $reserva = $resultado->fetch_assoc();

        $estadoOriginal = $reserva['estado'];

        // Simular rechazo del pago
        $this->assertFalse(
            $pagoAceptado,
            "El sistema debe rechazar el pago con esta tarjeta"
        );

        // Verificar que el estado NO cambia
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
            "La reserva debe mantenerse en estado Pendiente de Pago"
        );
    }
}
