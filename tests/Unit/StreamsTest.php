<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class StreamsTest extends TestCase
{
    /**
     * @test
     * @throws ConnectionException
     */
    public function testTokenPetitionToTwitch()
    {
        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response([
                'access_token' => 'test_access_token',
                'expires_in'   => 3600,
            ], 200),
        ]);

        $service = new ApiClient();

        $token = $service->getTokenFromTwitch();

        $this->assertEquals('test_access_token', $token);

        Http::assertSent(function ($request) use ($service) {
            return $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') && $request->url() == 'https://id.twitch.tv/oauth2/token' && $request['client_id'] === $service->getClientId() && $request['client_secret'] === $service->getClientSecret() && $request['grant_type'] === 'client_credentials';
        });
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function testTokenRetrievalThrowsExceptionWhenAccessTokenIsMissing()
    {
        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response([
                'error'             => 'invalid_request',
                'error_description' => 'Missing parameters.'
            ], 400),
        ]);

        $service = new ApiClient();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch: invalid_request');

        $service->getTokenFromTwitch();
    }

    /** @test */
    public function testCurlPetitionToTwitch()
    {
        $fakeResponse = [
            'data' => [
                [
                    'id'            => '123456789',
                    'user_id'       => '98765',
                    'user_login'    => 'sandysanderman',
                    'user_name'     => 'SandySanderman',
                    'game_id'       => '494131',
                    'game_name'     => 'Little Nightmares',
                    'type'          => 'live',
                    'title'         => 'hablamos y le damos a Little Nightmares 1',
                    'tags'          => ['Español'],
                    'viewer_count'  => 78365,
                    'started_at'    => '2021-03-10T15:04:21Z',
                    'language'      => 'es',
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/previews-ttv/live_user_auronplay-{width}x{height}.jpg',
                    'tag_ids'       => [],
                    'is_mature'     => false
                ]
            ],
            'pagination' => [
                'cursor' => 'eyJiIjp7IkN1cnNvciI6ImV5SnpJam8zT0RNMk5TNDBORFF4TlRjMU1UY3hOU3dpWkNJNlptRnNjMlVzSW5RaU9uUnlkV1Y5In0sImEiOnsiQ3Vyc29yIjoiZXlKeklqb3hOVGs0TkM0MU56RXhNekExTVRZNU1ESXNJbVFpT21aaGJITmxMQ0owSWpwMGNuVmxmUT09In19'
            ]
        ];
        $twitchStreamsUrl = 'https://api.twitch.tv/helix/streams';
        $twitchToken      = 'some_fake_token';

        Http::fake([
            $twitchStreamsUrl => Http::response($fakeResponse, 200),
        ]);

        $service = new ApiClient();

        $response = $service->sendCurlPetitionToTwitch($twitchStreamsUrl, $twitchToken);

        $this->assertEquals(200, $response['status']);
        $this->assertJsonStringEqualsJsonString(json_encode($fakeResponse), $response['body']);

        Http::assertSent(function ($request) use ($twitchToken, $twitchStreamsUrl) {
            return $request->url() == $twitchStreamsUrl && $request->method() === 'GET' && $request->hasHeader('Authorization', 'Bearer ' . $twitchToken) && $request->hasHeader('Client-Id');
        });
    }

    /** @test */
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

        $service = new ApiClient();

        $response = $service->fetchUserDataFromTwitch($token, $userId);

        $this->assertArrayHasKey('twitch_id', $response);
        $this->assertEquals('12345', $response['twitch_id']);
        $this->assertEquals('Test User', $response['display_name']);
        $this->assertEquals(Carbon::parse('2020-01-01T00:00:00Z')->toDateTimeString(), $response['created_at']);

        Http::assertSent(function ($request) use ($token, $url) {
            return $request->url() == $url && $request->hasHeader('Authorization', 'Bearer ' . $token) && $request->hasHeader('Client-Id');
        });
    }

    /** @test */
    public function testParsesJsonFromTwitchResponseUnsuccessfully()
    {
        $token  = 'fake_token';
        $userId = 'wrong_id';
        $url    = 'https://api.twitch.tv/helix/users?id=' . $userId;

        Http::fake([
            $url => Http::response(['message' => 'Not Found'], 404),
        ]);

        $service = new ApiClient();

        $response = $service->fetchUserDataFromTwitch($token, $userId);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Failed to fetch data from Twitch', $response['error']);
        $this->assertEquals(404, $response['status_code']);
    }

}
