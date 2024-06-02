<?php

namespace Tests\Unit;

use Exception;
use Tests\TestCase;
use App\Services\FollowStreamerService;
use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\JsonResponse;
use Mockery;

class FollowStreamerServiceTest extends TestCase
{
    protected DBClient $dbClientMock;
    protected ApiClient $apiClientMock;
    protected FollowStreamerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClientMock = Mockery::mock(DBClient::class);
        $this->apiClientMock = Mockery::mock(ApiClient::class);
        $this->service = new FollowStreamerService($this->dbClientMock, $this->apiClientMock);
    }

    /** @test
     * @throws Exception
     */
    public function executeReturns404WhenUserNotFound()
    {
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with(456)
            ->andReturn(false);

        $response = $this->service->execute('456', '123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals(['message' => 'Usuario no encontrado'], $response->getData(true));
    }

    /** @test
     * @throws Exception
     */
    public function executeReturns409WhenAlreadyFollowing()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([['id' => '123']])
        ];
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with('456')
            ->andReturn($userData);
        $this->apiClientMock->shouldReceive('fetchStreamerDataFromTwitch')
            ->once()
            ->with('123')
            ->andReturn(['display_name' => 'StreamerName']);
        $this->dbClientMock->shouldReceive('updateUserAnalyticsInDB')
            ->never();

        $response = $this->service->execute('456', '123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->status());
        $this->assertEquals(['message' => 'Ya sigues a este streamer'], $response->getData(true));
    }

    /** @test
     * @throws Exception
     */
    public function executeReturns200WhenFollowSuccessful()
    {
        $userData = (object) [
            'followed_streamers' => json_encode([])
        ];
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with(456)
            ->andReturn($userData);
        $this->apiClientMock->shouldReceive('fetchStreamerDataFromTwitch')
            ->once()
            ->with('123')
            ->andReturn(['display_name' => 'StreamerName']);
        $this->dbClientMock->shouldReceive('updateUserAnalyticsInDB')
            ->once()
            ->with($userData);

        $response = $this->service->execute('456', '123');

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
