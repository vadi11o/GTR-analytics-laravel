<?php

namespace App\Infrastructure\Controllers;

use App\Services\GetStreamsService;
use Exception;
use Illuminate\Http\JsonResponse;

class GetStreamsController extends Controller
{
    private GetStreamsService $streamsService;

    public function __construct(GetStreamsService $streamsService)
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
