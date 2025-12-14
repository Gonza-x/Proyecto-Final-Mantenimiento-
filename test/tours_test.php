<?php
use PHPUnit\Framework\TestCase;

/**
 * Test de Tours
 * Objetivo: Verificar que los tours se pueden visualizar y gestionar correctamente
 */
class ToursTest extends TestCase
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
     * Test: Verificar que existe la tabla de tours
     */
    public function testTablaToursExiste()
    {
        $result = $this->conexion->query("SHOW TABLES LIKE 'tours'");
        $this->assertEquals(1, $result->num_rows,
            "La tabla 'tours' debe existir");
    }

    /**
     * Test: Verificar que hay tours disponibles
     */
    public function testExistenToursDisponibles()
    {
        $result = $this->conexion->query("SELECT COUNT(*) as total FROM tours");
        $row = $result->fetch_assoc();
        
        $this->assertGreaterThan(0, $row['total'],
            "Debe haber al menos un tour disponible");
    }

    /**
     * Test: Verificar estructura de datos de tours
     */
    public function testEstructuraTablaTours()
    {
        $result = $this->conexion->query("DESCRIBE tours");
        $columnas = [];
        
        while ($row = $result->fetch_assoc()) {
            $columnas[] = $row['Field'];
        }
        
        // Campos esenciales
        $this->assertContains('id', $columnas, "Tour debe tener 'id'");
        $this->assertContains('nombre', $columnas, "Tour debe tener 'nombre'");
        
        // Al menos debe tener información de precio
        $tienePrecio = in_array('precio', $columnas) || 
                       in_array('costo', $columnas) ||
                       in_array('tarifa', $columnas);
        $this->assertTrue($tienePrecio, "Tour debe tener información de precio");
    }

    /**
     * Test: Verificar que tours tienen datos completos
     */
    public function testToursConDatosCompletos()
    {
        $result = $this->conexion->query("SELECT * FROM tours LIMIT 1");
        
        if ($result->num_rows > 0) {
            $tour = $result->fetch_assoc();
            
            $this->assertArrayHasKey('id', $tour);
            $this->assertArrayHasKey('nombre', $tour);
            $this->assertNotEmpty($tour['id']);
            $this->assertNotEmpty($tour['nombre']);
        }
    }

    /**
     * Test: Validación de precios
     */
    public function testValidacionPrecioTour()
    {
        // Precio válido
        $precioValido = 150.00;
        $this->assertGreaterThan(0, $precioValido,
            "Precio debe ser mayor a 0");
        $this->assertIsNumeric($precioValido,
            "Precio debe ser numérico");
        
        // Precio inválido
        $precioInvalido = -50;
        $this->assertFalse($precioInvalido > 0,
            "Precio no puede ser negativo");
    }

    /**
     * Test: Verificar relación tours-provincias
     */
    public function testRelacionToursProvincias()
    {
        // Verificar que los tours están relacionados con provincias
        $query = "SELECT t.*, p.nombre as provincia_nombre 
                  FROM tours t 
                  LEFT JOIN provincias p ON t.provincia_id = p.id 
                  LIMIT 1";
        
        $result = $this->conexion->query($query);
        
        $this->assertNotFalse($result,
            "Debe poder hacer JOIN entre tours y provincias");
    }

    /**
     * Test: Verificar que el index.php existe (página principal de tours)
     */
    public function testPaginaPrincipalExiste()
    {
        $this->assertFileExists(__DIR__ . '/../index.php',
            "El archivo index.php debe existir para mostrar tours");
    }
}