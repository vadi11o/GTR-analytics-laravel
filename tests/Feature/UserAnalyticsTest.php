<?php

namespace Tests\Feature;

use App\Infrastructure\Controllers\RegisterUserController;
use App\Services\UserService;
use App\Services\UserServiceRegister;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UserAnalyticsTest extends TestCase
{
    /** @test */
    public function givenNewUserAnalyticsGetInsert()
    {
        $userServiceMock = Mockery::mock(UserService::class);
        $userServiceMock->shouldReceive('execute')->once()->with('nuevoUsuario')->andReturn(false);

        $registerMock = Mockery::mock(UserServiceRegister::class);
        $registerMock->shouldReceive('execute')->once()->with('nuevoUsuario', 'passwordSeguro123');

        $this->app->instance(UserService::class, $userServiceMock);
        $this->app->instance(UserServiceRegister::class, $registerMock);

        $controller = new RegisterUserController($userServiceMock, $registerMock);

        $request = new Request(['username' => 'nuevoUsuario', 'password' => 'passwordSeguro123']);

        $response = $controller->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->status());
    }

    /** @test */
    public function givenUserAnalyticsAlreadyRegisteredGetError()
    {
        $userServiceMock = Mockery::mock(UserService::class);
        $userServiceMock->shouldReceive('execute')->once()->with('usuarioExistente')->andReturn(true);

        $registerMock = Mockery::mock(UserServiceRegister::class);
        $registerMock->shouldReceive('execute')->zeroOrMoreTimes()->with('usuarioExistente', 'passwordSeguro123');

        $this->app->instance(UserService::class, $userServiceMock);
        $this->app->instance(UserServiceRegister::class, $registerMock);

        $controller = new RegisterUserController($userServiceMock, $registerMock);

        $request = new Request(['username' => 'usuarioExistente', 'password' => 'passwordSeguro123']);

        $response = $controller->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->status());
    }
}
