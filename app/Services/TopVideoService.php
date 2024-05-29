<?php

namespace App\Services;

use App\Models\TopVideo;
use App\Providers\TwitchTokenProvider;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopVideoService
{
    protected $twitchTokenService;

    public function __construct(TwitchTokenProvider $twitchTokenService)
    {
        $this->twitchTokenService = $twitchTokenService;
    }

    public function updateTopVideos($gameId)
    {
        $accessToken = $this->twitchTokenService->getTokenFromTwitch();
        if (!$accessToken) {
            throw new Exception('No se pudo obtener el token de Twitch.');
        }

        $url      = "https://api.twitch.tv/helix/videos?game_id={$gameId}&first=40&sort=views";
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Client-Id'     => env('TWITCH_CLIENT_ID'),
        ])->get($url);

        $videos = $response->json()['data'] ?? [];

        if (empty($videos)) {
            throw new Exception('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        }

        TopVideo::truncate();

        foreach ($videos as $video) {

            TopVideo::create([
                'video_id'   => $video['id'],
                'game_id'    => $gameId,
                'title'      => $video['title'],
                'views'      => $video['view_count'],
                'user_name'  => $video['user_name'],
                'duration'   => $video['duration'],
                'created_at' => $video['created_at'],
            ]);
        }
    }
}
