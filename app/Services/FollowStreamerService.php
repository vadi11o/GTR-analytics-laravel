<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use Exception;
use Illuminate\Http\JsonResponse;

class FollowStreamerService
{
    protected DBClient $dBClient;
    protected TwitchManager $apiClient;

    public function __construct(DBClient $dBClient, TwitchManager $apiClient)
    {
        $this->dBClient  = $dBClient;
        $this->apiClient = $apiClient;
    }

    public function execute(string $userId, string $streamerId): JsonResponse
    {
        $userData = $this->getUserData($userId);
        if (!$userData) {
            return new JsonResponse(['message' => 'Usuario no encontrado'], 404);
        }

        $streamerData = $this->getStreamerData($streamerId);
        if ($streamerData instanceof JsonResponse) {
            return $streamerData;
        }

        return $this->followStreamer($userData, $streamerId, $streamerData);
    }

    private function getUserData(string $userId)
    {
        return $this->dBClient->getUserAnalyticsByIdFromDB($userId);
    }

    private function getStreamerData(string $streamerId): JsonResponse|array
    {
        try {
            return $this->apiClient->fetchStreamerDataFromTwitch($streamerId);
        } catch (Exception $e) {
            return $this->handleStreamerDataException($e);
        }
    }

    private function handleStreamerDataException(Exception $exception): JsonResponse
    {
        return match ($exception->getCode()) {
            401     => new JsonResponse(['error' => 'Token de autenticación no proporcionado o inválido'], 401),
            403     => new JsonResponse(['error' => 'Acceso denegado debido a permisos insuficientes'], 403),
            404     => new JsonResponse(['error' => 'Streamer especificado no existe en la API'], 404),
            default => new JsonResponse(['error' => 'Error del servidor al seguir al streamer'], 500),
        };
    }

    private function followStreamer($userData, string $streamerId, array $streamerData): JsonResponse
    {
        $followedStreamers = $this->decodeFollowedStreamers($userData->followed_streamers);

        if (!is_array($followedStreamers)) {
            return new JsonResponse(['message' => 'Error al procesar los streamers seguidos'], 500);
        }

        if ($this->isAlreadyFollowing($followedStreamers, $streamerId)) {
            return new JsonResponse(['message' => 'Ya sigues a este streamer'], 409);
        }

        $this->updateFollowedStreamers($userData, $followedStreamers, $streamerId, $streamerData);
        return new JsonResponse(['message' => 'Ahora sigues a '. $streamerId], 200);
    }

    private function decodeFollowedStreamers($followedStreamers)
    {
        return $followedStreamers ? json_decode($followedStreamers, true) : [];
    }

    private function isAlreadyFollowing(array $followedStreamers, string $streamerId): bool
    {
        foreach ($followedStreamers as $streamer) {
            if ($streamer['id'] == $streamerId) {
                return true;
            }
        }
        return false;
    }

    private function updateFollowedStreamers($userData, array &$followedStreamers, string $streamerId, array $streamerData): void
    {
        $followedStreamers[]          = ['id' => $streamerId, 'display_name' => $streamerData['display_name']];
        $userData->followed_streamers = json_encode($followedStreamers);
        $this->dBClient->updateUserAnalyticsInDB($userData);
    }
}
