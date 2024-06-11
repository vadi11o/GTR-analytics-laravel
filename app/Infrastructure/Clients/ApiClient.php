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
    public function get($url, $token, $queryParams = [])
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id'     => $this->clientId,
        ])->get($this->baseUrl . $url, $queryParams);
    }
}
