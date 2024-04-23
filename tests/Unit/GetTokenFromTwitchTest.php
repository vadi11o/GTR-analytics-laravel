<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TwitchTokenService;
use Illuminate\Support\Facades\Http;
use App\Models\Token;
use Mockery;

class GetTokenFromTwitchTest extends TestCase
{
    public function testGetTokenFromTwitch()
    {
        Http::fake([
            'id.twitch.tv/oauth2/token' => Http::response(['access_token' => 'mock_token'], 200),
        ]);

        $service = new TwitchTokenService();
        $token = $service->getTokenFromTwitch();

        $this->assertEquals('mock_token', $token);
    }


    protected function tearDown(): void
    {
        Mockery::close();
    }
}
