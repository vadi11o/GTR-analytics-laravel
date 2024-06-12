<?php

namespace App\Managers;

use App\Infrastructure\Clients\ApiClient;
use App\Providers\TwitchTokenProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */

class TwitchManager
{
    private TwitchTokenProvider $tokenProvider;
    private ApiClient $apiClient;

    public function __construct(TwitchTokenProvider $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
        $this->apiClient     = new ApiClient();
    }

    /**
     * @throws Exception
     */
    public function fetchStreamsFromTwitch(): array
    {
        $token    = $this->tokenProvider->getTokenFromTwitch();
        $response = $this->apiClient->httpFetchStreamsFromTwitch('/streams', $token);

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
        $token    = $this->tokenProvider->getTokenFromTwitch();
        $response = $this->apiClient->httpfetchStreamerDataFromTwitch($token, $streamerId);

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
                'created_at'        => Carbon::parse($streamerData['created_at'])->toDateTimeString(),
            ];
        }

        return ['error' => 'Failed to fetch data from Twitch', 'status_code' => $response->status()];
    }

    public function updateGames($accessToken)
    {
        $response = $this->apiClient->httpUpdateGames($accessToken);

        return $response->json()['data'] ?? [];
    }

    public function updateVideos($accessToken, $gameId)
    {
        $response = $this->apiClient->httpUpdateVideos($accessToken, $gameId);

        return $response->json()['data'] ?? [];
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function getStreamsByUserId($userId)
    {
        $token    = $this->tokenProvider->getTokenFromTwitch();
        $response = $this->apiClient->httpGetStreamsByUserId($token, [
            'user_id' => $userId,
            'first'   => 5,
        ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        return ['error' => 'Failed to fetch data from Twitch', 'status_code' => $response->status()];
    }
}
