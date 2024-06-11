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
class UserRegisterServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @Test
     */
    public function testExecuteReturnsConflictWhenUserExists()
    {
        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with('testuser')
            ->andReturn(true);
        $service = new UserRegisterService($dbClientMock);

        $response = $service->execute('testuser', 'testpassword');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->status());
        $this->assertEquals(['message' => 'El nombre de usuario ya está en uso'], $response->getData(true));
    }

    /**
     * @Test
     */
    public function testExecuteCreatesUserWhenUserDoesNotExist()
    {
        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with('testuser')
            ->andReturn(false);
        $dbClientMock->shouldReceive('insertUserAnalyticsToDB')
            ->with(['username' => 'testuser', 'password' => 'testpassword'])
            ->once();
        $service = new UserRegisterService($dbClientMock);

        $response = $service->execute('testuser', 'testpassword');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->status());
        $this->assertEquals([
            'username' => 'testuser',
            'message'  => 'Usuario creado correctamente'
        ], $response->getData(true));
    }
}
