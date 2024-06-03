<?php

namespace App\Infrastructure\Controllers;

use App\Http\Requests\UnfollowRequest;
use App\Services\UnfollowStreamerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UnfollowStreamerController extends Controller
{
    protected UnfollowStreamerService $unfollowService;

    public function __construct(UnfollowStreamerService $unfollowService)
    {
        $this->unfollowService = $unfollowService;
    }

    public function __invoke(UnfollowRequest $request): JsonResponse
    {
        $userId     = $request->input('userId');
        $streamerId = $request->input('streamerId');

        try {
            return $this->unfollowService->execute($userId, $streamerId);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
