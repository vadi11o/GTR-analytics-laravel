<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\UserController;
use App\Services\UserService;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;
use Illuminate\Http\JsonResponse;

class UserTest extends TestCase
{

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/analytics/users?id=1234');

        $response->assertStatus(200);
    }
    public function testShowReturnsJsonResponseWhenUserIdIsProvided()
    {
        $userId = '12345';
        $request = Request::create('/users', 'GET', ['id' => $userId]);
        $expectedResponse = new JsonResponse(['user' => 'data'], 200);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('execute')
            ->once()
            ->with($userId)
            ->andReturn($expectedResponse);

        $controller = new UserController($mockUserService);

        $response = $controller->show($request);

        $this->assertEquals($expectedResponse, $response);
    }
}
