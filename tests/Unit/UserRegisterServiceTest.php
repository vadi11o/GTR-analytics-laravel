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
    protected DBClient $dbClientMock;
    protected UserRegisterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClientMock = Mockery::mock(DBClient::class);
        $this->service = new UserRegisterService($this->dbClientMock);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function returnsConflictWhenUserAllreadyExists()
    {
        $this->dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with('testuser')
            ->andReturn(true);

        $response = $this->service->execute('testuser', 'testpassword');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->status());
        $this->assertEquals(['message' => 'El nombre de usuario ya está en uso'], $response->getData(true));
    }

    /**
     * @test
     */
    public function createsNewUserWhenUserDoesNotExist()
    {
        $this->dbClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with('testuser')
            ->andReturn(false);
        $this->dbClientMock->shouldReceive('insertUserAnalyticsToDB')
            ->with(['username' => 'testuser', 'password' => 'testpassword'])
            ->once();

        $response = $this->service->execute('testuser', 'testpassword');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->status());
        $this->assertEquals([
            'username' => 'testuser',
            'message'  => 'Usuario creado correctamente'
        ], $response->getData(true));
    }
}
