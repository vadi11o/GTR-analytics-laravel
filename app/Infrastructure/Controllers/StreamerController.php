<?php

namespace App\Infrastructure\Controllers;

use App\Http\Requests\StreamerRequest;
use App\Managers\StreamerDataManager;
use Exception;
use Illuminate\Http\JsonResponse;

class StreamerController extends Controller
{
    protected StreamerDataManager $streamerDataManager;

    public function __construct(StreamerDataManager $streamerDataManager)
    {
        $this->streamerDataManager = $streamerDataManager;
    }

    public function __invoke(StreamerRequest $request): JsonResponse
    {
        $streamerId = $request->query('id');

        try {
            $result = $this->streamerDataManager->execute($streamerId);
            return response()->json($result->getData(), $result->status(), [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Exception) {
            $response = [
                'error' => 'No se pueden devolver streamers en este momento, intentalo mas tarde'
            ];
            return response()->json($response, 503, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }
}
