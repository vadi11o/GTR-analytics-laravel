<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TwitchTokenProvider
{
    private mixed $clientId;
    private mixed $clientSecret;

    public function __construct($clientId = null, $clientSecret = null)
    {
        $this->clientId     = $clientId     ?? env('TWITCH_CLIENT_ID');
        $this->clientSecret = $clientSecret ?? env('TWITCH_CLIENT_SECRET');
    }

    /**
     * @throws \Exception
     */
    public function getTokenFromTwitch(): string
    {
        $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'client_credentials',
        ]);

        if ($response->successful() && isset($response->json()['access_token'])) {
            return $response->json()['access_token'];
        }

        Log::warning('Failed to retrieve access token from Twitch', [
            'status'        => $response->status(),
            'response_body' => $response->json(),
        ]);

        throw new Exception('Failed to retrieve access token from Twitch: ' . $response->json()['error'] ?? 'Unknown error', $response->status());
    }
}
