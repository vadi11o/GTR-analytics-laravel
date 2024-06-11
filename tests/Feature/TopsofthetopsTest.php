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

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopsofthetopsTest extends TestCase
{

    protected DBClient $dbClient;
    protected ApiClient $apiClient;
    protected TwitchTokenProvider $tokenProvider;
    protected TopsofthetopsService $topsService;
    protected TopGamesService $topGamesService;
    protected TopVideoService $topVideosService;
    protected TopsofthetopsController $topsController;

    public function setUp(): void
    {
        parent::setUp();

        $this->dbClient      = Mockery::mock(DBClient::class);
        $this->apiClient     = Mockery::mock(ApiClient::class);
        $this->tokenProvider = Mockery::mock(TwitchTokenProvider::class);

        $this->topGamesService = new TopGamesService(
            $this->dbClient,
            $this->apiClient,
            $this->tokenProvider
        );

        $this->topVideosService = new TopVideoService(
            $this->dbClient,
            $this->apiClient,
            $this->tokenProvider
        );

        $this->topsService = new TopsofthetopsService(
            $this->dbClient,
            $this->tokenProvider,
            $this->topVideosService,
            $this->topGamesService
        );

        $this->topsController = new TopsofthetopsController(
            $this->topsService
        );

        $this->app->instance(TopsofthetopsController::class, $this->topsController);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test
     * @throws ConnectionException
     */
    public function getsTopOfTheTopsData()
    {
        $mockTopGames = [
            ['game_id' => 1, 'game_name' => 'Test Game']
        ];
        $mockTopOfTheTops = [
            [
                'game_id'                => 1,
                'game_name'              => 'Test Game',
                'user_name'              => 'Test User',
                'total_videos'           => 10,
                'total_views'            => 100,
                'most_viewed_title'      => 'Test Title',
                'most_viewed_views'      => 50,
                'most_viewed_duration'   => 3600,
                'most_viewed_created_at' => now()->toDateTimeString(),
                'ultima_actualizacion'   => now()->toDateTimeString()
            ]
        ];
        $expectedResponse = [
            [
                'game_id'                => 1,
                'game_name'              => 'Test Game',
                'user_name'              => 'Test User',
                'total_videos'           => 10,
                'total_views'            => 100,
                'most_viewed_title'      => 'Test Title',
                'most_viewed_views'      => 50,
                'most_viewed_duration'   => 3600,
                'most_viewed_created_at' => now()->toDateTimeString(),
                'ultima_actualizacion'   => now()->toDateTimeString()
            ]
        ];
        $this->dbClient->shouldReceive('updateGamesSince')->once()->with(600, $this->topVideosService);
        $this->tokenProvider->shouldReceive('getTokenFromTwitch')->andReturn('valid_token');
        $this->dbClient->shouldReceive('getTopGames')->andReturn(collect($mockTopGames));
        $this->dbClient->shouldReceive('getTopOfTheTopsData')->andReturn(collect($mockTopOfTheTops));
        $this->apiClient->shouldReceive('updateGames')->andReturn($mockTopGames);
        $this->dbClient->shouldReceive('saveGames')->andReturnNull();
        $topGameMock = Mockery::mock('alias:' . TopGame::class);
        $topGameMock->shouldReceive('pluck')->andReturn(collect([1]));
        $topOfTheTopMock = Mockery::mock('alias:' . TopOfTheTop::class);
        $topOfTheTopMock->shouldReceive('whereIn')->andReturnSelf();
        $topOfTheTopMock->shouldReceive('get')->andReturn(collect($mockTopOfTheTops));
        $request = Request::create('analytics/topsofthetops', 'GET', ['since' => 600]);

        $response = $this->topsController->__invoke($request);

        $responseArray = json_decode($response->getContent(), true);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $responseArray);
    }

    /**
     * @test
     */
    public function errorIfTokenRetrievalFails()
    {
        $this->tokenProvider->shouldReceive('getTokenFromTwitch')->andThrow(new ConnectionException());

        $request = Request::create('analytics/topsofthetops', 'GET', ['since' => 600]);

        try {
            $this->topsController->__invoke($request);
        } catch (ConnectionException $e) {
            $this->assertInstanceOf(ConnectionException::class, $e);
        }
    }
}
