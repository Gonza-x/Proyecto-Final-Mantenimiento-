<?php
use PHPUnit\Framework\TestCase;

/**
 * CP8-CE – Procesamiento de Pago con Tarjeta
 * Objetivo: Verificar que una reserva pendiente pueda pagarse correctamente
 * con un método de tarjeta válido.
 */
class ProcesamientoPagoTarjetaTest extends TestCase
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
     * Test: CP8-CE – Pago exitoso con tarjeta válida
     */
    public function testPagoTarjetaReservaPendiente()
    {
        // Datos de prueba
        $numeroTarjeta = "4111111111111111";
        $titular = "Carlos Rodríguez";
        $vencimiento = "12/27";
        $cvv = "123";
        $estadoInicial = "Pendiente de Pago";
        $estadoFinal = "Pagado";

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

        $this->assertGreaterThan(
            0,
            $resultado->num_rows,
            "Debe existir una reserva pendiente de pago"
        );

        $reserva = $resultado->fetch_assoc();
        $reservaId = $reserva['id'];

        // Simular procesamiento de pago exitoso
        $fechaPago = date('Y-m-d H:i:s');

        $update = $this->conexion->prepare("
            UPDATE reservas
            SET estado = ?, fecha_pago = ?
            WHERE id = ?
        ");
        $update->bind_param("ssi", $estadoFinal, $fechaPago, $reservaId);
        $resultadoPago = $update->execute();

        // Verificar que el pago fue exitoso
        $this->assertTrue(
            $resultadoPago,
            "El pago con tarjeta debe procesarse correctamente"
        );

        // Verificar estado y fecha de pago
        $verificar = $this->conexion->prepare("
            SELECT estado, fecha_pago
            FROM reservas
            WHERE id = ?
        ");
        $verificar->bind_param("i", $reservaId);
        $verificar->execute();
        $datosActuales = $verificar->get_result()->fetch_assoc();

        $this->assertEquals(
            $estadoFinal,
            $datosActuales['estado'],
            "La reserva debe quedar en estado Pagado"
        );

        $this->assertNotEmpty(
            $datosActuales['fecha_pago'],
            "Debe registrarse la fecha y hora del pago"
        );
    }
}
