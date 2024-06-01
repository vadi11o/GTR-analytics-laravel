<?php

namespace App\Infrastructure\Controllers;

use App\Services\FollowStreamerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class FollowStreamerController extends Controller
{
    protected FollowStreamerService $followStreamerService;

    public function __construct(FollowStreamerService $followStreamerService)
    {
        $this->followStreamerService = $followStreamerService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $userId     = $request->input('userId');
        $streamerId = $request->input('streamerId');

        if (!$streamerId || !$userId) {
            return response()->json(['error' => 'Falta el ID del  del streamer'], 400);
        }

        try {
            return $this->followStreamerService->execute($userId, $streamerId);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
