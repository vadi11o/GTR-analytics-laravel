<?php

namespace App\Http\Clients;

use App\Models\User;

class DBClient
{
    public function getUserByIdFromDB(String $userId)
    {
        return User::where('twitch_id', $userId)->first();
    }
    public function insertUserToDB(Array $userData)
    {
        User::create($userData);
    }
}
