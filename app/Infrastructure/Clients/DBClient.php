<?php

namespace App\Infrastructure\Clients;

use App\Models\TopGame;
use App\Models\TopOfTheTop;
use App\Models\TopVideo;
use App\Models\User;
use App\Models\UserAnalytics;
use Carbon\Carbon;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class DBClient
{
    public function getStreamerByIdFromDB(String $streamerId)
    {
        $streamer = User::where('twitch_id', $streamerId)->first();

        if ($streamer) {
            $streamer->makeHidden(['id']);

            $streamer = $streamer->toArray();
            $newId    = $streamer['twitch_id'];
            unset($streamer['twitch_id']);

            return ['id' => $newId] + $streamer;
        }
        return null;
    }
    public function insertStreamerToDB(array $streamerData): void
    {
        User::create($streamerData);
    }

    public function getUserAnalyticsByNameFromDB(String $userName)
    {
        $userAnalytics = UserAnalytics::where('username', $userName)->first();
        if ($userAnalytics) {
            return $userAnalytics;
        }
        return false;
    }
    public function insertUserAnalyticsToDB(array $userData): void
    {
        UserAnalytics::create($userData);
    }

<<<<<<< HEAD
    public function needsUpdate($gameId, $since)
=======
    public function needsUpdate($gameId, $since): bool
>>>>>>> master
    {
        $topOfTheTop = TopOfTheTop::find($gameId);

        if (!$topOfTheTop || !$topOfTheTop->ultima_actualizacion) {
            return true;
        }

        $lastUpdate = Carbon::parse($topOfTheTop->ultima_actualizacion);
        $now        = Carbon::now();

        return $now->diffInSeconds($lastUpdate) > $since;
    }

    public function updateTopForGame($game): void
    {
        $videos = TopVideo::where('game_id', $game->game_id)
            ->orderByDesc('views')
            ->get();

        if ($videos->isEmpty()) {
            return;
        }

        $mostViewedVideo = $videos->first();
        $userName        = $mostViewedVideo->user_name;

        $userVideos = TopVideo::where('user_name', $userName)->get();

        $totalViews  = $userVideos->sum('views');
        $totalVideos = $userVideos->count();

        TopOfTheTop::updateOrCreate(
            ['game_id' => $game->game_id],
            [
                'game_name'              => $game->game_name,
                'user_name'              => $userName,
                'total_videos'           => $totalVideos,
                'total_views'            => $totalViews,
                'most_viewed_title'      => $mostViewedVideo->title,
                'most_viewed_views'      => $mostViewedVideo->views,
                'most_viewed_duration'   => $mostViewedVideo->duration,
                'most_viewed_created_at' => $mostViewedVideo->created_at,
                'ultima_actualizacion'   => Carbon::now()
            ]
        );
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

    public function saveVideos($videos, $gameId): void
    {
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

    public function updateGamesSince($since,$topVideosService): void
    {
        $games = TopGame::all();

        foreach ($games as $game) {
            if ($this->needsUpdate($game->game_id, $since)) {
                $topVideosService->execute($game->game_id);
                $this->updateTopForGame($game);
            }
        }
    }
}
