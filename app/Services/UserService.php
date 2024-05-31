<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;

class UserService
{
    protected DBClient $dBClient;
    public function __construct(DBClient $dBClient)
    {
        $this->dBClient = $dBClient;
    }

    public function execute(String $username)
    {
        $userFromDB = $this->dBClient->getUserAnalyticsByNameFromDB($username);
        if ($userFromDB) {
            return $userFromDB;
        }
        return null;
    }
}
