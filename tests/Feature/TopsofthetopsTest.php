<?php

namespace Tests\Feature;

use App\Infrastructure\Controllers\TopsofthetopsController;
use App\Services\TopGamesService;
use App\Services\TopVideoService;
use App\Services\TopsofthetopsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;
use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\ApiClient;
use App\Providers\TwitchTokenProvider;
use Illuminate\Http\Client\ConnectionException;
use App\Models\TopGame;
use App\Models\TopOfTheTop;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class TopsofthetopsTest extends TestCase
{
    use WithoutMiddleware;

    protected DBClient $dbClientMock;
    protected ApiClient $apiClientMock;
    protected TwitchTokenProvider $twitchTokenProviderMock;
    protected TopsofthetopsService $topsofthetopsService;
    protected TopGamesService $topGamesService;
    protected TopVideoService $topVideosService;
    protected TopsofthetopsController $topsofthetopsController;

    public function setUp(): void
    {
        parent::setUp();

        $this->dbClientMock = Mockery::mock(DBClient::class);
        $this->apiClientMock = Mockery::mock(ApiClient::class);
        $this->twitchTokenProviderMock = Mockery::mock(TwitchTokenProvider::class);

        $this->topGamesService = new TopGamesService(
            $this->dbClientMock,
            $this->apiClientMock,
            $this->twitchTokenProviderMock
        );

        $this->topVideosService = new TopVideoService(
            $this->dbClientMock,
            $this->apiClientMock,
            $this->twitchTokenProviderMock
        );

        $this->topsofthetopsService = new TopsofthetopsService(
            $this->dbClientMock,
            $this->twitchTokenProviderMock,
            $this->topVideosService,
            $this->topGamesService
        );

        $this->topsofthetopsController = new TopsofthetopsController(
            $this->topsofthetopsService
        );

        $this->app->instance(TopsofthetopsController::class, $this->topsofthetopsController);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**@test
     * @throws ConnectionException
     */
    public function itShouldReturnJsonResponseWithTopGamesAndTopVideosData()
    {
        $mockTopGames = [
            ['game_id' => 1, 'game_name' => 'Test Game']
        ];
        $mockTopOfTheTops = [
            [
                'game_id' => 1,
                'game_name' => 'Test Game',
                'user_name' => 'Test User',
                'total_videos' => 10,
                'total_views' => 100,
                'most_viewed_title' => 'Test Title',
                'most_viewed_views' => 50,
                'most_viewed_duration' => 3600,
                'most_viewed_created_at' => now()->toDateTimeString(),
                'ultima_actualizacion' => now()->toDateTimeString()
            ]
        ];
        $expectedResponse = [
            [
                'game_id' => 1,
                'game_name' => 'Test Game',
                'user_name' => 'Test User',
                'total_videos' => 10,
                'total_views' => 100,
                'most_viewed_title' => 'Test Title',
                'most_viewed_views' => 50,
                'most_viewed_duration' => 3600,
                'most_viewed_created_at' => now()->toDateTimeString(),
                'ultima_actualizacion' => now()->toDateTimeString()
            ]
        ];
        $this->dbClientMock->shouldReceive('updateGamesSince')->once()->with(600, $this->topVideosService);
        $this->twitchTokenProviderMock->shouldReceive('getTokenFromTwitch')->andReturn('valid_token');
        $this->dbClientMock->shouldReceive('getTopGames')->andReturn(collect($mockTopGames));
        $this->dbClientMock->shouldReceive('getTopOfTheTopsData')->andReturn(collect($mockTopOfTheTops));
        $this->apiClientMock->shouldReceive('updateGames')->andReturn($mockTopGames);
        $this->dbClientMock->shouldReceive('saveGames')->andReturnNull();
        $topGameMock = Mockery::mock('alias:' . TopGame::class);
        $topGameMock->shouldReceive('pluck')->andReturn(collect([1]));
        $topOfTheTopMock = Mockery::mock('alias:' . TopOfTheTop::class);
        $topOfTheTopMock->shouldReceive('whereIn')->andReturnSelf();
        $topOfTheTopMock->shouldReceive('get')->andReturn(collect($mockTopOfTheTops));
        $request = Request::create('analytics/topsofthetops', 'GET', ['since' => 600]);

        $response = $this->topsofthetopsController->__invoke($request);

        $responseArray = json_decode($response->getContent(), true);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $responseArray);
    }

    /**
     * @test
     */
    public function itShouldThrowConnectionExceptionWhenTokenRetrievalFails()
    {
        $this->twitchTokenProviderMock->shouldReceive('getTokenFromTwitch')->andThrow(new ConnectionException());

        $request = Request::create('analytics/topsofthetops', 'GET', ['since' => 600]);

        try {
            $this->topsofthetopsController->__invoke($request);
        } catch (ConnectionException $e) {
            $this->assertInstanceOf(ConnectionException::class, $e);
        }
    }
}

