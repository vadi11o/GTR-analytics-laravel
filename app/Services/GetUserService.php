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
        $this->dbClient  = $dbClient;
        $this->apiClient = $apiClient;
    }

    public function execute(String $userId)
    {
        $userData = $this->apiClient->fetchUserDataFromTwitch($userId);

        if ($userData) {
            $this->dbClient->insertUserToDB($userData);
            if (isset($userData['twitch_id'])) {
                $userData['id'] = $userData['twitch_id'];
                unset($userData['twitch_id']);
            }
            $userData = ['id' => $userData['id']] + $userData;

            return response()->json($userData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return response()->json(['error' => 'No se encontraron datos de usuario para el ID proporcionado.'], 404);
    }
}
