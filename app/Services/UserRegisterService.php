<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use Illuminate\Http\JsonResponse;

class UserRegisterService
{
    protected DBClient $dBClient;

    public function __construct(DBClient $dBClient)
    {
        $this->dBClient = $dBClient;
    }

    public function execute(String $username, String $password): JsonResponse
    {
        $userExists = $this->dBClient->getUserAnalyticsByNameFromDB($username);
        if ($userExists) {
            return new JsonResponse(['message' => 'El nombre de usuario ya está en uso'], 409);
        }

        $dates = ['username' => $username, 'password' => $password];
        $this->dBClient->insertUserAnalyticsToDB($dates);

        return new JsonResponse(['username' => $username, 'message' => 'Usuario creado correctamente'], 201);
    }
}
