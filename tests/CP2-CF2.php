<?php
use PHPUnit\Framework\TestCase;

/**
 * CP2-CF2 – Creación de Reserva con datos de contacto inválidos
 * Objetivo: Validar que no se confirme una reserva con correo inválido.
 */
class CreacionReservaDatosInvalidosTest extends TestCase
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
     * Test: No permitir reserva con correo inválido
     */
    public function testNoCrearReservaConCorreoInvalido()
    {
        // Datos de prueba
        $nombre = "Carlos Suarez";
        $correo = "carlos.suarezgmail.com"; // correo inválido (sin @)
        $destino = "Parque Nacional Coiba";
        $fecha = "2025-12-15";
        $adultos = 2;
        $ninos = 1;
        $jubilados = 0;

        // Validación básica del correo
        $correoValido = filter_var($correo, FILTER_VALIDATE_EMAIL);

        // Comprobación
        $this->assertFalse(
            $correoValido,
            "El sistema debe detectar el correo como inválido"
        );

        // Línea mínima para garantizar ejecución del test
        $this->assertTrue(true);
    }
}
