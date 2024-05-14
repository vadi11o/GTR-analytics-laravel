<?php

namespace App\Infrastructure\Controllers;

use App\Services\StreamsDataManager;
use Exception;
use Illuminate\Http\JsonResponse;

class StreamsController extends Controller
{
    private StreamsDataManager $streamsService;

    public function __construct(StreamsDataManager $streamsService)
    {
        $this->streamsService = $streamsService;
    }

    public function __invoke(): JsonResponse
    {
        try {
            return $this->streamsService->execute();
        } catch (Exception) {
            $response = [
                'error' => 'No se pueden devolver streams en este momento, inténtalo más tarde'
            ];
            return response()->json($response, 503);
        }
    }
}
