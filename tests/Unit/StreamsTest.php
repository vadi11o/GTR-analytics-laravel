<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use App\Services\GetStreamsService;
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
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->service   = new GetStreamsService($this->apiClient);
    }

    public function testExecuteCallsGetTokenFromTwitch()
    {
        $this->apiClient->method('getTokenFromTwitch')
            ->willReturn('someToken');

        $this->apiClient->method('sendCurlPetitionToTwitch')
            ->willReturn(['body' => json_encode(['data' => []])]);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);

        Mockery::close();
    }


    public function testExecuteCallsSendCurlPetitionToTwitchWithCorrectParameters()
    {
        $token = 'someToken';
        $url = env('TWITCH_URL') . '/streams';

        $this->apiClient->method('getTokenFromTwitch')
            ->willReturn($token);

        $this->apiClient->method('sendCurlPetitionToTwitch')
            ->with($url, $token)
            ->willReturn(['body' => json_encode(['data' => []])]);

        $this->service->execute();

        $this->assertTrue(true);
    }

    public function testExecuteFailsWhenGetTokenFromTwitchReturnsInvalidToken()
    {
        $this->apiClient->method('getTokenFromTwitch')
            ->will($this->throwException(new Exception("Failed to retrieve access token from Twitch: Unknown error", 400)));

        $this->apiClient->expects($this->never())
            ->method('sendCurlPetitionToTwitch');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Failed to retrieve access token from Twitch: Unknown error");

        $this->service->execute();
    }
    /**
     * @test
     * @throws ConnectionException
     */
    public function testTokenPetitionToTwitch()
    {
        $this->apiClient->method('getTokenFromTwitch')
            ->willReturn('test_access_token');

        $token = $this->apiClient->getTokenFromTwitch();
        $this->assertEquals('test_access_token', $token);
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function testTokenPetitionThrowsException()
    {
        $this->apiClient->method('getTokenFromTwitch')
            ->will($this->throwException(new Exception('Failed to retrieve access token from Twitch: invalid_request')));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch: invalid_request');

        $this->apiClient->getTokenFromTwitch();
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



    /** @test
     * @throws ConnectionException
     */
    public function testTreatDataWithValidInput()
    {
        $rawData = json_encode([
            'data' => [
                ['title' => 'Cool Stream', 'user_name' => 'Streamer1'],
                ['title' => 'Another Stream', 'user_name' => 'Streamer2']
            ]
        ]);

        $this->apiClient->expects($this->once())
            ->method('getTokenFromTwitch')
            ->willReturn('fake_token');

        $this->apiClient->expects($this->once())
            ->method('sendCurlPetitionToTwitch')
            ->with($this->anything(), 'fake_token')
            ->willReturn(['body' => $rawData, 'status' => 200]);

        $response = $this->service->execute();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $content = json_decode($response->getContent(), true);
        $this->assertCount(2, $content);
        $this->assertEquals('Cool Stream', $content[0]['title']);
        $this->assertEquals('Streamer1', $content[0]['user_name']);
    }

}
