<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Clients\DBClient;
use App\Services\UserService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UserServiceTest extends TestCase
{
    protected DBClient $dbClient;
    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClient    = Mockery::mock(DBClient::class);
        $this->userService = new UserService($this->dbClient);
    }

    /**
     * @test
     */
    public function getsUsers()
    {
        $users = new Collection([
            (object) ['username' => 'usuario1', 'followed_streamers' => json_encode([['display_name' => 'streamer1'], ['display_name' => 'streamer2']])],
            (object) ['username' => 'usuario2', 'followed_streamers' => json_encode([['display_name' => 'streamer2'], ['display_name' => 'streamer3']])]
        ]);
        $this->dbClient->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andReturn($users);

        $response = $this->userService->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            [
                'username'          => 'usuario1',
                'followedStreamers' => ['streamer1', 'streamer2']
            ],
            [
                'username'          => 'usuario2',
                'followedStreamers' => ['streamer2', 'streamer3']
            ]
        ], $response->getData(true));
    }

    /**
     * @test
     */
    public function errorWhenServerFails()
    {
        $this->dbClient->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andThrow(new Exception('Error'));

        $response = $this->userService->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());
        $this->assertEquals(['message' => 'Error del servidor al obtener la lista de usuarios'], $response->getData(true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
