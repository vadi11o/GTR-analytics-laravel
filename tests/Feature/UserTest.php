<?php

namespace Tests\Feature;

use App\Infrastructure\Clients\DBClient;
use Exception;
use Tests\TestCase;
use App\Services\UserService;
use App\Infrastructure\Controllers\UserController;
use Illuminate\Http\JsonResponse;
use Mockery;
use Illuminate\Database\Eloquent\Collection;

class UserTest extends TestCase
{
    protected DBClient $dbClientMock;
    protected UserService $userService;
    protected UserController $userController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClientMock = Mockery::mock('App\Infrastructure\Clients\DBClient');
        $this->userService = new UserService($this->dbClientMock);
        $this->userController = new UserController($this->userService);
    }

    /** @test */
    public function itReturns200WithListOfUsers()
    {
        $users = new Collection([
            (object) ['username' => 'usuario1', 'followed_streamers' => json_encode([['display_name' => 'streamer1'], ['display_name' => 'streamer2']])],
            (object) ['username' => 'usuario2', 'followed_streamers' => json_encode([['display_name' => 'streamer2'], ['display_name' => 'streamer3']])]
        ]);
        $this->dbClientMock->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andReturn($users);

        $response = $this->userController->__invoke();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            [
                'username' => 'usuario1',
                'followedStreamers' => ['streamer1', 'streamer2']
            ],
            [
                'username' => 'usuario2',
                'followedStreamers' => ['streamer2', 'streamer3']
            ]
        ], $response->getData(true));
    }

    /** @test */
    public function itReturns500OnServerError()
    {
        $this->dbClientMock->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andThrow(new Exception('Error'));

        $response = $this->userController->__invoke();

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
