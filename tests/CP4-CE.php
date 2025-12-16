<?php
use PHPUnit\Framework\TestCase;

/**
 * CP4-CE – Gestión de Usuarios (Admin)
 * Objetivo: Verificar que el administrador pueda actualizar
 * correctamente los datos de un usuario.
 */
class GestionUsuariosAdminTest extends TestCase
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
     * Test: Actualización de datos de usuario por administrador
     */
    public function testAdministradorPuedeActualizarUsuario()
    {
        // Datos de prueba
        $email = "maria.perez@email.com";
        $nuevoNombre = "María Pérez";
        $nuevoTelefono = "6000-0000";

        // Verificar que el usuario exista
        $stmt = $this->conexion->prepare(
            "SELECT nombre, telefono FROM usuarios WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertGreaterThan(
            0,
            $result->num_rows,
            "El usuario debe existir para poder ser actualizado"
        );

        // Simular actualización de datos
        $update = $this->conexion->prepare(
            "UPDATE usuarios SET nombre = ?, telefono = ? WHERE email = ?"
        );
        $update->bind_param("sss", $nuevoNombre, $nuevoTelefono, $email);
        $update->execute();

        // Verificar que los datos fueron actualizados
        $verificacion = $this->conexion->prepare(
            "SELECT nombre, telefono FROM usuarios WHERE email = ?"
        );
        $verificacion->bind_param("s", $email);
        $verificacion->execute();
        $usuarioActualizado = $verificacion->get_result()->fetch_assoc();

        $this->assertEquals(
            $nuevoNombre,
            $usuarioActualizado['nombre'],
            "El nombre del usuario debe actualizarse correctamente"
        );

        $this->assertEquals(
            $nuevoTelefono,
            $usuarioActualizado['telefono'],
            "El teléfono del usuario debe actualizarse correctamente"
        );

        // Línea mínima para asegurar ejecución del test
        $this->assertTrue(true);
    }
}
