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

    public function testProvinciasDisponiblesEnBaseDatos()
    {
        $result = $this->conexion->query("SELECT COUNT(*) as total FROM provincias");
        $row = $result->fetch_assoc();
        
        $this->assertGreaterThan(0, $row['total'], 
            "Debe haber al menos una provincia en la base de datos");

        // Línea mínima para que PHPUnit ejecute el test
        $this->assertTrue(true);
    }

    public function testObtenerInformacionProvinciaEspecifica()
    {
        $stmt = $this->conexion->prepare("SELECT * FROM provincias WHERE nombre LIKE ?");
        $search = "%Bocas%";
        $stmt->bind_param("s", $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->assertGreaterThan(0, $result->num_rows, 
            "Debe existir la provincia Bocas del Toro");
        
        $provincia = $result->fetch_assoc();
        $this->assertArrayHasKey('id', $provincia);
        $this->assertArrayHasKey('nombre', $provincia);
        $this->assertNotEmpty($provincia['nombre']);

        $this->assertTrue(true);
    }

    public function testArchivoPaginaProvinciaExiste()
    {
        $this->assertFileExists(__DIR__ . '/../provincia.php',
            "El archivo provincia.php debe existir para mostrar detalles");

        $this->assertTrue(true);
    }

    public function testProvinciasConToursDisponibles()
    {
        $query = "SELECT p.nombre, COUNT(t.id) as total_tours 
                  FROM provincias p 
                  LEFT JOIN tours t ON t.provincia_id = p.id 
                  GROUP BY p.id 
                  HAVING total_tours > 0";
        
        $result = $this->conexion->query($query);
        $this->assertGreaterThan(0, $result->num_rows,
            "Debe haber al menos una provincia con tours disponibles");

        $this->assertTrue(true);
    }

    public function testEstructuraDatosProvinciaCompleta()
    {
        $result = $this->conexion->query("SELECT * FROM provincias LIMIT 1");
        
        if ($result->num_rows > 0) {
            $provincia = $result->fetch_assoc();
            
            $this->assertArrayHasKey('id', $provincia, 
                "Provincia debe tener campo 'id'");
            $this->assertArrayHasKey('nombre', $provincia,
                "Provincia debe tener campo 'nombre'");
            
            $this->assertNotEmpty($provincia['id']);
            $this->assertNotEmpty($provincia['nombre']);
        }

        $this->assertTrue(true);
    }
}
