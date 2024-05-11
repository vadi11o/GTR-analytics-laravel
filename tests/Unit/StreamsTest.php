<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StreamsTest extends TestCase
{
    /**
     * @test
     * @throws ConnectionException
     */
    public function testCurlPetitionForTokenRetrieve()
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
}
