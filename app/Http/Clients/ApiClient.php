<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;

class ApiClient
{
    public function sendCurlPetitionToTwitch($twitchStreamsUrl, $twitchToken, $twitchClientId)
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
}
