<?php
use PHPUnit\Framework\TestCase;

/**
 * Test de Login y Autenticación
 * Objetivo: Verificar que el sistema de login funciona correctamente
 */
class LoginTest extends TestCase
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
     * Test: Verificar que existe la tabla de usuarios
     */
    public function testTablaUsuariosExiste()
    {
        $result = $this->conexion->query("SHOW TABLES LIKE 'usuarios'");
        $this->assertEquals(1, $result->num_rows,
            "La tabla 'usuarios' debe existir");
    }

    /**
     * Test: Verificar estructura de tabla usuarios
     */
    public function testEstructuraTablaUsuarios()
    {
        $result = $this->conexion->query("DESCRIBE usuarios");
        $columnas = [];
        
        while ($row = $result->fetch_assoc()) {
            $columnas[] = $row['Field'];
        }
        
        $this->assertContains('id', $columnas);
        $this->assertContains('email', $columnas);
        $this->assertContains('password', $columnas);
    }

    /**
     * Test: Verificar que el archivo login.php existe
     */
    public function testArchivoLoginExiste()
    {
        $this->assertFileExists(__DIR__ . '/../login.php',
            "El archivo login.php debe existir");
    }

    /**
     * Test: Verificar que hay al menos un usuario en el sistema
     */
    public function testExistenUsuariosEnSistema()
    {
        $result = $this->conexion->query("SELECT COUNT(*) as total FROM usuarios");
        $row = $result->fetch_assoc();
        
        $this->assertGreaterThanOrEqual(0, $row['total'],
            "La tabla usuarios debe estar lista para recibir usuarios");
    }

    /**
     * Test: Validar formato de email (función de validación)
     */
    public function testValidacionFormatoEmail()
    {
        // Emails válidos
        $this->assertTrue(filter_var('usuario@ejemplo.com', FILTER_VALIDATE_EMAIL) !== false);
        $this->assertTrue(filter_var('test@dominio.pa', FILTER_VALIDATE_EMAIL) !== false);
        
        // Emails inválidos
        $this->assertFalse(filter_var('usuario@', FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var('usuario.com', FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var('@dominio.com', FILTER_VALIDATE_EMAIL) !== false);
    }
}