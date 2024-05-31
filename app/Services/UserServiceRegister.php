<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;

class UserServiceRegister
{
    protected DBClient $dBClient;
    public function __construct(DBClient $dBClient)
    {
        $this->dBClient = $dBClient;
    }

    public function execute(String $username, String $password)
    {
        $datos = ['username' => $username, 'password' => $password];
        $this->dBClient->insertUserAnalyticsToDB($datos);
    }
}
