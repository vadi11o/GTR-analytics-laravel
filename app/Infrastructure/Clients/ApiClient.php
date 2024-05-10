<?php

namespace App\Infrastructure\Clients;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;


class ApiClient
{
    private $clientId;
    private $clientSecret;

    public function __construct($clientId = null, $clientSecret = null)
    {
        $this->clientId = $clientId ?? env('TWITCH_CLIENT_ID');
        $this->clientSecret = $clientSecret ?? env('TWITCH_CLIENT_SECRET');
    }

    public function sendCurlPetitionToTwitch($twitchStreamsUrl, $twitchToken)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $twitchToken,
            'Client-Id' => $this->clientId,
        ])->get($twitchStreamsUrl);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }

    public function getTokenFromTwitch()
    {
        $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        return $response->json()['access_token'];
    }


    public function fetchUserDataFromTwitch($token, $userId)
    {
        $url = "https://api.twitch.tv/helix/users?id=" . $userId;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id' => $this->clientId,
        ])->get($url);

        if ($response->successful()) {
            $userData = $response->json()['data'][0];

            return [
                "twitch_id" => $userData['id'],
                "login" => $userData['login'],
                "display_name" => $userData['display_name'],
                "type" => $userData['type'],
                "broadcaster_type" => $userData['broadcaster_type'],
                "description" => $userData['description'],
                "profile_image_url" => $userData['profile_image_url'],
                "offline_image_url" => $userData['offline_image_url'],
                "view_count" => $userData['view_count'],
                "created_at" => Carbon::parse($userData['created_at'])->toDateTimeString()
            ];
        } else {
            return ['error' => 'Failed to fetch data from Twitch', 'status_code' => $response->status()];
        }
    }
}
