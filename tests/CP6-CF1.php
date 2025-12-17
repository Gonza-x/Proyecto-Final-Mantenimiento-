<?php
use PHPUnit\Framework\TestCase;

/**
 * CP6-CF1 – Visualización de Reservas del Usuario sin reservas
 * Objetivo: Comprobar la respuesta del sistema cuando el usuario no tiene reservas.
 */
class VisualizacionReservasUsuarioSinReservasTest extends TestCase
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
     * Test: CP6-CF1 – Usuario sin reservas
     */
    public function testUsuarioSinReservasNoMuestraResultados()
    {
        // Usuario de prueba sin reservas
        $emailUsuario = "antonio.morales@hotmail.com";

        // Buscar reservas del usuario
        $stmt = $this->conexion->prepare("
            SELECT id
            FROM reservas
            WHERE email_usuario = ?
        ");
        $stmt->bind_param("s", $emailUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        // Verificar que NO existan reservas
        $this->assertEquals(
            0,
            $result->num_rows,
            "El usuario no debe tener reservas registradas"
        );
    }
}
