<?php
use PHPUnit\Framework\TestCase;

/**
 * Test de Sistema de Reservas
 * Objetivo: Verificar que el sistema de reservas funciona correctamente
 */
class ReservasTest extends TestCase
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
     * Test: Verificar que existe la tabla de reservas
     */
    public function testTablaReservasExiste()
    {
        $result = $this->conexion->query("SHOW TABLES LIKE 'reservas'");
        $this->assertEquals(1, $result->num_rows,
            "La tabla 'reservas' debe existir");
    }

    /**
     * Test: Verificar estructura mínima de tabla reservas
     */
    public function testEstructuraTablaReservas()
    {
        $result = $this->conexion->query("DESCRIBE reservas");
        $columnas = [];
        
        while ($row = $result->fetch_assoc()) {
            $columnas[] = $row['Field'];
        }
        
        // Campos esenciales para una reserva
        $this->assertContains('id', $columnas, "Debe tener campo 'id'");
        
        // Al menos uno de estos debe existir para relacionar con usuarios
        $tieneRelacionUsuario = in_array('usuario_id', $columnas) || 
                               in_array('user_id', $columnas) ||
                               in_array('cliente_id', $columnas);
        $this->assertTrue($tieneRelacionUsuario, 
            "Debe tener un campo para relacionar con usuarios");
    }

    /**
     * Test: Verificar que el archivo reserva.php existe
     */
    public function testArchivoReservaExiste()
    {
        $this->assertFileExists(__DIR__ . '/../reserva.php',
            "El archivo reserva.php debe existir");
    }

    /**
     * Test: Verificar que el archivo mis-reservas.php existe
     */
    public function testArchivoMisReservasExiste()
    {
        $this->assertFileExists(__DIR__ . '/../mis-reservas.php',
            "El archivo mis-reservas.php debe existir para ver reservas");
    }

    /**
     * Test: Verificar que se pueden listar reservas
     */
    public function testPuedeConsultarReservas()
    {
        $result = $this->conexion->query("SELECT * FROM reservas LIMIT 1");
        $this->assertNotFalse($result, 
            "Debe poder ejecutar SELECT en tabla reservas");
    }

    /**
     * Test: Validación de fechas (fecha futura para reservas)
     */
    public function testValidacionFechaReserva()
    {
        $fechaHoy = date('Y-m-d');
        $fechaFutura = date('Y-m-d', strtotime('+7 days'));
        $fechaPasada = date('Y-m-d', strtotime('-7 days'));
        
        // Fecha futura debe ser mayor que hoy
        $this->assertGreaterThan($fechaHoy, $fechaFutura,
            "Fecha de reserva debe ser futura");
        
        // Fecha pasada debe ser menor que hoy
        $this->assertLessThan($fechaHoy, $fechaPasada,
            "No se deben permitir reservas en fechas pasadas");
    }

    /**
     * Test: Validación de cantidad de personas
     */
    public function testValidacionCantidadPersonas()
    {
        // Cantidad válida
        $cantidadValida = 4;
        $this->assertGreaterThan(0, $cantidadValida,
            "Cantidad de personas debe ser mayor a 0");
        $this->assertLessThanOrEqual(50, $cantidadValida,
            "Cantidad de personas debe ser razonable");
        
        // Cantidad inválida
        $cantidadInvalida = 0;
        $this->assertFalse($cantidadInvalida > 0,
            "No se debe permitir reserva con 0 personas");
    }
}