<?php

namespace Tests\Feature;

use App\Http\Requests\FollowRequest;
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
        $this->dbClient     = Mockery::mock('App\Infrastructure\Clients\DBClient');
        $this->apiClient    = Mockery::mock('App\Managers\TwitchManager');
        $this->followService    = new FollowStreamerService($this->dbClient, $this->apiClient);
        $this->followController = new FollowStreamerController($this->followService);
    }

    /** @test */
    public function itReturns404WhenUserNotFound()
    {
        $request = FollowRequest::create('/follow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn(null);

        $response = $this->followController->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'Usuario no encontrado'], $response->getData(true));
    }

    /** @test */
    public function itReturns409WhenAlreadyFollowing()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '123']])
        ];
        $request = FollowRequest::create('/follow', 'POST', ['userId' => '456', 'streamerId' => '123']);
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

        $response = $this->followController->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->status());
        $this->assertEquals(['message' => 'Ya sigues a este streamer'], $response->getData(true));
    }

    /** @test */
    public function itReturns200WhenFollowSuccessful()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([])
        ];
        $request = FollowRequest::create('/follow', 'POST', ['userId' => '456', 'streamerId' => '123']);
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

        $response = $this->followController->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['message' => 'Ahora sigues a 123'], $response->getData(true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
