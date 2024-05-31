<?php

namespace App\Infrastructure\Controllers;

use App\Services\UserService;
use App\Services\UserServiceRegister;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterUserController
{
    private UserService $userService;
    private UserServiceRegister $userServiceRegister;
    public function __construct(UserService $userService, UserServiceRegister $userServiceRegister)
    {
        $this->userService         = $userService;
        $this->userServiceRegister = $userServiceRegister;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $nombreUsuario = $request->input('username');
        $password      = $request->input('password');
        $userExist     = $this->userService->execute($nombreUsuario);
        if($userExist) {
            return response()->json(['message' => 'El nombre de usuario ya está en uso'], 409);
        }
        try {
            $this->userServiceRegister->execute($nombreUsuario, $password);
        } catch (Exception) {
            return response()->json(['message' => 'Error del servidor al crear el usuario'], 500);
        }
        return response()->json(['username' => $nombreUsuario,'message' => 'Usuario creado correctamente'], 201);
    }
}
