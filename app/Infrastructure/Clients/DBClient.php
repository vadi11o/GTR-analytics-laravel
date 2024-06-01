<?php

namespace App\Infrastructure\Clients;

use App\Models\TopOfTheTop;
use App\Models\TopVideo;
use App\Models\User;
use App\Models\UserAnalytics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

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

    public function needsUpdate($gameId, $since)
    {
        $topOfTheTop = TopOfTheTop::find($gameId);

        if (!$topOfTheTop || !$topOfTheTop->ultima_actualizacion) {
            return true;
        }

        $lastUpdate = Carbon::parse($topOfTheTop->ultima_actualizacion);
        $now        = Carbon::now();

        return $now->diffInSeconds($lastUpdate) > $since;
    }

    public function updateTopForGame($game)
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
}
