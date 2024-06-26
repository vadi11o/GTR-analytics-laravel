<?php

namespace Tests\Feature;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

class GetStreamerTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->streamerData = [
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

        $this->dbClient = $this->createMock(DBClient::class);
        $this->twitchManager = $this->createMock(TwitchManager::class);

        $this->app->instance(DBClient::class, $this->dbClient);
        $this->app->instance(TwitchManager::class, $this->twitchManager);
    }

    /** @test
     *
     * @throws Exception
     */
    public function returnsStreamerDataFromTwitch()
    {
        $this->dbClient->expects($this->once())
            ->method('getStreamerByIdFromDB')
            ->with('83232866')
            ->willReturn(null);
        $this->twitchManager->expects($this->once())
            ->method('fetchStreamerDataFromTwitch')
            ->with('83232866')
            ->willReturn($this->streamerData);

        $response = $this->getJson('analytics/streamers?id=83232866');

        $response->assertStatus(200);
        $response->assertJson($this->streamerData);
    }


    /** @test
     *
     * @throws Exception
     */
    public function returnsStreamerDataFromDB()
    {
        $this->dbClient->expects($this->once())
            ->method('getStreamerByIdFromDB')
            ->with('83232866')
            ->willReturn($this->streamerData);
        $this->twitchManager->expects($this->never())
            ->method('fetchStreamerDataFromTwitch');

        $response = $this->getJson('analytics/streamers?id=83232866');

        $response->assertStatus(200);
        $response->assertJson($this->streamerData);
    }

    /**
     * @test
     */
    public function errorWhenMissingParameter()
    {
        $response = $this->getJson('analytics/streamers');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El parametro "id" es obligatorio.',
            ]);
    }
}
