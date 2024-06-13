<?php

namespace Services;

use App\Infrastructure\Clients\ApiClient;
use App\Managers\TwitchManager;
use App\Providers\TwitchTokenProvider;
use App\Services\GetStreamsService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Mockery;
use ReflectionException;
use ReflectionMethod;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class GetStreamsServiceTest extends TestCase
{
    protected TwitchManager $twitchManager;

    protected ApiClient $apiClient;
    private GetStreamsService $service;

    /**
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->twitchManager     = $this->getMockBuilder(TwitchManager::class)
            ->setConstructorArgs([$this->tokenProvider, $this->apiClient])
            ->onlyMethods(['fetchStreamsFromTwitch'])
            ->getMock();

        $this->service = new GetStreamsService($this->twitchManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function returnsDataInValidFormat()
    {
        $fakeResponse = [
            'body' => json_encode(['data' => [['title' => 'Stream 1', 'user_name' => 'User 1']]])
        ];
        $this->twitchManager
            ->method('fetchStreamsFromTwitch')
            ->willReturn($fakeResponse);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJsonStringEqualsJsonString(
            json_encode([['title' => 'Stream 1', 'user_name' => 'User 1']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function emptyDataWhenThereAreNoStreams()
    {
        $fakeResponse = ['body' => json_encode(['data' => []])];
        $this->twitchManager
            ->method('fetchStreamsFromTwitch')
            ->willReturn($fakeResponse);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJsonStringEqualsJsonString(
            json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getContent()
        );
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function treatsDataWellToValidFormat()
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

    /**
     * @test
     * @throws ReflectionException
     */
    public function emptyDataWhileTreatingFormat()
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
}
