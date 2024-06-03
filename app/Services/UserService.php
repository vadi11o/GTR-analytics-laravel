<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UserService
{
    protected DBClient $dBClient;

    public function __construct(DBClient $dBClient)
    {
        $this->dBClient = $dBClient;
    }

    public function execute(): JsonResponse
    {
        try {
            $users = $this->dBClient->getAllUsersFromDB();
            $result = [];

            foreach ($users as $user) {
                $followedStreamers = $user->followed_streamers ? json_decode($user->followed_streamers, true) : [];
                $streamerNames = array_map(function($streamer) {
                    return $streamer['display_name'];
                }, $followedStreamers);

                $result[] = [
                    'username' => $user->username,
                    'followedStreamers' => $streamerNames
                ];
            }

            return response()->json($result, 200, [], JSON_PRETTY_PRINT| JSON_UNESCAPED_SLASHES);
        } catch (Exception) {
            return new JsonResponse(['message' => 'Error del servidor al obtener la lista de usuarios'], 500);
        }
    }
}
