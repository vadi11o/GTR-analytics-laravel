<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Models\TopGame;
use App\Providers\TwitchTokenProvider;
use Exception;
use Illuminate\Http\Client\ConnectionException;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopsofthetopsService
{
    protected $twitchTokenService;
    protected $topVideosService;

    protected $topGamesService;
    protected DBClient $dbClient;

    public function __construct(DBClient $dbClient = null, TwitchTokenProvider $twitchTokenService, TopVideoService $topVideosService, TopGamesService $topGamesService)
    {
        $this->twitchTokenService = $twitchTokenService;
        $this->topVideosService   = $topVideosService;
        $this->topGamesService    = $topGamesService;
        $this->dbClient  = $dbClient;

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

        $this->updateGamesSince($since);
    }

    /**
     * @throws Exception
     */
    public function updateGamesSince($since)
    {
        $games = TopGame::all();

        foreach ($games as $game) {
            if ($this->dbClient->needsUpdate($game->game_id, $since)) {
                $this->topVideosService->execute($game->game_id);
                $this->dbClient->updateTopForGame($game);
            }
        }
    }
}
