<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

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

    /** @test */
    public function testCurlPetitionToTwitch()
    {
        // Mock the Twitch API response
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

}
