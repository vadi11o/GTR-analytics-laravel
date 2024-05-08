<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function show(Request $request):jsonResponse
    {
        $userId = $request->query('id');
        if (!$userId) {
            return response()->json(['error' => 'El parámetro "id" no se proporcionó en la URL.'], 400);
        }

        return $this->userService->execute($userId);
    }
}

