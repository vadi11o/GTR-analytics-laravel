<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GetStreamsService
{
    protected $twitchTokenService;
    public function __construct(TwitchTokenService $twitchTokenService)
    {
        $this->twitchTokenService = $twitchTokenService;
    }
    public function execute()
    {
        $token = $this->twitchTokenService->getTokenFromTwitch();

        $url = env('TWITCH_URL') . '/streams';
        $response = $this->curlPetition($url, $token, env('TWITCH_CLIENT_ID'));

        return ($response['body']);
    }

    public function curlPetition($url, $token, $client_id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id' => $client_id,
        ])->get($url);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
