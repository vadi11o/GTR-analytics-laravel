<?php

namespace App\Infrastructure\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Providers\TwitchTokenProvider;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class ApiClient
{
    private TwitchTokenProvider $tokenProvider;

    public function __construct(TwitchTokenProvider $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * @throws Exception
     */
    public function fetchStreamsFromTwitch(): array
    {
        $token    = $this->tokenProvider->getTokenFromTwitch();
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get(env('TWITCH_URL') . '/streams');

        return [
            'status' => $response->status(),
            'body'   => $response->body(),
        ];
    }

    /**
     * @throws Exception
     */
    public function fetchStreamerDataFromTwitch($streamerId): array
    {
        $token = $this->tokenProvider->getTokenFromTwitch();
        $url   = env('TWITCH_URL') . '/users?id=' . $streamerId;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        if ($response->successful()) {
            $streamerData = $response->json()['data'][0];

            return [
                'twitch_id'         => $streamerData['id'],
                'login'             => $streamerData['login'],
                'display_name'      => $streamerData['display_name'],
                'type'              => $streamerData['type'],
                'broadcaster_type'  => $streamerData['broadcaster_type'],
                'description'       => $streamerData['description'],
                'profile_image_url' => $streamerData['profile_image_url'],
                'offline_image_url' => $streamerData['offline_image_url'],
                'view_count'        => $streamerData['view_count'],
                'created_at'        => Carbon::parse($streamerData['created_at'])->toDateTimeString()
            ];
        }
        return ['error' => 'Failed to fetch data from Twitch', 'status_code' => $response->status()];
    }

    /**
     * @throws ConnectionException
     */
    public function updateGames($accessToken)
    {
        $url      = env('TWITCH_URL') . '/games/top?first=3';
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        return $response->json()['data'] ?? [];
    }

    /**
     * @throws ConnectionException
     */
    public function updateVideos($accessToken, $gameId)
    {
        $url      = env('TWITCH_URL') . "/videos?game_id=$gameId&first=40&sort=views";
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        return $response->json()['data'] ?? [];
    }
}
