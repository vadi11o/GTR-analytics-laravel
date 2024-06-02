<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\JsonResponse;

class FollowStreamerService
{
    protected DBClient $dBClient;
    protected ApiClient $apiClient;

    public function __construct(DBClient $dBClient, ApiClient $apiClient)
    {
        $this->dBClient  = $dBClient;
        $this->apiClient = $apiClient;
    }

    public function execute(String $userId, String $streamerId): JsonResponse
    {
        $userData = $this->dBClient->getUserAnalyticsByIdFromDB($userId);
        if (!$userData) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], 404);
        }

        try {
            $streamerData = $this->apiClient->fetchStreamerDataFromTwitch($streamerId);
        } catch (\Exception $e) {
            if ($e->getCode() === 401) {
                return new JsonResponse(['message' => 'Token de autenticación no proporcionado o inválido'], 401);
            } if ($e->getCode() === 403) {
                return new JsonResponse(['message' => 'Acceso denegado debido a permisos insuficientes'], 403);
            } if ($e->getCode() === 404) {
                return new JsonResponse(['message' => 'Streamer especificado no existe en la API'], 404);
            } else {
                return new JsonResponse(['message' => 'Error del servidor al seguir al streamer'], 500);
            }
        }

        $followedStreamers = $userData->followed_streamers ? json_decode($userData->followed_streamers, true) : [];
        if (!is_array($followedStreamers)) {
            return new JsonResponse(['message' => 'Error al procesar los streamers seguidos'], 500);
        }
        foreach ($followedStreamers as $streamer) {
            if ($streamer['id'] == $streamerId) {
                return new JsonResponse(['message' => 'Ya sigues a este streamer'], 409);
            }
        }

        $followedStreamers[]          = ['id' => $streamerId, 'display_name' => $streamerData['display_name']];
        $userData->followed_streamers = $followedStreamers;
        $this->dBClient->updateUserAnalyticsInDB($userData);

        return new JsonResponse(['message' => 'Ahora sigues a '. $streamerId], 200);
    }
}
