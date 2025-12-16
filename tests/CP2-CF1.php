<?php
use PHPUnit\Framework\TestCase;

/**
 * CP2-CF1 - Creación de Reserva (fecha no disponible)
 */
class CreacionReservaNoDisponibleTest extends TestCase
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
     * Test: Verificar que no se pueda crear una reserva en una fecha no disponible
     */
    public function testReservaFechaNoDisponible()
    {
        // Datos de prueba
        $usuario = "carlos.rodriguez@gmail.com"; // usuario autenticado
        $destino = "Parque Nacional Coiba";
        $fecha = "2024-01-01"; // fecha no disponible
        $adultos = 2;
        $ninos = 1;
        $jubilados = 0;

        // Supongamos que hay una función crearReserva($usuario, $destino, $fecha, $adultos, $ninos, $jubilados)
        // que retorna false si la fecha no está disponible, o array con detalles si se creó la reserva.
        $resultado = crearReserva($usuario, $destino, $fecha, $adultos, $ninos, $jubilados);

        // Comprobaciones
        $this->assertFalse($resultado, "La reserva no debe crearse en fecha no disponible");
    }
}
