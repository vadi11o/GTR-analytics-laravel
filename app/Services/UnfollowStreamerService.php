<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\JsonResponse;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UnfollowStreamerService
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

        $followedStreamers = $userData->followed_streamers ? json_decode($userData->followed_streamers, true) : [];
        if (!is_array($followedStreamers)) {
            return new JsonResponse(['message' => 'Error al procesar los streamers seguidos'], 500);
        }

        $newList = [];
        $found   = false;
        foreach ($followedStreamers as $streamer) {
            if ($streamer['id'] == $streamerId) {
                $found = true;
                continue;
            }
            $newList[] = $streamer;
        }

        if (!$found) {
            return new JsonResponse(['message' => 'No sigues a este streamer'], 404);
        }

        $userData->followed_streamers = json_encode($newList);
        $this->dBClient->updateUserAnalyticsInDB($userData);

        return new JsonResponse(['message' => 'Dejaste de seguir a ' . $streamerId], 200);
    }
}
