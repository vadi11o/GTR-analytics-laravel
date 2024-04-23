<?php

namespace App\Services;

use App\Models\TopGame;
use App\Models\TopOfTheTop;
use App\Models\TopVideo;
use Carbon\Carbon;

class TopsofthetopsService
{
    protected $twitchTokenService;
    protected $topVideosService;

    protected $topGamesService;
    public function __construct(TwitchTokenService $twitchTokenService, TopVideoService $topVideosService, TopGamesService $topGamesService)
    {
        $this->twitchTokenService = $twitchTokenService;
        $this->topVideosService = $topVideosService;
        $this->topGamesService = $topGamesService;
    }

    public function updateTopOfTheTops($since)
    {
        $accessToken = $this->twitchTokenService->getTokenFromTwitch();
        if (!$accessToken) {
            throw new \Exception("No se pudo obtener el token de Twitch.");
        }

        $this->topGamesService->updateTopGames();

        $games = TopGame::all();

        foreach ($games as $game) {
            if ($this->needsUpdate($game->game_id, $since)) {
                $this->topVideosService->updateTopVideos($game->game_id);
                $this->updateTopForGame($game);
            }
        }
    }

    protected function needsUpdate($gameId, $since)
    {
        $topOfTheTop = TopOfTheTop::find($gameId);

        if (!$topOfTheTop || !$topOfTheTop->ultima_actualizacion) {
            return true;
        }

        $lastUpdate = Carbon::parse($topOfTheTop->ultima_actualizacion);
        $now = Carbon::now();

        return $now->diffInSeconds($lastUpdate) > $since;
    }

    protected function updateTopForGame($game)
    {
        $videos = TopVideo::where('game_id', $game->game_id)
            ->orderByDesc('views')
            ->get();

        if ($videos->isEmpty()) {
            return;
        }

        $totalViews = $videos->sum('views');
        $totalVideos = $videos->count();
        $mostViewedVideo = $videos->first();

        $topOfTheTop = TopOfTheTop::updateOrCreate(
            ['game_id' => $game->game_id],
            [
                'game_name' => $game->game_name,
                'user_name' => $mostViewedVideo->user_name,
                'total_videos' => $totalVideos,
                'total_views' => $totalViews,
                'most_viewed_title' => $mostViewedVideo->title,
                'most_viewed_views' => $mostViewedVideo->views,
                'most_viewed_duration' => $mostViewedVideo->duration,
                'most_viewed_created_at' => $mostViewedVideo->created_at,
                'ultima_actualizacion' => Carbon::now()
            ]
        );
    }
}
