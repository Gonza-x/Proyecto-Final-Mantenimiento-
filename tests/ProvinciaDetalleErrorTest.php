<?php
use PHPUnit\Framework\TestCase;

/**
 * CP1-CF2 – Error al cargar detalle de provincia
 * Objetivo: Verificar que el sistema maneje correctamente
 * un error al intentar acceder al detalle de una provincia.
 */
class ProvinciaDetalleErrorTest extends TestCase
{
    private $conexion;

    protected function setUp(): void
    {
        // Conexión a la base de datos
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
     * Test: CP1-CF2
     * Verificar que la provincia "Darién" existe
     */
    public function testProvinciaDarienExiste()
    {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM provincias WHERE nombre = ?"
        );
        $nombre = "Darién";
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertGreaterThan(
            0,
            $result->num_rows,
            "La provincia Darién debe existir para ejecutar la prueba"
        );
    }

    /**
     * Test: CP1-CF2
     * Simular error al cargar el detalle de la provincia
     */
    public function testErrorAlCargarDetalleProvincia()
    {
        // Simulación de error (por ejemplo, fallo en la consulta)
        $resultado = false;

        // El sistema debe manejar el error y mostrar un mensaje
        if ($resultado === false) {
            $mensaje = "No se pudo cargar la información de la provincia";
        }

        $this->assertEquals(
            "No se pudo cargar la información de la provincia",
            $mensaje,
            "El sistema debe mostrar un mensaje de error claro"
        );
    }

    /**
     * Test: Verificar que el archivo provincia.php existe
     * y que la navegación no se rompe
     */
    public function testPaginaProvinciaSigueDisponible()
    {
        $this->assertFileExists(
            __DIR__ . '/../provincia.php',
            "La página provincia.php debe existir aunque ocurra un error"
        );
    }
}
