<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Models\User;
use App\Services\GetStreamsService;
use App\Services\GetUserService;
use App\Services\UserDataManager;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class UsersTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @Test
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = $this->createMock(ApiClient::class);

        $this->dbClient = $this->createMock(DBClient::class);

        $this->getUserService = $this->createMock(GetUserService::class);

        $this->streamsService  = new GetStreamsService($this->apiClient, $this->dbClient);
        $this->userDataManager = new UserDataManager($this->getUserService, $this->dbClient);

    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testExecuteReturnsUserFromDBIfFound()
    {
        $userId     = '123';
        $userFromDB = ['id' => $userId, 'name' => 'John Doe'];

        $this->dbClient->method('getUserByIdFromDB')
            ->with($userId)
            ->willReturn($userFromDB);

        // Expect not to call GetUserService
        $this->getUserService->expects($this->never())
            ->method('execute');

        $response = $this->userDataManager->execute($userId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($userFromDB, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $response->content());
    }

    public function testExecuteCallsGetUserServiceIfUserNotFoundInDB()
    {
        $userId          = '123';
        $userFromService = new JsonResponse(['id' => $userId, 'name' => 'John Doe'], 200);

        $this->dbClient->method('getUserByIdFromDB')
            ->with($userId)
            ->willReturn(null);

        $this->getUserService->expects($this->once())
            ->method('execute')
            ->with($userId)
            ->willReturn($userFromService);

        $response = $this->userDataManager->execute($userId);

        $this->assertEquals($userFromService, $response);
    }

    public function testGetUserByIdFromDBIsCalled()
    {
        $userId   = '12345';
        $dbClient = Mockery::mock(DBClient::class);
        $dbClient->shouldReceive('getUserByIdFromDB')
            ->once()
            ->with($userId)
            ->andReturn(['id' => $userId]);

        $result = $dbClient->getUserByIdFromDB($userId);

        $this->assertNotEmpty($result);
        Mockery::close();
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
        $dbClient->shouldReceive('insertUserToDB')
            ->once()
            ->with(Mockery::on(function ($arg) use ($userData) {
                return $arg === $userData;
            }))
            ->andReturnNull();

        $dbClient->insertUserToDB($userData);

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

        $result = $this->dbClient->getUserByIdFromDB($userId);

        $this->assertNull($result);
    }

    public function testExecuteWithValidData()
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

    /** @test
     * @throws ConnectionException
     */
    public function testParsesJsonFromTwitchResponseSuccessfully()
    {
        $token  = 'fake_token';
        $userId = '12345';
        $url    = 'https://api.twitch.tv/helix/users?id=' . $userId;

        $fakeResponse = [
            'data' => [
                [
                    'id'                => '12345',
                    'login'             => 'testuser',
                    'display_name'      => 'Test User',
                    'type'              => '',
                    'broadcaster_type'  => 'partner',
                    'description'       => 'A great Twitch streamer',
                    'profile_image_url' => 'https://example.com/image.jpg',
                    'offline_image_url' => 'https://example.com/offline.jpg',
                    'view_count'        => 100,
                    'created_at'        => '2020-01-01T00:00:00Z'
                ]
            ]
        ];

        Http::fake([
            $url => Http::response($fakeResponse, 200),
        ]);

        $this->apiClient->method('fetchUserDataFromTwitch')
            ->willReturn($fakeResponse['data'][0]);

        $response = $this->apiClient->fetchUserDataFromTwitch($token, $userId);

        $this->assertEquals('12345', $response['id']);
        $this->assertEquals('Test User', $response['display_name']);
        $this->assertEquals(Carbon::parse('2020-01-01T00:00:00Z')->toDateTimeString(), Carbon::parse($response['created_at'])->toDateTimeString());

        Http::fake([
            'https://api.twitch.tv/helix/users?id=12345' => Http::response([
                'data' => [
                    [
                        'id'                => '12345',
                        'login'             => 'testuser',
                        'display_name'      => 'Test User',
                        'type'              => '',
                        'broadcaster_type'  => 'partner',
                        'description'       => 'A great Twitch streamer',
                        'profile_image_url' => 'https://example.com/image.jpg',
                        'offline_image_url' => 'https://example.com/offline.jpg',
                        'view_count'        => 100,
                        'created_at'        => '2020-01-01T00:00:00Z'
                    ]
                ]
            ], 200)
        ]);
    }

    /** @test
     * @throws ConnectionException
     */
    public function testParsesJsonFromTwitchResponseUnsuccessfully()
    {
        $token  = 'fake_token';
        $userId = 'wrong_id';
        $url    = 'https://api.twitch.tv/helix/users?id=' . $userId;

        Http::fake([
            $url => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->apiClient->method('fetchUserDataFromTwitch')
            ->willReturn(['error' => 'Failed to fetch data from Twitch', 'status_code' => 404]);  // Assume this is the format your method returns on error

        $response = $this->apiClient->fetchUserDataFromTwitch($token, $userId);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Failed to fetch data from Twitch', $response['error']);
        $this->assertEquals(404, $response['status_code']);
    }

}
