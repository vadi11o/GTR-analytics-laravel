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
        $this->apiClient = $this->getMockBuilder(ApiClient::class)
            ->setConstructorArgs([$this->tokenProvider])
            ->onlyMethods(['fetchStreamsFromTwitch'])
            ->getMock();
        $this->service   = new GetStreamsService($this->apiClient);
    }

    /**@test
     * @throws ConnectionException
     */
    public function testExecuteCallsGetTokenFromTwitch()
    {
        $this->apiClient->method('fetchStreamsFromTwitch')
            ->willReturn(['body' => json_encode(['data' => []])]);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        Mockery::close();
    }

    /**@test
     * @throws ConnectionException
     */
    public function testGetStreamServiceReturnsValidJson()
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
    public function testGetStreamServiceExecuteHandlesEmptyData()
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

    public function testGetStreamsServiceTreatsDataWell()
    {
        $rawData = json_encode(['data' => [['title' => 'Stream 1', 'user_name' => 'User 1']]]);
        $expectedResponse = [['title' => 'Stream 1', 'user_name' => 'User 1']];

        $method = new ReflectionMethod(GetStreamsService::class, 'treatData');

        $response = $method->invoke($this->service, $rawData);

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }

    public function testGetStreamsServiceTreatsEmptyDataWell()
    {
        $rawData = json_encode(['data' => []]);
        $expectedResponse = [];
        $method = new ReflectionMethod(GetStreamsService::class, 'treatData');

        $response = $method->invoke($this->service, $rawData);

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }
}
