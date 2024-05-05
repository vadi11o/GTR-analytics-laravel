<?php

namespace Tests\Unit;

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

    public function testExecuteReturnsResponseBodyWhenCalledWithValidToken()
    {
        $twitchTokenServiceMock = Mockery::mock(TwitchTokenService::class);
        $twitchTokenServiceMock->shouldReceive('getTokenFromTwitch')->once()->andReturn('valid_token');

        $responseMock = Mockery::mock('Illuminate\Http\Client\Response');
        $responseMock->shouldReceive('status')->once()->andReturn(200);
        $responseMock->shouldReceive('body')->once()->andReturn('expected_response_body');

        Http::shouldReceive('withHeaders')
            ->once()
            ->with([
                'Authorization' => 'Bearer valid_token',
                'Client-Id' => env('TWITCH_CLIENT_ID'),
            ])
            ->andReturnSelf()
            ->shouldReceive('get')
            ->once()
            ->with(env('TWITCH_URL') . '/streams')
            ->andReturn($responseMock);

        $service = new GetStreamsService($twitchTokenServiceMock);
        $result = $service->execute();

        $this->assertEquals('expected_response_body', $result);
    }

    public function testCurlPetitionShouldReturnCorrectHttpStatusAndBodyWhenCalledWithValidCredentials()
    {
        $responseMock = Mockery::mock('Illuminate\Http\Client\Response');
        $responseMock->shouldReceive('status')->andReturn(200);
        $responseMock->shouldReceive('body')->andReturn('{"data": "some data"}');

        Http::shouldReceive('withHeaders')
            ->with([
                'Authorization' => 'Bearer dummy_token',
                'Client-Id' => 'dummy_client_id',
            ])
            ->andReturnSelf()
            ->shouldReceive('get')
            ->with('https://api.twitch.tv/helix/streams')
            ->andReturn($responseMock);

        $twitchTokenServiceMock = Mockery::mock('App\Services\TwitchTokenService');
        $twitchTokenServiceMock->shouldReceive('getTokenFromTwitch')->andReturn('dummy_token');

        $service = new GetStreamsService($twitchTokenServiceMock);
        $result = $service->curlPetition('https://api.twitch.tv/helix/streams', 'dummy_token', 'dummy_client_id');

        $this->assertEquals(['status' => 200, 'body' => '{"data": "some data"}'], $result);
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
}
