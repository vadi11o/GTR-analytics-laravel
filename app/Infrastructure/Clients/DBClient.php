<?php

namespace App\Infrastructure\Clients;

use App\Models\User;
use App\Services\TokenProvider;
use Illuminate\Support\Facades\Http;
use Exception;

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

}
