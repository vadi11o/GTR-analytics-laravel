<?php

namespace Tests\Feature;

use App\Http\Requests\UnfollowRequest;
use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Controllers\UnfollowStreamerController;
use App\Managers\TwitchManager;
use App\Services\UnfollowStreamerService;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UnfollowStreamerTest extends TestCase
{
    protected DBClient $dbClient;
    protected TwitchManager $apiClient;
    protected UnfollowStreamerService $unfollowService;
    protected UnfollowStreamerController $unfollowControler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClient          = Mockery::mock('App\Infrastructure\Clients\DBClient');
        $this->apiClient         = Mockery::mock('App\Managers\TwitchManager');
        $this->unfollowService   = new UnfollowStreamerService($this->dbClient, $this->apiClient);
        $this->unfollowControler = new UnfollowStreamerController($this->unfollowService);
    }

    /** @test */
    public function itReturns404WhenUserNotFound()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn(null);
        $this->app->instance(DBClient::class, $this->dbClient);

        $response = $this->deleteJson('analytics/unfollow', [
            'userId'     => '456',
            'streamerId' => '123',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Usuario no encontrado']);
    }

    /** @test */
    public function itReturns404WhenNotFollowingStreamer()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '789']])
        ];
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->never();
        $this->app->instance(DBClient::class, $this->dbClient);

        $response = $this->deleteJson('analytics/unfollow', [
            'userId'     => '456',
            'streamerId' => '123',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'No sigues a este streamer']);
    }

    /** @test */
    public function itReturns500WhenFollowedStreamersNotArray()
    {
        $userData = (object) [
            'followed_streamers' => 'invalid_json'
        ];
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->never();
        $this->app->instance(DBClient::class, $this->dbClient);

        $response = $this->deleteJson('analytics/unfollow', [
            'userId'     => '456',
            'streamerId' => '123',
        ]);

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Error al procesar los streamers seguidos']);
    }

    /** @test */
    public function itReturns200WhenUnfollowSuccessful()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '123'], ['id' => '789']])
        ];
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->once()
            ->with(Mockery::on(function ($userData) {
                $decoded = json_decode($userData->followed_streamers, true);
                return is_array($decoded) && count($decoded) == 1 && $decoded[0]['id'] == '789';
            }));
        $this->app->instance(DBClient::class, $this->dbClient);

        $response = $this->deleteJson('analytics/unfollow', [
            'userId'     => '456',
            'streamerId' => '123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Dejaste de seguir a 123']);
    }

    /** @test */
    public function errorWhenMissingParameters()
    {
        $response = $this->deleteJson('analytics/unfollow', [
            'userId'     => '',
            'streamerId' => 123,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El ID del usuario es obligatorio',
            ]);

        $response = $this->deleteJson('analytics/unfollow', [
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
