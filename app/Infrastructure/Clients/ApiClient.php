<?php

namespace App\Infrastructure\Clients;

use App\Services\TokenProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class ApiClient
{
    protected TokenProvider $tokenprovider;
    public function __construct(TokenProvider $tokenProvider)
    {
        $this->tokenprovider = $tokenProvider;
    }

    public function sendCurlPetitionToTwitch($twitchStreamsUrl): array
    {
        $twitchToken = $this->tokenprovider->getTokenFromTwitch();
        $response    = Http::withHeaders([
            'Authorization' => 'Bearer ' . $twitchToken,
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($twitchStreamsUrl);

        return [
            'status' => $response->status(),
            'body'   => $response->body(),
        ];
    }

    /**
     */
    public function fetchUserDataFromTwitch($userId): array
    {
        $url = 'https://api.twitch.tv/helix/users?id=' . $userId;

        $token = $this->tokenprovider->getTokenFromTwitch();

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
    public function fetchUserFollowedStreamers($twitchStreamsUrl): array
    {
        $twitchToken = $this->tokenprovider->getTokenFromTwitch();
        $response    = Http::withHeaders([
            'Authorization' => 'Bearer ' . $twitchToken,
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($twitchStreamsUrl);

        return [
            'status' => $response->status(),
            'body'   => $response->body(),
        ];
    }
}
