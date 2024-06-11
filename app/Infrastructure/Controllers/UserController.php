<?php

namespace App\Infrastructure\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function __invoke(): JsonResponse
    {
        return $this->userService->execute();
    }
}
