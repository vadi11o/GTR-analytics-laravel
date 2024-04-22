<?php

namespace Tests\Unit;

use App\Services\TwitchTokenService;
use PHPUnit\Framework\TestCase;

class AnalyticsTest extends TestCase
{
    /**
     * @test
     */
    public function twitch_token_from_twitch_is_available(): void
    {
        $tokenService = new TwitchTokenService();

        $token = $tokenService->getTokenFromTwitch();

        $this->assertNotNull($token);
    }

}
