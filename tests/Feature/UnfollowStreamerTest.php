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
        $this->dbClient = Mockery::mock('App\Infrastructure\Clients\DBClient');
        $this->apiClient = Mockery::mock('App\Managers\TwitchManager');
        $this->unfollowService = new UnfollowStreamerService($this->dbClient, $this->apiClient);
        $this->unfollowControler = new UnfollowStreamerController($this->unfollowService);
    }

    /** @test */
    public function itReturns404WhenUserNotFound()
    {
        $request = UnfollowRequest::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn(null);

        $response = $this->unfollowControler->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'Usuario no encontrado'], $response->getData(true));
    }

    /** @test */
    public function itReturns404WhenNotFollowingStreamer()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '789']])
        ];
        $request = UnfollowRequest::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->never();

        $response = $this->unfollowControler->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'No sigues a este streamer'], $response->getData(true));
    }

    /** @test */
    public function itReturns500WhenFollowedStreamersNotArray()
    {
        $userData = (object) [
            'followed_streamers' => 'invalid_json'
        ];
        $request = UnfollowRequest::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->never();

        $response = $this->unfollowControler->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());
        $this->assertEquals(['message' => 'Error al procesar los streamers seguidos'], $response->getData(true));
    }

    /** @test */
    public function itReturns200WhenUnfollowSuccessful()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '123'], ['id' => '789']])
        ];
        $request = UnfollowRequest::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->once()
            ->with(Mockery::on(function($userData) {
                $decoded = json_decode($userData->followed_streamers, true);
                return is_array($decoded) && count($decoded) == 1 && $decoded[0]['id'] == '789';
            }));

        $response = $this->unfollowControler->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['message' => 'Dejaste de seguir a 123'], $response->getData(true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
