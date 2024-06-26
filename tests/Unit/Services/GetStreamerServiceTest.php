<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Managers\StreamerDataManager;
use App\Managers\TwitchManager;
use App\Models\User;
use App\Providers\TwitchTokenProvider;
use App\Services\GetStreamerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class GetStreamerServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @Test
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->apiClient           = $this->createMock(ApiClient::class);
        $this->twitchManager     = $this->getMockBuilder(TwitchManager::class)
            ->setConstructorArgs([$this->tokenProvider, $this->apiClient])
            ->onlyMethods(['fetchStreamerDataFromTwitch'])
            ->getMock();
        $this->dbClient            = $this->createMock(DBClient::class);
        $this->getStreamerService  = $this->createMock(GetStreamerService::class);
        $this->streamerDataManager = new StreamerDataManager($this->getStreamerService, $this->dbClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test
     */
    public function missingIdParameterOnRequest()
    {
        $response = $this->getJson('analytics/streamers');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'El parametro "id" es obligatorio.'
        ]);
    }

    /** @test
     */
    public function invalidIdParameterOnRequest()
    {
        $response = $this->getJson('analytics/streamers?id=invalid_id');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'El parametro "id" debe ser un entero.'
        ]);
    }

    /**@test
     * @throws \Exception
     */
    public function returnsStreamerFromDBIfFound()
    {
        $userId     = '123';
        $userFromDB = ['id' => $userId, 'name' => 'John Doe'];
        $this->dbClient->method('getStreamerByIdFromDB')
            ->with($userId)
            ->willReturn($userFromDB);
        $this->getStreamerService->expects($this->never())
            ->method('execute');

        $response = $this->streamerDataManager->execute($userId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($userFromDB, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $response->content());
    }

    /**@test
     * @throws \Exception
     */
    public function fetchStreamerFromTwitchWhenIsNotInDB()
    {
        $streamerId   = '83232866';
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
        $this->dbClient->expects($this->once())
            ->method('getStreamerByIdFromDB')
            ->with($this->equalTo($streamerId))
            ->willReturn(null);
        $this->getStreamerService->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($streamerId))
            ->willReturn(new JsonResponse($streamerData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->streamerDataManager->execute($streamerId);
    }

    /** @test
     */
    public function getStreamerByIdFromDBIsCalled()
    {
        $userId   = '12345';
        $dbClient = Mockery::mock(DBClient::class);
        $dbClient->shouldReceive('getStreamerByIdFromDB')
            ->once()
            ->with($userId)
            ->andReturn(['id' => $userId]);

        $result = $dbClient->getStreamerByIdFromDB($userId);

        $this->assertNotEmpty($result);
    }

    /** @test
     * @throws \Exception
     */
    public function returnsStreamerDataWhenFound()
    {
        $streamerId   = '83232866';
        $streamerData = [
            'twitch_id'         => '83232866',
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
        $this->twitchManager->expects($this->once())
            ->method('fetchStreamerDataFromTwitch')
            ->with($this->equalTo($streamerId))
            ->willReturn($streamerData);
        $this->dbClient->expects($this->once())
            ->method('insertStreamerToDB')
            ->with($streamerData);
        $service = new GetStreamerService($this->dbClient, $this->twitchManager);

        $response           = $service->execute($streamerId);
        $expectedData       = $streamerData;
        $expectedData['id'] = $expectedData['twitch_id'];
        unset($expectedData['twitch_id']);
        $responseData = json_decode($response->getContent(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($expectedData, $responseData);
    }

    /** @test
     * @throws \Exception
     */
    public function insertStreamerToDBIsCalled()
    {
        $this->getStreamerService = new GetStreamerService($this->dbClient, $this->twitchManager, $this->tokenProvider);
        $streamerId               = '12345';
        $streamerData             = [
            'twitch_id' => '12345',
            'name'      => 'test_streamer'
        ];
        $this->twitchManager->expects($this->once())
            ->method('fetchStreamerDataFromTwitch')
            ->with($streamerId)
            ->willReturn($streamerData);
        $this->dbClient->expects($this->once())
            ->method('insertStreamerToDB')
            ->with($streamerData);

        $this->getStreamerService->execute($streamerId);
    }

    /** @test
     */
    public function ifTheUserIsNotFoundInTheDBReturnsNull()
    {
        $userId   = 'nonexistent';
        $userMock = Mockery::mock('overload:' . User::class);
        $userMock->shouldReceive('where')
            ->once()
            ->with('twitch_id', $userId)
            ->andReturnSelf();
        $userMock->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $result = $this->dbClient->getStreamerByIdFromDB($userId);

        $this->assertNull($result);
    }

    /**@test
     * @throws \Exception
     */
    public function fetchStreamerDataFromTwitchReturnsStreamerData()
    {
        $this->twitchManager = new TwitchManager($this->tokenProvider,$this->apiClient);
        $streamerId      = '83232866';
        $token           = 'test_token';
        $url             = env('TWITCH_URL') . '/users?id=' . $streamerId;
        $streamerData    = [
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
        $responseData = [
            'data' => [$streamerData]
        ];
        $this->tokenProvider->expects($this->once())
            ->method('getTokenFromTwitch')
            ->willReturn($token);
        Http::fake([
            $url => Http::response($responseData)
        ]);

        $result = $this->twitchManager->fetchStreamerDataFromTwitch($streamerId);

        $this->assertEquals([
            'twitch_id'         => '83232866',
            'login'             => 'ibai',
            'display_name'      => 'ibai',
            'type'              => '',
            'broadcaster_type'  => 'partner',
            'description'       => 'Si lees esto que sepas que te aprecio',
            'profile_image_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/574228be-01ef-4eab-bc0e-a4f6b68bedba-profile_image-300x300.png',
            'offline_image_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/b01927d9-1cc2-4ba0-b3e2-6e96959179d0-channel_offline_image-1920x1080.jpeg',
            'view_count'        => 0,
            'created_at'        => Carbon::parse('2015-02-20T16:47:56Z')->toDateTimeString()
        ], $result);
    }
}
