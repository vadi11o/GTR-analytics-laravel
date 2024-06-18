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

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class GetUserTest extends TestCase
{
    protected DBClient $dBClient;
    protected UserService $userService;
    protected UserController $userController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dBClient       = Mockery::mock('App\Infrastructure\Clients\DBClient');
        $this->userService    = new UserService($this->dBClient);
        $this->userController = new UserController($this->userService);
    }

    /** @test */
    public function returnsListOfUsers()
    {
        $users = new Collection([
            (object) ['username' => 'usuario1', 'followed_streamers' => json_encode([['display_name' => 'streamer1'], ['display_name' => 'streamer2']])],
            (object) ['username' => 'usuario2', 'followed_streamers' => json_encode([['display_name' => 'streamer2'], ['display_name' => 'streamer3']])]
        ]);
        $this->dBClient->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andReturn($users);
        $this->app->instance(DBClient::class, $this->dBClient);

        $response = $this->getJson('analytics/users');

        $response->assertStatus(200);
        $response->assertJson([
            [
                'username'          => 'usuario1',
                'followedStreamers' => ['streamer1', 'streamer2']
            ],
            [
                'username'          => 'usuario2',
                'followedStreamers' => ['streamer2', 'streamer3']
            ]
        ]);
    }

    /** @test */
    public function ErrorOnServerFailure()
    {
        $this->dBClient->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andThrow(new Exception('Error'));
        $this->app->instance(DBClient::class, $this->dBClient);

        $response = $this->getJson('analytics/users');

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Error del servidor al obtener la lista de usuarios']);
    }

    /** @test */
    public function returnsEmptyListWhenNoUsers()
    {
        $users = new Collection([]);
        $this->dBClient->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andReturn($users);
        $this->app->instance(DBClient::class, $this->dBClient);

        $response = $this->getJson('analytics/users');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /** @test */
    public function returnsUserWithNoFollowedsWhenUserFollowsNoStreamers()
    {
        $users = new Collection([
            (object) ['username' => 'usuario1', 'followed_streamers' => json_encode([])],
        ]);
        $this->dBClient->shouldReceive('getAllUsersFromDB')
            ->once()
            ->andReturn($users);
        $this->app->instance(DBClient::class, $this->dBClient);

        $response = $this->getJson('analytics/users');

        $response->assertStatus(200);
        $response->assertJson([
            [
                'username'          => 'usuario1',
                'followedStreamers' => []
            ]
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
