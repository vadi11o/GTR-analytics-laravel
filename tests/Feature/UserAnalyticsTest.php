<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function testRegisterUserSuccessfully()
    {
        $response = $this->json('POST', '/users', [
            'username' => 'testuser',
            'password' => 'testpassword'
        ]);

        $response->assertStatus(201); // Comprueba que el estado HTTP es 201 Created
        $response->assertJson([
            'username' => 'testuser',
            'message' => 'Usuario creado correctamente'
        ]);
    }

    public function testRegisterUserFailsWhenUsernameAlreadyExists()
    {
        // Crear un usuario existente
        $this->json('POST', '/users', [
            'username' => 'existinguser',
            'password' => 'existingpassword'
        ]);

        // Intentar registrar el mismo usuario nuevamente
        $response = $this->json('POST', '/users', [
            'username' => 'existinguser',
            'password' => 'newpassword'
        ]);

        $response->assertStatus(409); // Comprueba que el estado HTTP es 409 Conflict
        $response->assertJson(['message' => 'El nombre de usuario ya está en uso']);
    }

    public function testRegisterUserValidationFailsWithIncompleteData()
    {
        // Intentar registrar un usuario sin contraseña
        $response = $this->json('POST', '/users', [
            'username' => 'newuser'
        ]);

        $response->assertStatus(422); // Comprueba que el estado HTTP es 422 Unprocessable Entity
        $response->assertJsonValidationErrors(['password']); // Comprueba que hay errores de validación en el campo 'password'
    }
}
