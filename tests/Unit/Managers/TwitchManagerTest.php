<?php

namespace Tests\Unit\Managers;

use Tests\TestCase;
use App\Managers\TwitchManager;
use App\Infrastructure\Clients\ApiClient;
use App\Providers\TwitchTokenProvider;
use Illuminate\Http\Client\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class TwitchManagerTest extends TestCase
{
    private $tokenProvider;
    private $apiClient;
    private $twitchManager;

    protected function setUp(): void
    {
        parent::setUp();


        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->apiClient     = $this->createMock(ApiClient::class);


        $this->twitchManager = new TwitchManager($this->tokenProvider, $this->apiClient);
    }

    /**
     * @test
     */
    public function fetchStreamsFromTwitch()
    {
        $fakeToken       = 'fake-token';
        $fakeStreamsData = ['data' => 'streamData'];
        $guzzleResponse  = new GuzzleResponse(200, [], json_encode($fakeStreamsData));
        $fakeResponse    = new Response($guzzleResponse);
        $this->tokenProvider->method('getTokenFromTwitch')
            ->willReturn($fakeToken);
        $this->apiClient->method('httpFetchStreamsFromTwitch')
            ->with($fakeToken)
            ->willReturn($fakeResponse);

        $result = $this->twitchManager->fetchStreamsFromTwitch();

        $this->assertEquals(['status' => 200, 'body' => json_encode($fakeStreamsData)], $result);
    }

    /**
     * @test
     */
    public function validatesStreamDataFormat()
    {
        $streamerId       = '123456';
        $fakeToken        = 'fake-token';
        $fakeStreamerData = [
            'data' => [[
                'id'                => $streamerId,
                'login'             => 'testuser',
                'display_name'      => 'Test User',
                'type'              => 'staff',
                'broadcaster_type'  => 'partner',
                'description'       => 'A test user',
                'profile_image_url' => 'http://example.com/image.jpg',
                'offline_image_url' => 'http://example.com/offline.jpg',
                'view_count'        => 123,
                'created_at'        => '2020-01-01T00:00:00Z'
            ]]
        ];
        $guzzleResponse = new GuzzleResponse(200, [], json_encode($fakeStreamerData));
        $fakeResponse   = new Response($guzzleResponse);
        $this->tokenProvider->method('getTokenFromTwitch')
            ->willReturn($fakeToken);
        $this->apiClient->method('httpfetchStreamerDataFromTwitch')
            ->with($fakeToken, $streamerId)
            ->willReturn($fakeResponse);

        $result = $this->twitchManager->fetchStreamerDataFromTwitch($streamerId);

        $expected = [
            'twitch_id'         => $streamerId,
            'login'             => 'testuser',
            'display_name'      => 'Test User',
            'type'              => 'staff',
            'broadcaster_type'  => 'partner',
            'description'       => 'A test user',
            'profile_image_url' => 'http://example.com/image.jpg',
            'offline_image_url' => 'http://example.com/offline.jpg',
            'view_count'        => 123,
            'created_at'        => '2020-01-01 00:00:00'
        ];
        $this->assertEquals($expected, $result);
    }
}
