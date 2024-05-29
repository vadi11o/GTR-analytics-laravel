<?php

namespace App\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TopsofthetopsService;
use App\Models\TopGame;
use App\Models\TopOfTheTop;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopsofthetopsController extends Controller
{
    protected TopsofthetopsService $topsOfTheTopsService;

    public function __construct(TopsofthetopsService $topsOfTheTopsService)
    {
        $this->topsOfTheTopsService = $topsOfTheTopsService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $since = $request->query('since', 600);
        $this->topsOfTheTopsService->updateTopOfTheTops($since);
        $gameIds = TopGame::pluck('game_id');

        $topOfTheTopsData = TopOfTheTop::whereIn('game_id', $gameIds)->get();

        return response()->json([
            'data' => $topOfTheTopsData
        ])->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
