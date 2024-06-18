<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use App\Providers\TwitchTokenProvider;

class GetStreamerService
{
    protected TwitchManager $apiClient;
    protected DBClient $dbClient;
    private TwitchTokenProvider $tokenProvider;

    public function __construct(DBClient $dbClient, TwitchManager $apiClient = null, TwitchTokenProvider $tokenProvider = null)
    {
        $this->tokenProvider = $tokenProvider ?? new TwitchTokenProvider();
        $this->apiClient     = $apiClient     ?? new TwitchManager($this->$tokenProvider);
        $this->dbClient      = $dbClient;
    }

    /**
     * @throws \Exception
     */
    public function execute(String $streamerId)
    {
        $streamerData = $this->apiClient->fetchStreamerDataFromTwitch($streamerId);

        if ($streamerData) {
            $this->dbClient->insertStreamerToDB($streamerData);
            if (isset($streamerData['twitch_id'])) {
                $streamerData['id'] = $streamerData['twitch_id'];
                unset($streamerData['twitch_id']);
            }
            $streamerData = ['id' => $streamerData['id']] + $streamerData;

            return response()->json($streamerData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return response()->json(['error' => 'No se encontraron datos de usuario para el ID proporcionado.'], 404);
    }
}
