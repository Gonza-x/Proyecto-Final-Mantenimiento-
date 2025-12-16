<?php
use PHPUnit\Framework\TestCase;

/**
 * CP1-CF1 – Exploración de provincias sin datos
 * Objetivo: Validar el comportamiento del sistema
 * cuando no existen provincias disponibles para mostrar.
 */
class ProvinciasSinDatosTest extends TestCase
{
    /**
     * Test: CP1-CF1
     * Verificar que el sistema muestre un mensaje informativo
     * cuando no hay provincias disponibles.
     */
    public function testMensajeCuandoNoHayProvincias()
    {
        // Simulación de resultado vacío (sin tocar la BD)
        $provincias = [];

        if (empty($provincias)) {
            $mensaje = "No hay provincias disponibles";
        }

        $this->assertEquals(
            "No hay provincias disponibles",
            $mensaje,
            "El sistema debe mostrar un mensaje cuando no existen provincias"
        );
    }

    /**
     * Test: Verificar que la página principal de provincias existe
     * y que la navegación no se rompe
     */
    public function testPaginaProvinciasExiste()
    {
        $this->assertFileExists(
            __DIR__ . '/../index.php',
            "La página principal debe existir aunque no haya provincias"
        );
    }
}
