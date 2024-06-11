<?php

namespace Tests\Unit;

use Exception;
use ReflectionClass;
use ReflectionException;
use Tests\TestCase;
use App\Services\FollowStreamerService;
use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\JsonResponse;
use Mockery;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class FollowStreamerServiceTest extends TestCase
{
    protected DBClient $dbClient;
    protected ApiClient $apiClient;
    protected FollowStreamerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClient  = Mockery::mock(DBClient::class);
        $this->apiClient = Mockery::mock(ApiClient::class);
        $this->service       = new FollowStreamerService($this->dbClient, $this->apiClient);
    }

    /** @test
     * @throws Exception
     */
    public function executeReturns404WhenUserNotFound()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
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
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->once()
            ->with(456)
            ->andReturn($userData);
        $this->apiClient->shouldReceive('fetchStreamerDataFromTwitch')
            ->once()
            ->with('123')
            ->andReturn(['display_name' => 'StreamerName']);
        $this->dbClient->shouldReceive('updateUserAnalyticsInDB')
            ->once()
            ->with($userData);

        $response = $this->service->execute('456', '123');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['message' => 'Ahora sigues a 123'], $response->getData(true));
    }

    /**
     * @test
     */
    public function handlesErrorWhenFetchingStreamerDataFails()
    {
        $userId           = 123;
        $streamerId       = 123;
        $exceptionMessage = 'Error del servidor al seguir al streamer';
        $this->dbClientMock
            ->shouldReceive('getUserAnalyticsByIdFromDB')
            ->with($userId)
            ->andReturn(['id' => $userId, 'name' => 'Test User']);
        $this->apiClientMock
            ->shouldReceive('fetchStreamerDataFromTwitch')
            ->with($streamerId)
            ->andThrow(new Exception($exceptionMessage));

        $response = $this->service->execute($userId, $streamerId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());
        $this->assertEquals(['error' => $exceptionMessage], $response->getData(true));
    }

    /**
     * @test
     */
    public function handlesErrorWhenMismatchingTypeInUserData()
    {
        $userData = (object) [
            'followed_streamers' => 123
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
        $this->assertEquals(500, $response->status());
        $this->assertEquals(['message' => 'Error al procesar los streamers seguidos'], $response->getData(true));
    }

    /**
     * @test
     */
    public function detectsWhenUserIsAlreadyFollowingStreamer()
    {
        $followedStreamers = [
            ['id' => 'streamer1', 'display_name' => 'Streamer 1'],
            ['id' => 'streamer2', 'display_name' => 'Streamer 2'],
            ['id' => 'streamer3', 'display_name' => 'Streamer 3'],
        ];
        $streamerId = 'streamer2';

        $result = $this->invokeMethod($this->service, 'isAlreadyFollowing', [$followedStreamers, $streamerId]);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function detectsWhenUserIsNotAlreadyFollowingStreamer()
    {
        $followedStreamers = [
            ['id' => 'streamer1', 'display_name' => 'Streamer 1'],
            ['id' => 'streamer3', 'display_name' => 'Streamer 3'],
        ];
        $streamerId = 'streamer2';

        $result = $this->invokeMethod($this->service, 'isAlreadyFollowing', [$followedStreamers, $streamerId]);

        $this->assertFalse($result);
    }

    /**
     * @throws ReflectionException
     */
    private function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
