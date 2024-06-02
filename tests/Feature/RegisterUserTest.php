<?php

namespace Tests\Feature;

use App\Infrastructure\Clients\DBClient;
use Exception;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RegisterUserTest extends TestCase
{
    /**
     * @Test
     */
    public function testRegisterUserSuccess()
    {
        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with('testuser')
            ->andReturn(false);
        $dbClientMock->shouldReceive('insertUserAnalyticsToDB')
            ->once();
        $this->app->instance(DBClient::class, $dbClientMock);

        $response = $this->postJson('analytics/users', [
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'username' => 'testuser',
                'message'  => 'Usuario creado correctamente',
            ]);
    }

    /**
     * @Test
     */
    public function testRegisterUserFailureUserExists()
    {
        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')->with('testuser')->andReturn(true);
        $this->app->instance(DBClient::class, $dbClientMock);

        $response = $this->postJson('analytics/users', [
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'El nombre de usuario ya está en uso',
            ]);
    }

    /**
     * @Test
     */
    public function RegisterUserFailureMissingUsernameOrPassword()
    {
        $response = $this->postJson('analytics/users', [
            'username' => '',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Falta el nombre de usuario o la contraseña',
            ]);

        $response = $this->postJson('analytics/users', [
            'username' => 'testuser',
            'password' => '',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Falta el nombre de usuario o la contraseña',
            ]);
    }

    /**
     * @Test
     */
    public function testRegisterUserServiceThrowsException()
    {
        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')->with('testuser')->andThrow(new Exception('Database error'));
        $this->app->instance(DBClient::class, $dbClientMock);

        $response = $this->postJson('analytics/users', [
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Database error',
            ]);
    }
}
