<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use App\Providers\TwitchTokenProvider;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopVideoService
{
    protected TwitchTokenProvider $tokenProvider;
    protected DBClient $dbClient;
    protected TwitchManager $apiClient;

    public function __construct(DBClient $dbClient, TwitchManager $apiClient = null, TwitchTokenProvider $tokenProvider = null)
    {
        $this->tokenProvider = $tokenProvider ?? new TwitchTokenProvider();
        $this->apiClient     = $apiClient     ?? new TwitchManager($this->$tokenProvider);
        $this->dbClient      = $dbClient;
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

        $videos = $this->apiClient->updateVideos($accessToken, $gameId);

        if (empty($videos)) {
            throw new Exception('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        }

        $this->dbClient->saveVideos($videos, $gameId);
    }
}
