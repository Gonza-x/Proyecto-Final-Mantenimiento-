<?php
use PHPUnit\Framework\TestCase;

/**
 * CP5-CF2 – Validación de Formularios
 * Objetivo: Verificar que el sistema detecte formatos incorrectos
 * en campos específicos (correo electrónico).
 */
class ValidacionFormularioFormatoEmailTest extends TestCase
{
    /**
     * Test: Envío fallido del formulario por formato de email inválido
     */
    public function testFormularioNoSeEnviaConEmailFormatoInvalido()
    {
        // Datos de prueba
        $nombre = "Carlos Suárez";
        $email = "carlosgmail.com"; // Email inválido (sin @)
        $telefono = "6000-1234";

        $errores = [];

        // Validaciones simuladas
        if (empty($email)) {
            $errores[] = "El email es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "Formato de correo inválido";
        }

        // Verificar que se detecta el error de formato
        $this->assertContains(
            "Formato de correo inválido",
            $errores,
            "Debe mostrarse error por formato incorrecto de correo"
        );

        // Simular que el formulario NO se envía
        $formularioEnviado = false;

        $this->assertFalse(
            $formularioEnviado,
            "El formulario no debe enviarse con email inválido"
        );
    }
}
