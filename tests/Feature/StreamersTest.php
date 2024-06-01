<?php

namespace Tests\Feature;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use Exception;
use Tests\TestCase;
use Illuminate\Http\JsonResponse;
use App\Services\StreamerDataManager;

/**
 * @group exclude
 */
class StreamersTest extends TestCase
{
    /** @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testStreamerControllerReturnsSuccessfulResponseWhenStreamerNotInDB()
    {
        $streamerData = [
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
        $mockDBClient = $this->createMock(DBClient::class);
        $mockDBClient->expects($this->once())
            ->method('getStreamerByIdFromDB')
            ->with($this->equalTo('83232866'))
            ->willReturn(null);
        $mockApiClient = $this->createMock(ApiClient::class);
        $mockApiClient->expects($this->once())
            ->method('fetchStreamerDataFromTwitch')
            ->with($this->equalTo('83232866'))
            ->willReturn($streamerData);
        $this->app->instance(DBClient::class, $mockDBClient);
        $this->app->instance(ApiClient::class, $mockApiClient);

        $response = $this->getJson('analytics/streamers?id=83232866');

        $response->assertStatus(200);
        $response->assertJson($streamerData);
    }

    public function testStreamerControllerReturnsSuccessfulResponseWhenStreamerInDB()
    {
        $streamerData = [
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
        $mockDBClient = $this->createMock(DBClient::class);
        $mockDBClient->expects($this->once())
            ->method('getStreamerByIdFromDB')
            ->with($this->equalTo('83232866'))
            ->willReturn($streamerData);
        $mockApiClient = $this->createMock(ApiClient::class);
        $mockApiClient->expects($this->never())
            ->method('fetchStreamerDataFromTwitch');
        $this->app->instance(DBClient::class, $mockDBClient);
        $this->app->instance(ApiClient::class, $mockApiClient);

        $response = $this->getJson('analytics/streamers?id=83232866');

        $response->assertStatus(200);
        $response->assertJson($streamerData);
    }
}
