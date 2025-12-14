<?php
use PHPUnit\Framework\TestCase;

/**
 * CP-EP-01 – Exploración exitosa de provincias
 * Objetivo: Verificar que el usuario pueda visualizar y acceder 
 * correctamente a la información de una provincia.
 */
class ProvinciasTest extends TestCase
{
    private $conexion;

    protected function setUp(): void
    {
        // Conectar a la base de datos
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
     * Test: CP-EP-01 - Exploración exitosa de provincias
     * Paso 1-3: Verificar que se pueden cargar las provincias
     */
    public function testProvinciasDisponiblesEnBaseDatos()
    {
        // Verificar que la tabla provincias existe y tiene datos
        $result = $this->conexion->query("SELECT COUNT(*) as total FROM provincias");
        $row = $result->fetch_assoc();
        
        $this->assertGreaterThan(0, $row['total'], 
            "Debe haber al menos una provincia en la base de datos");
    }

    /**
     * Test: CP-EP-01 - Paso 4-5
     * Verificar que se puede obtener información de una provincia específica
     * Datos de prueba: "Bocas del Toro"
     */
    public function testObtenerInformacionProvinciaEspecifica()
    {
        // Buscar provincia "Bocas del Toro"
        $stmt = $this->conexion->prepare("SELECT * FROM provincias WHERE nombre LIKE ?");
        $search = "%Bocas%";
        $stmt->bind_param("s", $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->assertGreaterThan(0, $result->num_rows, 
            "Debe existir la provincia Bocas del Toro");
        
        $provincia = $result->fetch_assoc();
        
        // Verificar que tiene los campos necesarios
        $this->assertArrayHasKey('id', $provincia);
        $this->assertArrayHasKey('nombre', $provincia);
        $this->assertNotEmpty($provincia['nombre']);
    }

    /**
     * Test: Verificar que el archivo provincia.php existe
     * Esto simula el paso de acceder a la página de provincia
     */
    public function testArchivoPaginaProvinciaExiste()
    {
        $this->assertFileExists(__DIR__ . '/../provincia.php',
            "El archivo provincia.php debe existir para mostrar detalles");
    }

    /**
     * Test: Verificar que hay tours asociados a provincias
     */
    public function testProvinciasConToursDisponibles()
    {
        // Verificar que existen tours asociados a provincias
        $query = "SELECT p.nombre, COUNT(t.id) as total_tours 
                  FROM provincias p 
                  LEFT JOIN tours t ON t.provincia_id = p.id 
                  GROUP BY p.id 
                  HAVING total_tours > 0";
        
        $result = $this->conexion->query($query);
        
        $this->assertGreaterThan(0, $result->num_rows,
            "Debe haber al menos una provincia con tours disponibles");
    }

    /**
     * Test: Verificar estructura de datos de provincia
     */
    public function testEstructuraDatosProvinciaCompleta()
    {
        $result = $this->conexion->query("SELECT * FROM provincias LIMIT 1");
        
        if ($result->num_rows > 0) {
            $provincia = $result->fetch_assoc();
            
            // Verificar campos mínimos esperados
            $this->assertArrayHasKey('id', $provincia, 
                "Provincia debe tener campo 'id'");
            $this->assertArrayHasKey('nombre', $provincia,
                "Provincia debe tener campo 'nombre'");
            
            // Verificar que los datos no están vacíos
            $this->assertNotEmpty($provincia['id']);
            $this->assertNotEmpty($provincia['nombre']);
        }
    }
}