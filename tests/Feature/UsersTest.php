<?php

namespace Tests\Feature;

use Exception;
use Tests\TestCase;
use Illuminate\Http\JsonResponse;
use App\Services\UserDataManager;

class UsersTest extends TestCase
{
    /** @test */
    public function userControllerReturnsBadRequestWhenIdIsMissing()
    {
        $response = $this->getJson('analytics/users');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'El parametro "id" no se proporciono en la URL.'
        ]);
    }

    /** @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function userControllerReturnsSuccessfulResponse()
    {
        $mockService = $this->createMock(UserDataManager::class);
        $userData    = [
            'id'                => '83232866',
            'login'             => 'ibai',
            'display_name'      => 'ibai',
            'type'              => '',
            'broadcaster_type'  => 'partner',
            'description'       => 'Si lees esto que sepas que te aprecio',
            'profile_image_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/574228be-01ef-4eab-bc0e-a4f6b68bedba-profile_image-300x300.png',
            'offline_image_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/b01927d9-1cc2-4ba0-b3e2-6e96959179d0-channel_offline_image-1920x1080.jpeg',
            'view_count'        => 0,
            'created_at'        => '2015-02-20T16:47:56Z'
        ];
        $mockResult = new JsonResponse($userData, 200);

        $mockService->expects($this->once())
            ->method('execute')
            ->with($this->equalTo('83232866'))
            ->willReturn($mockResult);

        $this->app->instance(UserDataManager::class, $mockService);

        $response = $this->getJson('analytics/users?id=83232866');

        $response->assertStatus(200);
        $response->assertJson($userData);
    }
}
