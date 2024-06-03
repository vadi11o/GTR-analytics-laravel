<?php

namespace Tests\Feature;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use Tests\TestCase;
use App\Services\UnfollowStreamerService;
use App\Infrastructure\Controllers\UnfollowStreamerController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Mockery;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UnfollowStreamerTest extends TestCase
{
    protected DBClient $dbClientMock;
    protected ApiClient $apiClientMock;
    protected UnfollowStreamerService $unfollowService;
    protected UnfollowStreamerController $unfollowControler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClientMock = Mockery::mock('App\Infrastructure\Clients\DBClient');
        $this->apiClientMock = Mockery::mock('App\Infrastructure\Clients\ApiClient');
        $this->unfollowService = new UnfollowStreamerService($this->dbClientMock, $this->apiClientMock);
        $this->unfollowControler = new UnfollowStreamerController($this->unfollowService);
    }

    /** @test */
    public function itReturns404WhenUserNotFound()
    {
        $request = Request::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
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
        $request = Request::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClientMock->shouldReceive('updateUserAnalyticsInDB')
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
        $request = Request::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClientMock->shouldReceive('updateUserAnalyticsInDB')
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
        $request = Request::create('/unfollow', 'POST', ['userId' => '456', 'streamerId' => '123']);
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dbClientMock->shouldReceive('updateUserAnalyticsInDB')
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
