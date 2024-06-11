<?php

namespace App\Infrastructure\Controllers;

use App\Http\Requests\FollowRequest;
use App\Services\FollowStreamerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class FollowStreamerController extends Controller
{
    protected FollowStreamerService $followService;

    public function __construct(FollowStreamerService $followService)
    {
        $this->followService = $followService;
    }

    public function __invoke(FollowRequest $request): JsonResponse
    {
        $userId     = $request->input('userId');
        $streamerId = $request->input('streamerId');

        try {
            return $this->followService->execute($userId, $streamerId);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
