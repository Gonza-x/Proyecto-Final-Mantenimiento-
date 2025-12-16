<?php
use PHPUnit\Framework\TestCase;

/**
 * CP3-CF1 – Gestión de Reservas (Admin)
 * Objetivo: Verificar el comportamiento al buscar un código
 * de reserva inexistente.
 */
class GestionReservasAdminInexistenteTest extends TestCase
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
     * Test: Búsqueda de reserva inexistente
     */
    public function testBusquedaReservaInexistente()
    {
        // Dato de prueba
        $codigoReserva = "RES-9999";

        // Buscar reserva por código
        $stmt = $this->conexion->prepare(
            "SELECT * FROM reservas WHERE codigo_reserva = ?"
        );
        $stmt->bind_param("s", $codigoReserva);
        $stmt->execute();
        $result = $stmt->get_result();

        // Verificar que no se encuentren registros
        $this->assertEquals(
            0,
            $result->num_rows,
            "No deben existir reservas con el código ingresado"
        );

        // Línea mínima para asegurar ejecución del test
        $this->assertTrue(true);
    }
}
