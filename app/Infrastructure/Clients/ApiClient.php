<?php

namespace App\Infrastructure\Clients;

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
    public function fetchUserDataFromTwitch($userId): array
    {
        $token = $this->tokenProvider->getTokenFromTwitch();
        $url   = env('TWITCH_URL') . '/users?id=' . $userId;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
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
