<?php

namespace Tests\Unit\Managers;

use App\Infrastructure\Clients\ApiClient;
use App\Managers\TwitchManager;
use App\Providers\TwitchTokenProvider;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Mockery;
use Tests\TestCase;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TwitchManagerTest extends TestCase
{
    protected $tokenProvider;
    protected $apiClient;
    protected $twitchManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenProvider = Mockery::mock(TwitchTokenProvider::class);
        $this->apiClient = Mockery::mock(ApiClient::class);
        $this->twitchManager = new TwitchManager($this->tokenProvider, $this->apiClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function fetchStreamsFromTwitch()
    {
        $this->tokenProvider->shouldReceive('getTokenFromTwitch')
            ->once()
            ->andReturn('mocked_token');
        $this->apiClient->shouldReceive('httpFetchStreamsFromTwitch')
            ->with('mocked_token')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode(['data' => 'mocked_data']))));

        $result = $this->twitchManager->fetchStreamsFromTwitch();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(json_encode(['data' => 'mocked_data']), $result['body']);
    }

    /**
     * @test
     */
    public function fetchStreamerDataFromTwitch()
    {
        $this->tokenProvider->shouldReceive('getTokenFromTwitch')
            ->once()
            ->andReturn('mocked_token');
        $this->apiClient->shouldReceive('httpfetchStreamerDataFromTwitch')
            ->with('mocked_token', 'mocked_streamer_id')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode([
                'data' => [[
                    'id'                => '123',
                    'login'             => 'mocked_login',
                    'display_name'      => 'mocked_display_name',
                    'type'              => '',
                    'broadcaster_type'  => '',
                    'description'       => 'mocked_description',
                    'profile_image_url' => 'mocked_profile_image_url',
                    'offline_image_url' => 'mocked_offline_image_url',
                    'view_count'        => 1000,
                    'created_at'        => '2020-01-01T00:00:00Z'
                ]]
            ]))));

        $result = $this->twitchManager->fetchStreamerDataFromTwitch('mocked_streamer_id');

        $this->assertEquals('123', $result['twitch_id']);
        $this->assertEquals('mocked_login', $result['login']);
        $this->assertEquals('mocked_display_name', $result['display_name']);
        $this->assertEquals('mocked_description', $result['description']);
        $this->assertEquals('mocked_profile_image_url', $result['profile_image_url']);
        $this->assertEquals('mocked_offline_image_url', $result['offline_image_url']);
        $this->assertEquals(1000, $result['view_count']);
        $this->assertEquals(Carbon::parse('2020-01-01T00:00:00Z')->toDateTimeString(), $result['created_at']);
    }

    /**
     * @test
     */
    public function updatesGames()
    {
        $this->apiClient->shouldReceive('httpUpdateGames')
            ->with('mocked_token')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode([
                'data' => [
                    ['id' => '1', 'name' => 'Game 1'],
                    ['id' => '2', 'name' => 'Game 2'],
                    ['id' => '3', 'name' => 'Game 3'],
                ]
            ]))));

        $result = $this->twitchManager->updateGames('mocked_token');

        $this->assertCount(3, $result);
        $this->assertEquals('Game 1', $result[0]['name']);
        $this->assertEquals('Game 2', $result[1]['name']);
        $this->assertEquals('Game 3', $result[2]['name']);
    }

    /**
     * @test
     */
    public function updatesVideos()
    {
        $this->apiClient->shouldReceive('httpUpdateVideos')
            ->with('mocked_token', 'mocked_game_id')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode([
                'data' => [
                    ['id' => '1', 'title' => 'Video 1'],
                    ['id' => '2', 'title' => 'Video 2'],
                    ['id' => '3', 'title' => 'Video 3'],
                ]
            ]))));

        $result = $this->twitchManager->updateVideos('mocked_token', 'mocked_game_id');

        $this->assertCount(3, $result);
        $this->assertEquals('Video 1', $result[0]['title']);
        $this->assertEquals('Video 2', $result[1]['title']);
        $this->assertEquals('Video 3', $result[2]['title']);
    }

    /**
     * @test
     */
    public function getsStreamsByUserId()
    {
        $this->tokenProvider->shouldReceive('getTokenFromTwitch')
            ->once()
            ->andReturn('mocked_token');
        $this->apiClient->shouldReceive('httpGetStreamsByUserId')
            ->with('mocked_token', ['user_id' => 'mocked_user_id', 'first' => 5])
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode([
                'data' => [
                    ['id' => '1', 'title' => 'Stream 1'],
                    ['id' => '2', 'title' => 'Stream 2'],
                    ['id' => '3', 'title' => 'Stream 3'],
                ]
            ]))));

        $result = $this->twitchManager->getStreamsByUserId('mocked_user_id');

        $this->assertCount(3, $result);
        $this->assertEquals('Stream 1', $result[0]['title']);
        $this->assertEquals('Stream 2', $result[1]['title']);
        $this->assertEquals('Stream 3', $result[2]['title']);
    }
}
