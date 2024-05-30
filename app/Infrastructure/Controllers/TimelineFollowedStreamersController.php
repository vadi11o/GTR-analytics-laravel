<?php

namespace App\Infrastructure\Controllers;

use App\Services\GetFollowedStreamers;
use Exception;
use Illuminate\Http\Request;

class TimelineFollowedStreamersController extends Controller
{
    private GetFollowedStreamers $getFollowedStreamers;
    private array $folowedStreamers;
    public function __construct(GetFollowedStreamers $getFollowedStreamers)
    {
        $this->getFollowedStreamers = $getFollowedStreamers;
    }
    public function __invoke($userId): \Illuminate\Http\JsonResponse
    {
        if (!$userId) {
            return response()->json(['error' => 'El parametro "id" no se proporciono en la URL.'], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        try {
            $this->folowedStreamers = $this->getFollowedStreamers->execute($userId);
            return response()->json($this->folowedStreamers);
        } catch (Exception) {
            $response = [
                'error' => 'No se puede devolver el timeline en este momento, inténtalo más tarde'
            ];
            return response()->json($response, 503);
        }
    }
}
