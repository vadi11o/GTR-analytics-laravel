<?php

namespace App\Infrastructure\Clients;

use App\Models\User;
use App\Models\UserAnalytics;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class DBClient
{
    public function __construct()
    {
    }
    public function getUserByIdFromDB(String $userId)
    {
        $user = User::where('twitch_id', $userId)->first();

        if ($user) {
            $user->makeHidden(['id']);

            $user  = $user->toArray();
            $newId = $user['twitch_id'];
            unset($user['twitch_id']);

            $user = ['id' => $newId] + $user;

            return $user;
        }
        return null;
    }
    public function insertUserToDB(array $userData): void
    {
        User::create($userData);
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

}
