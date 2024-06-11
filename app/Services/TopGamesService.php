<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use App\Providers\TwitchTokenProvider;
use Exception;
use Illuminate\Http\Client\ConnectionException;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopGamesService
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
     * @throws ConnectionException
     * @throws Exception
     */
    public function execute(): void
    {
        $accessToken = $this->tokenProvider->getTokenFromTwitch();
        if (!$accessToken) {
            throw new Exception('No se pudo obtener el token de Twitch.');
        }

        $games = $this->apiClient->updateGames($accessToken);

        if (empty($games)) {
            throw new Exception('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        }

        $this->dbClient->saveGames($games);
    }
}
