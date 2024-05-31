<?php

namespace App\Services;

use App\Models\TopGame;
use App\Providers\TwitchTokenProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopGamesService
{
    protected $twitchTokenService;

    public function __construct(TwitchTokenProvider $twitchTokenService)
    {
        $this->twitchTokenService = $twitchTokenService;
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function execute(): void
    {
        $accessToken = $this->twitchTokenService->getTokenFromTwitch();
        if (!$accessToken) {
            throw new Exception('No se pudo obtener el token de Twitch.');
        }

        $games = $this->updateGames($accessToken);

        if (empty($games)) {
            throw new Exception('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        }

        $this->saveGames($games);
    }


    public function updateGames($accessToken)
    {
        $url = 'https://api.twitch.tv/helix/games/top?first=3';
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        $games = $response->json()['data'] ?? [];

        return $games;
    }

    public function saveGames($games): void
    {
        TopGame::truncate();

        foreach ($games as $game) {
            TopGame::create([
                'game_id'   => $game['id'],
                'game_name' => $game['name'],
            ]);
        }
    }
}
