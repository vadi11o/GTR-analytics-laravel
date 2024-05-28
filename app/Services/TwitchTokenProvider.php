<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class TwitchTokenProvider
{
    public function __construct()
    {

    }

    /**
     * @throws ConnectionException
     */
    public function getTokenFromTwitch(): String
    {
        $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
            'client_id'     => env('TWITCH_CLIENT_ID'),
            'client_secret' => env('TWITCH_CLIENT_SECRET'),
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
