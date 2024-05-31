<?php

namespace App\Infrastructure\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Providers\TwitchTokenProvider;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class ApiClient
{
    private mixed $clientId;
    private TwitchTokenProvider $tokenProvider;

    public function __construct(TwitchTokenProvider $tokenProvider, $clientId = null)
    {
        $this->clientId     = $clientId     ?? env('TWITCH_CLIENT_ID');
        $this->tokenProvider = $tokenProvider;
    }

    public function sendCurlPetitionToTwitch($twitchStreamsUrl,$twitchToken): array
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
    public function fetchUserDataFromTwitch($userId): array
    {
        $token = $this->tokenProvider->getTokenFromTwitch();
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

    public function updateGames($accessToken)
    {
        $url = 'https://api.twitch.tv/helix/games/top?first=3';
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        $games = $response->json()['data'] ?? [];

        return $games;
    }

    public function updateVideos($accessToken,$gameId)
    {
        $url      = "https://api.twitch.tv/helix/videos?game_id={$gameId}&first=40&sort=views";
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        $videos = $response->json()['data'] ?? [];

        return $videos;
    }
}
