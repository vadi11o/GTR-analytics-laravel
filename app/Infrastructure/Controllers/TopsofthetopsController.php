<?php

namespace App\Infrastructure\Controllers;

use App\Http\Requests\TopsOfTheTopsRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TopsOfTheTopsService;
use App\Models\TopGame;
use App\Models\TopOfTheTop;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopsofthetopsController extends Controller
{
    protected TopsOfTheTopsService $topsOfTheTopsService;

    public function __construct(TopsOfTheTopsService $topsOfTheTopsService)
    {
        $this->topsOfTheTopsService = $topsOfTheTopsService;
    }

    /**
     * @throws ConnectionException
     */
    public function __invoke(TopsOfTheTopsRequest $request): JsonResponse
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
