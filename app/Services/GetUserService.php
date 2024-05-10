<?php

namespace App\Services;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use Illuminate\Http\JsonResponse;

class GetUserService
{
    protected ApiClient $apiClient;
    protected DBClient $dbClient;

    public function __construct(DBClient $dbClient, ApiClient $apiClient)
    {
        $this->dbClient = $dbClient;
        $this->apiClient = $apiClient;
    }

    public function execute(String $userId)
    {
        $tokenFromTwitch = $this->dbClient->getTokenFromTwitch();

        if (!$tokenFromTwitch) {
            return response()->json(['error' => 'No se pudo obtener el token de acceso desde Twitch.'], 500);
        }

        $userData = $this->apiClient->fetchUserDataFromTwitch($tokenFromTwitch, $userId);

        if ($userData) {
            $this->dbClient->insertUserToDB($userData);
            if (isset($userData['twitch_id'])) {
                $userData['id'] = $userData['twitch_id'];
                unset($userData['twitch_id']);
            }
            $userData = ['id' => $userData['id']] + $userData;

            return response()->json($userData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            return response()->json(['error' => 'No se encontraron datos de usuario para el ID proporcionado.'], 404);
        }
    }
}
