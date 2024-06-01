<?php
namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Models\User;
use App\Providers\TwitchTokenProvider;
use App\Services\GetStreamerService;
use App\Services\StreamerDataManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @group exclude
 */
class StreamersTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @Test
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->apiClient = $this->getMockBuilder(ApiClient::class)
            ->setConstructorArgs([$this->tokenProvider])
            ->onlyMethods(['fetchStreamerDataFromTwitch'])
            ->getMock();
        $this->dbClient = $this->createMock(DBClient::class);
        $this->getUserService = $this->createMock(GetStreamerService::class);
        $this->streamerDataManager = new StreamerDataManager($this->getUserService, $this->dbClient);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testStreamerControllerHandlesMissingIdParameter()
    {
        $response = $this->getJson('analytics/streamers');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'El parametro "id" es obligatorio.'
        ]);
    }

    public function testStreamerControllerHandlesInvalidIdParameter()
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
    public function testExecuteReturnsStreamerFromDBIfFound()
    {
        $userId     = '123';
        $userFromDB = ['id' => $userId, 'name' => 'John Doe'];

        $this->dbClient->method('getStreamerByIdFromDB')
            ->with($userId)
            ->willReturn($userFromDB);

        $this->getUserService->expects($this->never())
            ->method('execute');

        $response = $this->streamerDataManager->execute($userId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($userFromDB, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $response->content());
    }

    /**@test
     * @throws \Exception
     */
    public function testExecuteCallsGetStreamerServiceWhenNotInDB()
    {
        $streamerId = '83232866';
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

        $this->getUserService->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($streamerId))
            ->willReturn(new JsonResponse($streamerData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $response = $this->streamerDataManager->execute($streamerId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($streamerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $response->getContent());
    }

    public function testGetUserByIdFromDBIsCalled()
    {
        $userId   = '12345';
        $dbClient = Mockery::mock(DBClient::class);
        $dbClient->shouldReceive('getStreamerByIdFromDB')
            ->once()
            ->with($userId)
            ->andReturn(['id' => $userId]);

        $result = $dbClient->getStreamerByIdFromDB($userId);

        $this->assertNotEmpty($result);
        Mockery::close();
    }

    public function testGetStreamerServiceReturnsStreamerDataWhenFound()
    {
        $streamerId = '83232866';
        $streamerData = [
            'twitch_id'        => '83232866',
            'login'            => 'ibai',
            'display_name'     => 'ibai',
            'type'             => '',
            'broadcaster_type' => 'partner',
            'description'      => 'Si lees esto que sepas que te aprecio',
            'profile_image_url'=> 'https://static-cdn.jtvnw.net/jtv_user_pictures/574228be-01ef-4eab-bc0e-a4f6b68bedba-profile_image-300x300.png',
            'offline_image_url'=> 'https://static-cdn.jtvnw.net/jtv_user_pictures/b01927d9-1cc2-4ba0-b3e2-6e96959179d0-channel_offline_image-1920x1080.jpeg',
            'view_count'       => 0,
            'created_at'       => '2015-02-20T16:47:56Z'
        ];

        $this->apiClient->expects($this->once())
            ->method('fetchStreamerDataFromTwitch')
            ->with($this->equalTo($streamerId))
            ->willReturn($streamerData);

        $this->dbClient->expects($this->once())
            ->method('insertStreamerToDB')
            ->with($streamerData);

        $service = new GetStreamerService($this->dbClient, $this->apiClient);
        $response = $service->execute($streamerId);

        $expectedData = $streamerData;
        $expectedData['id'] = $expectedData['twitch_id'];
        unset($expectedData['twitch_id']);

        $responseData = json_decode($response->getContent(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($expectedData, $responseData);
    }


    public function testInsertUserToDBIsCalled()
    {
        $userData = [
            'twitch_id'         => '12345',
            'login'             => 'testlogin',
            'display_name'      => 'TestDisplayName',
            'type'              => 'staff',
            'broadcaster_type'  => 'partner',
            'description'       => 'User description here',
            'profile_image_url' => 'http://example.com/profile.jpg',
            'offline_image_url' => 'http://example.com/offline.jpg',
            'view_count'        => 100,
        ];

        $dbClient = Mockery::mock(DBClient::class);
        $dbClient->shouldReceive('insertStreamerToDB')
            ->once()
            ->with(Mockery::on(function ($arg) use ($userData) {
                return $arg === $userData;
            }))
            ->andReturnNull();

        $dbClient->insertStreamerToDB($userData);

        $this->assertTrue(true);

        Mockery::close();
    }

    public function testGetUserByIdFromDBReturnsNullIfNotFound()
    {
        $userId = 'nonexistent';

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
    public function testFetchStreamerDataFromTwitchReturnsStreamerData()
    {
        $this->apiClient = new ApiClient($this->tokenProvider);

        $streamerId = '83232866';
        $token = 'test_token';
        $url = env('TWITCH_URL') . '/users?id=' . $streamerId;

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

        $responseData = [
            'data' => [$streamerData]
        ];

        $this->tokenProvider->expects($this->once())
            ->method('getTokenFromTwitch')
            ->willReturn($token);

        Http::fake([
            $url => Http::response($responseData, 200)
        ]);

        $result = $this->apiClient->fetchStreamerDataFromTwitch($streamerId);

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

    public function testFetchStreamerDataFromTwitchReturnsErrorOnFailure()
    {
        $this->apiClient = new ApiClient($this->tokenProvider);

        $streamerId = '83232866';
        $token = 'test_token';
        $url = env('TWITCH_URL') . '/users?id=' . $streamerId;

        $this->tokenProvider->expects($this->once())
            ->method('getTokenFromTwitch')
            ->willReturn($token);

        Http::fake([
            $url => Http::response(null, 500)
        ]);

        $result = $this->apiClient->fetchStreamerDataFromTwitch($streamerId);

        $this->assertEquals([
            'error' => 'Failed to fetch data from Twitch',
            'status_code' => 500
        ], $result);
    }
}
