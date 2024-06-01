<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\DBClient;
use App\Services\UserRegisterService;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class RegisterUserTest extends TestCase
{
    protected $dbClientMock;
    protected $userRegisterService;

    public function setUp(): void
    {
        parent::setUp();
        $this->dbClientMock        = Mockery::mock(DBClient::class);
        $this->userRegisterService = new UserRegisterService($this->dbClientMock);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function testRegisterUserFailsWhenUsernameIsAlreadyTaken()
    {
        $username = 'testUser';
        $password = 'password123';

        // Simulando que el usuario ya existe
        $this->dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->once()
            ->with($username)
            ->andReturn(true);

        $response = $this->userRegisterService->execute($username, $password);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('El nombre de usuario ya está en uso', $response->getData()->message);
    }

    public function testRegisterUserSucceedsWhenUsernameIsNotTaken()
    {
        $username = 'newUser';
        $password = 'newPassword123';

        $this->dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->once()
            ->with($username)
            ->andReturn(null);

        $this->dbClientMock->shouldReceive('insertUserAnalyticsToDB')
            ->once()
            ->with(['username' => $username, 'password' => $password]);

        $response = $this->userRegisterService->execute($username, $password);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Usuario creado correctamente', $response->getData()->message);
    }

}
