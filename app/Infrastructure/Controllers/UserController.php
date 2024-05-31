<?php

namespace App\Infrastructure\Controllers;

use App\Services\UserDataManager;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserDataManager $userDataManager;

    public function __construct(UserDataManager $userDataManager)
    {
        $this->userDataManager = $userDataManager;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->query('id');
        if (!$userId) {
            return response()->json(['error' => 'El parametro "id" no se proporciono en la URL.'], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        try {
            $result = $this->userDataManager->execute($userId);
            return response()->json($result->getData(), $result->status(), [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Exception) {
            $response = [
                'error' => 'No se pueden devolver usuarios en este momento, intentalo mas tarde'
            ];
            return response()->json($response, 503, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }
}
