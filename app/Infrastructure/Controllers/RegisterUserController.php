<?php

namespace App\Infrastructure\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\UserRegisterService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterUserController extends Controller
{
    protected UserRegisterService $userRegisterService;

    public function __construct(UserRegisterService $userRegisterService)
    {
        $this->userRegisterService = $userRegisterService;
    }

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $username = $request->input('username');
        $password = $request->input('password');

        try {
            return $this->userRegisterService->execute($username, $password);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
