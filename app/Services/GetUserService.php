<?php

namespace App\Services;

use App\Http\Clients\ApiClient;
use App\Http\Clients\DBClient;
use App\Models\User;

class GetUserService
{
    protected ApiClient $apiClient;
    protected DBClient $dbClient;
    protected TwitchTokenService $tokenService;
    public function __construct(DBClient $dbClient, ApiClient $apiClient, TwitchTokenService $tokenService)
    {
        $this->dbClient = $dbClient;
        $this->apiClient = $apiClient;
        $this->tokenService = $tokenService;
    }

    public function execute(String $userId){
        $token = $this->tokenService->getTokenFromTwitch();

        if (!$token) {
            return response()->json(['error' => 'No se pudo obtener el token de acceso desde la base de datos.'], 500);
        }

        $userData = $this->apiClient->fetchUserDataFromTwitch($token, $userId);

        if ($userData) {
            $this->dbClient->insertUserToDB($userData);
            return response()->json($userData)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            return response()->json(['error' => 'No se encontraron datos de usuario para el ID proporcionado.'], 404);
        }

    }
}
