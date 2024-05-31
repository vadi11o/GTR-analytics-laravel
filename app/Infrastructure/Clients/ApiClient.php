<?php

namespace App\Infrastructure\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Providers\TwitchTokenProvider;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class ApiClient
{
    private mixed $clientId;
    private TwitchTokenProvider $tokenProvider;

    public function __construct(TwitchTokenProvider $tokenProvider, $clientId = null)
    {
        $this->clientId      = $clientId ?? env('TWITCH_CLIENT_ID');
        $this->tokenProvider = $tokenProvider;
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

    public function fetchUserDataFromTwitch($userId): array
    {
        $token = $this->tokenProvider->getTokenFromTwitch();
        $url   = 'https://api.twitch.tv/helix/users?id=' . $userId;

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
}
