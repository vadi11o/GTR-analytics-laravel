<?php

namespace App\Infrastructure\Clients;

use Illuminate\Support\Facades\Http;

class ApiClient
{
    private $clientId;
    private $clientSecret;

    public function __construct($clientId = null, $clientSecret = null)
    {
        $this->clientId = $clientId ?? env('TWITCH_CLIENT_ID');
        $this->clientSecret = $clientSecret ?? env('TWITCH_CLIENT_SECRET');
    }

    public function getTokenFromTwitch()
    {
        $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        return $response->json()['access_token'] ?? null;
    }

    public function sendCurlPetitionToTwitch($twitchStreamsUrl,$twitchToken)
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
}
