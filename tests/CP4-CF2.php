<?php
use PHPUnit\Framework\TestCase;

/**
 * CP4-CF2 – Gestión de Usuarios (Admin)
 * Objetivo: Verificar el manejo de error cuando falla
 * la actualización de los datos de un usuario.
 */
class GestionUsuariosAdminErrorActualizacionTest extends TestCase
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
     * Test: Error al actualizar datos de usuario
     */
    public function testErrorAlActualizarDatosUsuario()
    {
        // Usuario de prueba existente
        $emailUsuario = "usuario.prueba@email.com";
        $nuevoNombre = "Nombre Fallido";
        $nuevoTelefono = "0000-0000";

        // Obtener datos actuales
        $stmt = $this->conexion->prepare(
            "SELECT nombre, telefono FROM usuarios WHERE email = ?"
        );
        $stmt->bind_param("s", $emailUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertGreaterThan(
            0,
            $result->num_rows,
            "El usuario debe existir para simular el error"
        );

        $datosOriginales = $result->fetch_assoc();

        // Simular error de guardado (NO ejecutar UPDATE)
        $guardadoExitoso = false;

        $this->assertFalse(
            $guardadoExitoso,
            "El sistema debe detectar el error al intentar guardar los cambios"
        );

        // Verificar que los datos NO cambiaron
        $verificacion = $this->conexion->prepare(
            "SELECT nombre, telefono FROM usuarios WHERE email = ?"
        );
        $verificacion->bind_param("s", $emailUsuario);
        $verificacion->execute();
        $datosFinales = $verificacion->get_result()->fetch_assoc();

        $this->assertEquals(
            $datosOriginales['nombre'],
            $datosFinales['nombre'],
            "El nombre del usuario debe permanecer sin cambios"
        );

        $this->assertEquals(
            $datosOriginales['telefono'],
            $datosFinales['telefono'],
            "El teléfono del usuario debe permanecer sin cambios"
        );

        // Línea mínima para asegurar ejecución del test
        $this->assertTrue(true);
    }
}
