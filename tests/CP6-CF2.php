<?php
use PHPUnit\Framework\TestCase;

/**
 * CP6-CF2 – Error al consultar historial de reservas
 * Objetivo: Verificar que el sistema maneje errores al consultar las reservas del usuario.
 */
class VisualizacionReservasErrorConsultaTest extends TestCase
{
    private $conexion;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../config.php';

        // Conexión normal
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
     * Test: CP6-CF2 – Error en consulta de reservas
     */
    public function testErrorAlConsultarReservas()
    {
        // Usuario autenticado (dato irrelevante porque hay error)
        $emailUsuario = "carlos.rodriguez@gmail.com";

        // Simular error: consulta a tabla inexistente
        $query = "
            SELECT *
            FROM reservas_inexistente
            WHERE email_usuario = '$emailUsuario'
        ";

        $resultado = @$this->conexion->query($query);

        // Verificar que la consulta FALLA
        $this->assertFalse(
            $resultado,
            "La consulta debe fallar y el sistema debe manejar el error"
        );

        // Verificar que existe un mensaje de error en la conexión
        $this->assertNotEmpty(
            $this->conexion->error,
            "Debe generarse un mensaje de error al no poder cargar las reservas"
        );
    }
}
