<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use App\Providers\TwitchTokenProvider;
use App\Services\GetStreamsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class StreamsTest extends TestCase
{
    protected ApiClient $apiClient;
    private GetStreamsService $service;

    /**
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->apiClient     = $this->getMockBuilder(ApiClient::class)
            ->setConstructorArgs([$this->tokenProvider])
            ->onlyMethods(['fetchStreamsFromTwitch'])
            ->getMock();
        $this->service = new GetStreamsService($this->apiClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**@test
     * @throws ConnectionException
     */
    public function getStreamServiceReturnsValidJson()
    {
        $fakeResponse = [
            'body' => json_encode(['data' => [['title' => 'Stream 1', 'user_name' => 'User 1']]])
        ];
        $this->apiClient
            ->method('fetchStreamsFromTwitch')
            ->willReturn($fakeResponse);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJsonStringEqualsJsonString(
            json_encode([['title' => 'Stream 1', 'user_name' => 'User 1']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }

    /**@test
     * @throws ConnectionException
     */
    public function getStreamServiceExecuteHandlesEmptyData()
    {
        $fakeResponse = ['body' => json_encode(['data' => []])];
        $this->apiClient
            ->method('fetchStreamsFromTwitch')
            ->willReturn($fakeResponse);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJsonStringEqualsJsonString(
            json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }

    /** @test
     * @throws \ReflectionException
     */
    public function getStreamsServiceTreatsDataWell()
    {
        $rawData          = json_encode(['data' => [['title' => 'Stream 1', 'user_name' => 'User 1']]]);
        $expectedResponse = [['title' => 'Stream 1', 'user_name' => 'User 1']];
        $method           = new ReflectionMethod(GetStreamsService::class, 'treatData');

        $response = $method->invoke($this->service, $rawData);

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }

    /** @test
     * @throws \ReflectionException
     */
    public function getStreamsServiceTreatsEmptyDataWell()
    {
        $rawData          = json_encode(['data' => []]);
        $expectedResponse = [];
        $method           = new ReflectionMethod(GetStreamsService::class, 'treatData');

        $response = $method->invoke($this->service, $rawData);

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }

    /** @test
     */
    public function fetchStreamsFromTwitchReturnsSuccess()
    {
        $this->apiClient = new ApiClient($this->tokenProvider);
        $token           = 'test_token';
        $streamsData     = [
            'data' => [
                [
                    'id'        => '1',
                    'user_name' => 'streamer1',
                    'game_id'   => '1234',
                    'title'     => 'Stream Title 1',
                ]
            ]
        ];
        $this->tokenProvider->expects($this->once())
            ->method('getTokenFromTwitch')
            ->willReturn($token);
        Http::fake([
            env('TWITCH_URL') . '/streams' => Http::response($streamsData, 200)
        ]);

        $result = $this->apiClient->fetchStreamsFromTwitch();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(json_encode($streamsData), $result['body']);
    }

    /** @test
     */
    public function fetchStreamsFromTwitchReturnsFailure()
    {
        $this->apiClient = new ApiClient($this->tokenProvider);
        $token           = 'test_token';
        $this->tokenProvider->expects($this->once())
            ->method('getTokenFromTwitch')
            ->willReturn($token);
        Http::fake([
            env('TWITCH_URL') . '/streams' => Http::response(null, 500)
        ]);

        $result = $this->apiClient->fetchStreamsFromTwitch();

        $this->assertEquals(500, $result['status']);
        $this->assertEquals('', $result['body']);
    }

    /** @test
     */
    public function fetchStreamsFromTwitchUsesCorrectToken()
    {
        $this->apiClient = new ApiClient($this->tokenProvider);
        $token           = 'test_token';
        $this->tokenProvider->expects($this->once())
            ->method('getTokenFromTwitch')
            ->willReturn($token);
        Http::fake();

        $this->apiClient->fetchStreamsFromTwitch();

        Http::assertSent(function ($request) use ($token) {
            return $request->hasHeader('Authorization', 'Bearer ' . $token)
                && $request->hasHeader('Client-Id', env('TWITCH_CLIENT_ID'));
        });
    }
}
