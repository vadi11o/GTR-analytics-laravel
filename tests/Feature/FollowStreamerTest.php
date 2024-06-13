<?php

namespace Tests\Feature;

use App\Http\Requests\FollowRequest;
use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Controllers\FollowStreamerController;
use App\Managers\TwitchManager;
use App\Services\FollowStreamerService;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class FollowStreamerTest extends TestCase
{
    protected DBClient $dbClient;
    protected TwitchManager $apiClient;
    protected FollowStreamerService $followService;
    protected FollowStreamerController $followController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClient         = Mockery::mock('App\Infrastructure\Clients\DBClient');
        $this->apiClient        = Mockery::mock('App\Managers\TwitchManager');
        $this->followService    = new FollowStreamerService($this->dbClient, $this->apiClient);
        $this->followController = new FollowStreamerController($this->followService);
    }

    /** @test */
    public function itReturns404WhenUserNotFound()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn(null);
        $this->app->instance(DBClient::class, $this->dbClient);

        $response = $this->postJson('analytics/follow', [
            'userId'     => '456',
            'streamerId' => '123',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Usuario no encontrado']);
    }

    /** @test */
    public function itReturns409WhenAlreadyFollowing()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '123']])
        ];
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->apiClient->shouldReceive('fetchStreamerDataFromTwitch')
            ->once()
            ->with('123')
            ->andReturn(['display_name' => 'StreamerName']);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->never();
        $this->app->instance(DBClient::class, $this->dbClient);
        $this->app->instance(TwitchManager::class, $this->apiClient);

        $response = $this->postJson('analytics/follow', [
            'userId'     => '456',
            'streamerId' => '123',
        ]);

        $response->assertStatus(409)
            ->assertJson(['message' => 'Ya sigues a este streamer']);
    }

    /** @test */
    public function itReturns200WhenFollowSuccessful()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([])
        ];
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->apiClient->shouldReceive('fetchStreamerDataFromTwitch')
            ->once()
            ->with('123')
            ->andReturn(['display_name' => 'StreamerName']);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->once()
            ->with(Mockery::on(function ($userData) {
                $decoded = json_decode($userData->followed_streamers, true);
                return is_array($decoded) && count($decoded) == 1 && $decoded[0]['id'] == '123';
            }));
        $this->app->instance(DBClient::class, $this->dbClient);
        $this->app->instance(TwitchManager::class, $this->apiClient);

        $response = $this->postJson('analytics/follow', [
            'userId'     => '456',
            'streamerId' => '123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Ahora sigues a 123']);
    }

    /** @test */
    public function errorWhenMissingParameters()
    {
        $response = $this->postJson('analytics/follow', [
            'userId'     => '',
            'streamerId' => 123,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El ID del usuario es obligatorio',
            ]);

        $response = $this->postJson('analytics/follow', [
            'userId'     => 123,
            'streamerId' => '',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El ID del streamer es obligatorio',
            ]);
    }

    /** @test */
    public function errorWhenMissingParameters()
    {
        $response = $this->postJson('analytics/follow', [
            'userId'     => '',
            'streamerId' => 123,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El ID del usuario es obligatorio',
            ]);

        $response = $this->postJson('analytics/follow', [
            'userId'     => 123,
            'streamerId' => '',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El ID del streamer es obligatorio',
            ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
