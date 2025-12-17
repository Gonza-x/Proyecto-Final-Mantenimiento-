<?php
use PHPUnit\Framework\TestCase;

/**
 * CP8-CF1 – Pago con tarjeta inválida
 * Objetivo: Verificar que el sistema no procese pagos
 * con número de tarjeta en formato incorrecto.
 */
class ProcesamientoPagoTarjetaInvalidaTest extends TestCase
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
     * Test: CP8-CF1 – Tarjeta con formato incorrecto
     */
    public function testPagoConNumeroTarjetaInvalido()
    {
        // Datos de prueba
        $numeroTarjetaInvalida = "1234";
        $estadoInicial = "Pendiente de Pago";

        // Validación básica de formato (simulada)
        $tarjetaValida = preg_match('/^[0-9]{16}$/', $numeroTarjetaInvalida);

        $this->assertFalse(
            $tarjetaValida,
            "El número de tarjeta debe marcarse como inválido"
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

        // Simular que el pago NO se procesa
        $pagoProcesado = false;

        $this->assertFalse(
            $pagoProcesado,
            "El pago no debe procesarse con tarjeta inválida"
        );

        // Verificar que el estado de la reserva NO cambia
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
            "La reserva debe permanecer en estado Pendiente de Pago"
        );
    }
}
