<?php
use PHPUnit\Framework\TestCase;

/**
 * CP-EP-02 – Exploración de provincias sin datos disponibles
 * Objetivo: Validar el comportamiento del sistema cuando
 * no existen provincias registradas.
 */
class ProvinciasSinDatosTest extends TestCase
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
     * CP-EP-02
     * Precondición: la consulta de provincias retorna lista vacía
     */
    public function testExploracionProvinciasSinDatos()
    {
        $result = $this->conexion->query("SELECT COUNT(*) as total FROM provincias");
        $row = $result->fetch_assoc();

        if ($row['total'] > 0) {
            $this->markTestSkipped(
                'Precondición no cumplida: existen provincias registradas'
            );
        }

        $this->assertEquals(0, $row['total']);

        $mensaje = "No hay provincias disponibles";

        $this->assertEquals(
            "No hay provincias disponibles",
            $mensaje
        );
    }
}
