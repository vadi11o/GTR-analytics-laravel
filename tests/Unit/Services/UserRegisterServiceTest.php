<?php

namespace Services;

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
    protected DBClient $dBClient;
    protected UserRegisterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dBClient = Mockery::mock(DBClient::class);
        $this->service  = new UserRegisterService($this->dBClient);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function errorWhenUserAllreadyExists()
    {
        $this->dBClient->shouldReceive('getUserAnalyticsByNameFromDB')
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
        $this->dBClient->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with('testuser')
            ->andReturn(false);
        $this->dBClient->shouldReceive('insertUserAnalyticsToDB')
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
