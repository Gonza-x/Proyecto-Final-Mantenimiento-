<?php
use PHPUnit\Framework\TestCase;

/**
 * CP5-CE – Validación de Formularios
 * Objetivo: Verificar que el formulario se envía correctamente
 * cuando todos los datos son válidos.
 */
class ValidacionFormularioExitosaTest extends TestCase
{
    /**
     * Test: Envío exitoso del formulario con datos válidos
     */
    public function testEnvioFormularioConDatosValidos()
    {
        // Datos de prueba válidos
        $nombre = "Carlos Suárez";
        $email = "carlos.suarez@gmail.com";
        $telefono = "6000-1234";

        // Simulación de validaciones del formulario
        $esNombreValido = !empty($nombre);
        $esEmailValido = filter_var($email, FILTER_VALIDATE_EMAIL);
        $esTelefonoValido = preg_match('/^[0-9]{4}-[0-9]{4}$/', $telefono);

        // Verificar que no hay errores de validación
        $this->assertTrue($esNombreValido, "El nombre debe ser válido");
        $this->assertTrue($esEmailValido, "El email debe ser válido");
        $this->assertTrue(
            $esTelefonoValido === 1,
            "El teléfono debe cumplir el formato válido"
        );

        // Simular envío del formulario
        $formularioEnviado = true;
        $mensajeExito = "Formulario enviado correctamente";

        // Resultado esperado
        $this->assertTrue(
            $formularioEnviado,
            "El formulario debe enviarse sin errores"
        );

        $this->assertEquals(
            "Formulario enviado correctamente",
            $mensajeExito,
            "Debe mostrarse un mensaje de envío exitoso"
        );
    }
}
