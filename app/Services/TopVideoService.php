<?php

namespace App\Services;

use AllowDynamicProperties;
use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
#[AllowDynamicProperties] class TopVideoService
{
    protected TwitchTokenProvider $tokenProvider;

    protected ApiClient $apiClient;

    protected DBClient $dBClient;


    public function __construct(TwitchTokenProvider $tokenProvider, DBClient $dBClient)
    {
        $this->tokenProvider = $tokenProvider;
        $this->apiClient = $apiClient ?? new ApiClient($tokenProvider);
        $this->dBClient       = $dBClient;
    }

    /**
     * @throws Exception
     */
    public function execute($gameId): void
    {
        $accessToken = $this->tokenProvider->getTokenFromTwitch();
        if (!$accessToken) {
            throw new Exception('No se pudo obtener el token de Twitch.');
        }

        $videos = $this->apiClient->updateVideos($accessToken,$gameId);

        if (empty($videos)) {
            throw new Exception('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        }

        $this->dBClient->saveVideos($videos,$gameId);
    }
}
