<?php

namespace Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use App\Services\UnfollowStreamerService;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UnfollowStreamerServiceTest extends TestCase
{
    protected DBClient $dBClient;
    protected TwitchManager $twitchManager;
    protected UnfollowStreamerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dBClient  = Mockery::mock(DBClient::class);
        $this->twitchManager = Mockery::mock(TwitchManager::class);
        $this->service   = new UnfollowStreamerService($this->dBClient, $this->twitchManager);
    }

    /**
     * @test
     */
    public function errorWhenUserNotFound()
    {
        $this->dBClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn(null);

        $response = $this->service->execute('456', '123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'Usuario no encontrado'], $response->getData(true));
    }

    /**
     * @test
     */
    public function errorWhenNotFollowingStreamer()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '789']])
        ];
        $this->dBClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);

        $response = $this->service->execute('456', '123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'No sigues a este streamer'], $response->getData(true));
    }

    /**
     * @test
     */
    public function errorWhenFollowedStreamersDataInBadFormat()
    {
        $userData = (object) [
            'followed_streamers' => 'invalid_json'
        ];
        $this->dBClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);

        $response = $this->service->execute('456', '123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());
        $this->assertEquals(['message' => 'Error al procesar los streamers seguidos'], $response->getData(true));
    }

    /**
     * @test
     */
    public function unfollowStremaer()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '123'], ['id' => '789']])
        ];
        $this->dBClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->dBClient->shouldReceive('updateUserAnalyticsInDB')
            ->once()
            ->with(Mockery::on(function ($userData) {
                $decoded = json_decode($userData->followed_streamers, true);
                return is_array($decoded) && count($decoded) == 1 && $decoded[0]['id'] == '789';
            }));

        $response = $this->service->execute('456', '123');

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
