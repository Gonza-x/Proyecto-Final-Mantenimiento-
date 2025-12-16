<?php
use PHPUnit\Framework\TestCase;

/**
 * CP1-CR - Creación de Reserva
 */
class CreacionReservaTest extends TestCase
{
    private $conexion;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../config.php';
        $this->conexion = new mysqli($host ?? 'localhost', $usuario ?? 'root', $password ?? '', $bd ?? 'hou_panama_tours');
    }

    protected function tearDown(): void
    {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }

    /**
     * Test: Verificar que se cree correctamente una reserva con distribución mixta válida
     */
    public function testCrearReservaMixta()
    {
        // Datos de prueba
        $usuario = "carlos.rodriguez@gmail.com";
        $destino = "Parque Nacional Coiba";
        $fecha = "2025-12-15";
        $adultos = 2;
        $ninos = 1;
        $jubilados = 1;

        // Supongamos que hay una función crearReserva($usuario, $destino, $fecha, $adultos, $ninos, $jubilados)
        // que devuelve un array con 'codigo', 'estado' y 'costo_total'.
        $resultado = crearReserva($usuario, $destino, $fecha, $adultos, $ninos, $jubilados);

        // Comprobaciones básicas
        $this->assertArrayHasKey('codigo', $resultado, "Debe generarse un código único de reserva");
        $this->assertArrayHasKey('estado', $resultado, "Debe existir el estado de la reserva");
        $this->assertEquals('Pendiente de Pago', $resultado['estado'], "El estado debe ser 'Pendiente de Pago'");
        $this->assertArrayHasKey('costo_total', $resultado, "Debe calcularse el costo total");
        $this->assertGreaterThan(0, $resultado['costo_total'], "El costo total debe ser mayor a 0");
    }
}
