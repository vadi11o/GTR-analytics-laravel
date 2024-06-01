<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\DBClient;
use App\Services\UserService;
use App\Services\UserServiceRegister;
use Mockery;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class UserAnalyticsTest extends TestCase
{
    private DBClient $dBClientMock;
    /**
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dBClientMock = Mockery::mock(DBClient::class);
    }
    /** @test */
    public function givenUserAnalyticsAlreadyRegisteredRetrunsUser()
    {
        $username   = 'usuarioRegistrado';
        $userFromDB = ['name' => 'usuarioRegistrado', 'analytics' => 'someData'];

        $this->dBClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with($username)
            ->once()
            ->andReturn($userFromDB);

        $service = new UserService($this->dBClientMock);

        $result = $service->execute($username);
        $this->assertEquals($userFromDB, $result);
    }

    /** @test */
    public function givenUserAnalyticsNotRegisteredRetrunsNull()
    {
        $username = 'usuarioNoRegistrado';

        $this->dBClientMock->shouldReceive('getUserAnalyticsByNameFromDB')
            ->with($username)
            ->once()
            ->andReturn(false);

        $service = new UserService($this->dBClientMock);

        $result = $service->execute($username);
        $this->assertNull($result);
    }

    /** @test */
    public function givenUserAnalyticsNotRegisteredRegistersCorrectly()
    {
        $username = 'usuarioNoRegistrado';
        $password = 'contraseñaSegura123';
        $datos    = ['username' => $username, 'password' => $password];

        $this->dBClientMock->shouldReceive('insertUserAnalyticsToDB')
            ->with($datos)
            ->once();

        $service = new UserServiceRegister($this->dBClientMock);

        $service->execute($username, $password);

    }
}
