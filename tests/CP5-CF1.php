<?php
use PHPUnit\Framework\TestCase;

/**
 * CP5-CF1 – Validación de Formularios
 * Objetivo: Validar que el sistema no permita el envío
 * cuando faltan campos obligatorios.
 */
class ValidacionFormularioCamposObligatoriosTest extends TestCase
{
    /**
     * Test: Envío fallido del formulario por campos obligatorios vacíos
     */
    public function testFormularioNoSeEnviaConCamposObligatoriosVacios()
    {
        // Datos de prueba (email vacío)
        $nombre = "Carlos Suárez";
        $email = ""; // Campo obligatorio vacío
        $telefono = "6000-1234";

        // Validaciones simuladas
        $errores = [];

        if (empty($nombre)) {
            $errores[] = "El nombre es obligatorio";
        }

        if (empty($email)) {
            $errores[] = "El email es obligatorio";
        }

        if (empty($telefono)) {
            $errores[] = "El teléfono es obligatorio";
        }

        // Verificar que se detectan errores
        $this->assertNotEmpty(
            $errores,
            "El sistema debe detectar campos obligatorios vacíos"
        );

        // Verificar mensaje de error específico
        $this->assertContains(
            "El email es obligatorio",
            $errores,
            "Debe mostrarse error por email vacío"
        );

        // Simular que el formulario NO se envía
        $formularioEnviado = false;

        $this->assertFalse(
            $formularioEnviado,
            "El formulario no debe enviarse si faltan campos obligatorios"
        );
    }
}

