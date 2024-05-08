<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;

class ApiClient
{
    public function sendCurlPetitionToTwitchForStreams($twitchStreamsUrl, $twitchToken, $twitchClientId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $twitchToken,
            'Client-Id' => $twitchClientId,
        ])->get($twitchStreamsUrl);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
    public function fetchUserDataFromTwitch($token, $userId)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Client-Id' => env('TWITCH_CLIENT_ID'),
        ])->get("https://api.twitch.tv/helix/users?id=$userId");

        if ($response->successful()) {
            $data = $response->json();
            $user = $data['data'][0] ?? null;
            if ($user) {
                return [
                    'twitch_id' => $user['id'],
                    'login' => $user['login'],
                    'display_name' => $user['display_name'],
                    'type' => $user['type'],
                    'broadcaster_type' => $user['broadcaster_type'],
                    'description' => $user['description'],
                    'profile_image_url' => $user['profile_image_url'],
                    'offline_image_url' => $user['offline_image_url'],
                    'view_count' => $user['view_count'],
                ];
            }
        }

        return null;
    }
}
