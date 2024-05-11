<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Services\GetUserService;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;
/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UsersTest extends TestCase
{


    public function test_execute_with_valid_data()
    {

        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getTokenFromTwitch')->andReturn('fake_token');
        $dbClientMock->shouldReceive('insertUserToDB')->once();

        $apiClientMock = Mockery::mock(ApiClient::class);
        $apiClientMock->shouldReceive('fetchUserDataFromTwitch')->andReturn([
            'id' => 'fake_user_id',

        ]);

        $userService = new GetUserService($dbClientMock, $apiClientMock);

        $response = $userService->execute('fake_user_id');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertJson($response->content());

        $userData = json_decode($response->content(), true);
        $this->assertEquals('fake_user_id', $userData['id']);

    }

    public function test_execute_with_invalid_data()
    {

        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getTokenFromTwitch')->andReturn(null);

        $userService = new GetUserService($dbClientMock, Mockery::mock(ApiClient::class));

        $response = $userService->execute('fake_user_id');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->status());
        $this->assertJson($response->content());
        $this->assertJsonStringEqualsJsonString('{"error": "No se pudo obtener el token de acceso desde Twitch."}', $response->content());
    }

}
