<?php

namespace Tests\Unit;

use App\Http\Clients\ApiClient;
use App\Http\Controllers\StreamsController;
use Tests\TestCase;
use App\Services\StreamsService;
use App\Services\GetStreamsService;
use App\Services\TwitchTokenService;
use Mockery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;


class StreamsTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function testExecuteReturnsStreamsWithData()
    {
        $mockData = json_encode([
            'data' => [
                ['title' => 'Stream 1', 'user_name' => 'User1'],
                ['title' => 'Stream 2', 'user_name' => 'User2'],
            ]
        ]);

        $getStreamsServiceMock = Mockery::mock(GetStreamsService::class);
        $getStreamsServiceMock->shouldReceive('execute')->once()->andReturn($mockData);

        $service = new StreamsService($getStreamsServiceMock);
        $result = $service->execute();

        $this->assertInstanceOf(JsonResponse::class, $result);

        $responseData = json_decode($result->getContent(), true);
        $expectedData = [
            ['title' => 'Stream 1', 'user_name' => 'User1'],
            ['title' => 'Stream 2', 'user_name' => 'User2'],
        ];

        $this->assertEquals($expectedData, $responseData);
    }

    public function testIndexReturnsJsonResponseWithData()
    {
        $mockData = json_encode([
            ['title' => 'Stream 1', 'user_name' => 'User1'],
            ['title' => 'Stream 2', 'user_name' => 'User2'],
        ]);

        $streamsServiceMock = Mockery::mock(StreamsService::class);
        $streamsServiceMock->shouldReceive('execute')->once()->andReturn(new JsonResponse(json_decode($mockData, true)));

        $controller = new StreamsController($streamsServiceMock);
        $response = $controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_decode($mockData, true), $response->getData(true));
    }

    public function testGetsTokenFromTwitchReturnsAccessToken()
    {
        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response([
                'access_token' => '12345',
                'expires_in' => 3600,
                'token_type' => 'bearer'
            ], 200)
        ]);

        $service = new TwitchTokenService();

        $token = $service->getTokenFromTwitch();

        $this->assertEquals('12345', $token);
    }

    public function testSendCurlPetitionToTwitchReturnsCorrectData()
    {
        $twitchStreamsUrl = 'https://api.twitch.tv/helix/streams';
        $twitchToken = 'fake-token';
        $twitchClientId = 'fake-client-id';

        Http::fake([
            $twitchStreamsUrl => Http::response(['data' => 'stream data'], 200, ['Headers' => 'Value'])
        ]);

        $client = new ApiClient();
        $response = $client->sendCurlPetitionToTwitchForStreams($twitchStreamsUrl, $twitchToken, $twitchClientId);

        $this->assertEquals(200, $response['status']);
        $this->assertJson($response['body'], json_encode(['data' => 'stream data']));
    }
}
