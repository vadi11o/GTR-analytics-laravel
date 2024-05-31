<?php

namespace App\Services;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use Illuminate\Http\Client\ConnectionException;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopGamesService
{
    protected TwitchTokenProvider $tokenProvider;
    protected ApiClient $apiClient;
    protected DBClient $dBClient;



    public function __construct(ApiClient $apiClient = null,TwitchTokenProvider $tokenProvider, DBClient $dBClient)
    {
        $this->tokenProvider = $tokenProvider;
        $this->apiClient = $apiClient ?? new ApiClient($tokenProvider);
        $this->dBClient       = $dBClient;
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

        $this->dBClient->saveGames($games);
    }
}
