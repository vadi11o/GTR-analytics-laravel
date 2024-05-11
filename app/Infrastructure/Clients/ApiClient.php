<?php

namespace App\Infrastructure\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class ApiClient
{
    private mixed $clientId;
    private mixed $clientSecret;

    public function __construct($clientId = null, $clientSecret = null)
    {
        $this->clientId     = $clientId     ?? env('TWITCH_CLIENT_ID');
        $this->clientSecret = $clientSecret ?? env('TWITCH_CLIENT_SECRET');
    }

    public function sendCurlPetitionToTwitch($twitchStreamsUrl, $twitchToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $twitchToken,
            'Client-Id'     => $this->clientId,
        ])->get($twitchStreamsUrl);

        return [
            'status' => $response->status(),
            'body'   => $response->body(),
        ];
    }

    /**
     * @throws ConnectionException
     * @throws \Exception
     */
    public function getTokenFromTwitch()
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

    /**
     * @throws ConnectionException
     */
    public function fetchUserDataFromTwitch($token, $userId): array
    {
        $url = 'https://api.twitch.tv/helix/users?id=' . $userId;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => $this->clientId,
        ])->get($url);

        if ($response->successful()) {
            $userData = $response->json()['data'][0];

            return [
                'twitch_id'         => $userData['id'],
                'login'             => $userData['login'],
                'display_name'      => $userData['display_name'],
                'type'              => $userData['type'],
                'broadcaster_type'  => $userData['broadcaster_type'],
                'description'       => $userData['description'],
                'profile_image_url' => $userData['profile_image_url'],
                'offline_image_url' => $userData['offline_image_url'],
                'view_count'        => $userData['view_count'],
                'created_at'        => Carbon::parse($userData['created_at'])->toDateTimeString()
            ];
        }

        return ['error' => 'Failed to fetch data from Twitch', 'status_code' => $response->status()];

    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }
}
