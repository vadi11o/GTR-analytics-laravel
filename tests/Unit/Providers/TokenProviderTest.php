<?php

namespace Tests\Unit\Providers;

use App\Providers\TwitchTokenProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TokenProviderTest extends TestCase
{
    private TwitchTokenProvider $tokenProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenProvider = new TwitchTokenProvider('test_client_id', 'test_client_secret');
    }

    /**
     * @test
     * @throws Exception
     */
    public function SuccesRetrievingTokenFromTwitch()
    {
        Http::fake([
            'https://id.twitch.tv/oauth2/token' => Http::response(['access_token' => 'test_token']),
        ]);

        $token = $this->tokenProvider->getTokenFromTwitch();
        $this->assertEquals('test_token', $token);
    }

    /**
     * @test
     */
    public function ErrorIfTokenRetrievalFromTwitchFails()
    {
        Http::fake([
            'https://id.twitch.tv/oauth2/token' => Http::response(['error' => 'invalid_client'], 400),
        ]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch: invalid_client');
        Log::shouldReceive('warning')
            ->once()
            ->with('Failed to retrieve access token from Twitch', Mockery::type('array'));

        $this->tokenProvider->getTokenFromTwitch();
    }
}
