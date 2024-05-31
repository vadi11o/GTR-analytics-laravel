<?php

namespace App\Infrastructure\Controllers;

use Illuminate\Http\Client\ConnectionException;
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

    /**
     * @throws ConnectionException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $since = $request->query('since', 600);
        $this->topsOfTheTopsService->execute($since);
        $gameIds = TopGame::pluck('game_id');

        $topOfTheTopsData = TopOfTheTop::whereIn('game_id', $gameIds)->get();

        return response()->json(
            $topOfTheTopsData
        )->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
