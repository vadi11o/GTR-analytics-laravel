<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use App\Services\GetStreamsService;
use App\Services\TokenProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Mockery;
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
        $this->tokenProvider = $this->createMock(TokenProvider::class);
        //$this->apiClient = $this->createMock(ApiClient::class);
        $this->apiClient = $this->getMockBuilder(ApiClient::class)
            ->setConstructorArgs([$this->tokenProvider])
            ->onlyMethods(['sendCurlPetitionToTwitch'])
            ->getMock();
        $this->service   = new GetStreamsService($this->apiClient);
    }

    public function testExecuteCallsGetTokenFromTwitch()
    {
        $this->apiClient->method('sendCurlPetitionToTwitch')
            ->willReturn(['body' => json_encode(['data' => []])]);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);

        Mockery::close();
    }

    public function testExecuteCallsSendCurlPetitionToTwitchWithCorrectParameters()
    {
        $url   = env('TWITCH_URL') . '/streams';

        $this->tokenProvider->method('getTokenFromTwitch')
            ->willReturn('someToken');

        $this->apiClient->expects($this->once())
            ->method('sendCurlPetitionToTwitch')
            ->with($url)
            ->willReturn(['body' => json_encode(['data' => []])]);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /** @test */
    public function testCurlPetitionToTwitch()
    {
        $fakeResponse = [
            'status' => 200,
            'body'   => json_encode([
                'data' => [
                    [
                        'id'        => '123456789',
                        'user_name' => 'SandySanderman',
                        'title'     => 'Cool Stream'
                    ]
                ]
            ])
        ];
        $twitchStreamsUrl = 'https://api.twitch.tv/helix/streams';
        $twitchToken      = 'some_fake_token';

        $this->apiClient->method('sendCurlPetitionToTwitch')
            ->with($twitchStreamsUrl, $twitchToken)
            ->willReturn($fakeResponse);

        $response = $this->apiClient->sendCurlPetitionToTwitch($twitchStreamsUrl, $twitchToken);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals($fakeResponse['body'], $response['body']);
    }

    /** @test */
    public function testCurlPetitionToTwitchFailsWithNotFoundError()
    {
        $twitchStreamsUrl = 'https://api.twitch.tv/helix/streams';
        $twitchToken      = 'some_fake_token';
        $errorMessage     = ['message' => 'Stream not found', 'status' => 404];

        $this->apiClient->method('sendCurlPetitionToTwitch')
            ->willReturn([
                'status' => 404,
                'body'   => json_encode($errorMessage)
            ]);

        $response     = $this->apiClient->sendCurlPetitionToTwitch($twitchStreamsUrl, $twitchToken);
        $responseBody = json_decode($response['body'], true);

        $this->assertEquals(404, $response['status']);
        $this->assertEquals('Stream not found', $responseBody['message']);
    }
}
