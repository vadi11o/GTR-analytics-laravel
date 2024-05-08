<?php

namespace App\Services;

use App\Http\Clients\ApiClient;

class GetStreamsService
{
    protected $twitchTokenService;
    protected $apiClient;
    public function __construct(TwitchTokenService $twitchTokenService)
    {
        $this->twitchTokenService = $twitchTokenService;
        $this->apiClient = new ApiClient();
    }
    public function execute()
    {
        $twitchStreamsUrl = env('TWITCH_URL') . '/streams';
        $tokenFromTwitch = $this->twitchTokenService->getTokenFromTwitch();

        $streamsResponse = $this->apiClient->sendCurlPetitionToTwitchForStreams($twitchStreamsUrl, $tokenFromTwitch, env('TWITCH_CLIENT_ID'));

        return ($streamsResponse['body']);
    }
}
