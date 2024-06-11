<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UnfollowStreamerService;
use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\JsonResponse;
use Mockery;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UnfollowStreamerServiceTest extends TestCase
{
    protected DBClient $dBClient;
    protected ApiClient $apiClient;
    protected UnfollowStreamerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dBClient = Mockery::mock(DBClient::class);
        $this->apiClient = Mockery::mock(ApiClient::class);
        $this->service = new UnfollowStreamerService($this->dBClient, $this->apiClient);
    }

    /**
     * @test
     */
    public function executeReturns404WhenUserNotFound()
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
    public function executeReturns404WhenNotFollowingStreamer()
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
    public function executeReturns500WhenFollowedStreamersNotArray()
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
    public function executeReturns200WhenUnfollowSuccessful()
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
            ->with(Mockery::on(function($userData) {
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
