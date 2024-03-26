<?php

namespace App\Services;

use App\Models\TopGame;
use Illuminate\Support\Facades\Http;

class TopGamesService
{
    protected $twitchTokenService;

    public function __construct(TwitchTokenService $twitchTokenService)
    {
        $this->twitchTokenService = $twitchTokenService;
    }

    public function updateTopGames()
    {
        $accessToken = $this->twitchTokenService->getTokenFromTwitch();
        if (!$accessToken) {
            throw new \Exception("No se pudo obtener el token de Twitch.");
        }

        $url = 'https://api.twitch.tv/helix/games/top?first=3'; // Ajusta el parámetro 'first' según necesites
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Client-Id' => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        $games = $response->json()['data'] ?? [];

        if (empty($games)) {
            throw new \Exception("No se encontraron datos válidos en la respuesta de la API de Twitch.");
        }

        // Vaciar la tabla topGames antes de insertar los nuevos datos
        TopGame::truncate();

        foreach ($games as $game) {
            TopGame::create([
                'game_id' => $game['id'], // Asegúrate de que tu modelo TopGame y la migración correspondiente estén configurados para aceptar este campo
                'game_name' => $game['name'],
            ]);
        }
    }
}
