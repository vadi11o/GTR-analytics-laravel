<?php

namespace App\Infrastructure\Controllers;

use App\Http\Requests\TimelineRequest;
use App\Services\TimelineService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TimelineController extends Controller
{
    protected TimelineService $timelineService;

    public function __construct(TimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    public function __invoke(TimelineRequest $request): JsonResponse
    {
        $userId = $request->validated()['userId'];

        try {
            $timeline = $this->timelineService->execute($userId);

            return response()->json($timeline, 200);
        } catch (NotFoundHttpException) {
            return response()->json(['error' => 'El usuario especificado (userId: ' . $userId . ') no existe'], 404);
        } catch (Exception) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
