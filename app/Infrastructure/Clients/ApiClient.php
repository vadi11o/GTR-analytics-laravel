<?php

namespace App\Infrastructure\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    private string $clientId;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = env('TWITCH_CLIENT_ID');
        $this->baseUrl  = env('TWITCH_URL');
    }

    /**
     * @throws ConnectionException
     */
    public function httpFetchStreamsFromTwitch($token)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => $this->clientId,
        ])->get($this->baseUrl . '/streams');
    }

    /**
     * @throws ConnectionException
     */
    public function httpfetchStreamerDataFromTwitch($token, $streamerId)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => $this->clientId,
        ])->get($this->baseUrl . '/users?id=' . $streamerId);
    }

    /**
     * @throws ConnectionException
     */
    public function httpUpdateGames($token)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => $this->clientId,
        ])->get($this->baseUrl . '/games/top?first=3');
    }

    /**
     * @throws ConnectionException
     */
    public function httpUpdateVideos($token, $gameId)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => $this->clientId,
        ])->get($this->baseUrl . "/videos?game_id=$gameId&first=40&sort=views");
    }

    /**
     * @throws ConnectionException
     */
    public function httpGetStreamsByUserId($token, $queryParams = [])
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => $this->clientId,
        ])->get($this->baseUrl . '/videos', $queryParams);
    }
}
