<?php

namespace App\Infrastructure\Clients;

use App\Models\TopGame;
use App\Models\TopOfTheTop;
use App\Models\TopVideo;
use App\Models\User;
use Carbon\Carbon;


/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class DBClient
{

    public function getUserByIdFromDB(String $userId)
    {
        $user = User::where('twitch_id', $userId)->first();

        if ($user) {
            $user->makeHidden(['id']);

            $user  = $user->toArray();
            $newId = $user['twitch_id'];
            unset($user['twitch_id']);

            return ['id' => $newId] + $user;
        }
        return null;
    }
    public function insertUserToDB(array $userData): void
    {
        User::create($userData);
    }

    public function needsUpdate($gameId, $since): bool
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
        $userName = $mostViewedVideo->user_name;

        $userVideos = TopVideo::where('user_name', $userName)->get();

        $totalViews      = $userVideos->sum('views');
        $totalVideos     = $userVideos->count();

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

    public function saveVideos($videos, $gameId): void
    {
        TopVideo::truncate();

        foreach ($videos as $video) {

            TopVideo::  create([
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
