<?php

namespace App\Infrastructure\Controllers;

use App\Services\UnfollowStreamerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class UnfollowStreamerController extends Controller
{
    protected UnfollowStreamerService $unfollowStreamerService;

    public function __construct(UnfollowStreamerService $unfollowStreamerService)
    {
        $this->unfollowStreamerService = $unfollowStreamerService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $userId     = $request->input('userId');
        $streamerId = $request->input('streamerId');

        if (!$streamerId || !$userId) {
            return response()->json(['error' => 'Falta el ID del usuario o del streamer'], 400);
        }

        try {
            return $this->unfollowStreamerService->execute($userId, $streamerId);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
