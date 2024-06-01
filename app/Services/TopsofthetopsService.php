<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use Exception;
use Illuminate\Http\Client\ConnectionException;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopsofthetopsService
{
    protected TwitchTokenProvider $twitchTokenService;
    protected TopVideoService $topVideosService;

    protected TopGamesService $topGamesService;
    protected DBClient $dbClient;

    public function __construct(DBClient $dbClient, TwitchTokenProvider $twitchTokenService, TopVideoService $topVideosService, TopGamesService $topGamesService)
    {
        $this->twitchTokenService = $twitchTokenService;
        $this->topVideosService   = $topVideosService;
        $this->topGamesService    = $topGamesService;
        $this->dbClient           = $dbClient;

    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function execute($since): void
    {
        $accessToken = $this->twitchTokenService->getTokenFromTwitch();
        if (!$accessToken) {
            throw new Exception('No se pudo obtener el token de Twitch.');
        }

        $this->topGamesService->execute();

        $this->dbClient->updateGamesSince($since, $this->topVideosService);
    }
}
