<?php

namespace Tests\Feature;

use App\Infrastructure\Clients\DBClient;
use Exception;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UserRegisterTest extends TestCase
{
    protected DBClient $dbClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClient = Mockery::mock(DBClient::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function registerUserSuccess()
    {
        $dbClientMock = $this->dbClient;
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
     * @test
     */
    public function registerUserFailureWhenUserAllreadyExists()
    {
        $dbClientMock = $this->dbClient;
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
     * @test
     */
    public function registerUserFailureWhenMissingUsernameOrPassword()
    {
        $response = $this->postJson('analytics/users', [
            'username' => '',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El nombre del usuario es obligatorio',
            ]);

        $response = $this->postJson('analytics/users', [
            'username' => 'testuser',
            'password' => '',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'La contraseña es obligatoria',
            ]);
    }

    /**
     * @test
     */
    public function registerUserFailsDueToDatabaseError()
    {
        $dbClientMock = $this->dbClient;
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
