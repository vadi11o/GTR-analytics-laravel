<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use App\Services\GetStreamsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class StreamsTest extends TestCase
{
    protected ApiClient $apiClient;
    private GetStreamsService $service;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->service   = new GetStreamsService($this->apiClient);
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
            ->will($this->throwException(new \Exception('Failed to retrieve access token from Twitch: invalid_request')));

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
    public function testParsesJsonFromTwitchResponseSuccessfully()
    {
        $token  = 'fake_token';
        $userId = '12345';
        $url    = 'https://api.twitch.tv/helix/users?id=' . $userId;

        $fakeResponse = [
            'data' => [
                [
                    'id'                => '12345',
                    'login'             => 'testuser',
                    'display_name'      => 'Test User',
                    'type'              => '',
                    'broadcaster_type'  => 'partner',
                    'description'       => 'A great Twitch streamer',
                    'profile_image_url' => 'https://example.com/image.jpg',
                    'offline_image_url' => 'https://example.com/offline.jpg',
                    'view_count'        => 100,
                    'created_at'        => '2020-01-01T00:00:00Z'
                ]
            ]
        ];

        Http::fake([
            $url => Http::response($fakeResponse, 200),
        ]);

        $this->apiClient->method('fetchUserDataFromTwitch')
            ->willReturn($fakeResponse['data'][0]);  // Simulate the data processing if needed

        $response = $this->apiClient->fetchUserDataFromTwitch($token, $userId);

        $this->assertEquals('12345', $response['id']);
        $this->assertEquals('Test User', $response['display_name']);
        $this->assertEquals(Carbon::parse('2020-01-01T00:00:00Z')->toDateTimeString(), Carbon::parse($response['created_at'])->toDateTimeString());

        Http::fake([
            'https://api.twitch.tv/helix/users?id=12345' => Http::response([
                'data' => [
                    [
                        'id'                => '12345',
                        'login'             => 'testuser',
                        'display_name'      => 'Test User',
                        'type'              => '',
                        'broadcaster_type'  => 'partner',
                        'description'       => 'A great Twitch streamer',
                        'profile_image_url' => 'https://example.com/image.jpg',
                        'offline_image_url' => 'https://example.com/offline.jpg',
                        'view_count'        => 100,
                        'created_at'        => '2020-01-01T00:00:00Z'
                    ]
                ]
            ], 200)
        ]);
    }

    /** @test
     * @throws ConnectionException
     */
    public function testParsesJsonFromTwitchResponseUnsuccessfully()
    {
        $token  = 'fake_token';
        $userId = 'wrong_id';
        $url    = 'https://api.twitch.tv/helix/users?id=' . $userId;

        Http::fake([
            $url => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->apiClient->method('fetchUserDataFromTwitch')
            ->willReturn(['error' => 'Failed to fetch data from Twitch', 'status_code' => 404]);  // Assume this is the format your method returns on error

        $response = $this->apiClient->fetchUserDataFromTwitch($token, $userId);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Failed to fetch data from Twitch', $response['error']);
        $this->assertEquals(404, $response['status_code']);
    }

    /**
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
