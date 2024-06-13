<?php

namespace Tests\Feature;

use App\Http\Requests\TopsOfTheTopsRequest;
use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Controllers\TopsofthetopsController;
use App\Managers\TwitchManager;
use App\Models\TopGame;
use App\Models\TopOfTheTop;
use App\Providers\TwitchTokenProvider;
use App\Services\TopGamesService;
use App\Services\TopsOfTheTopsService;
use App\Services\TopVideoService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TopsofthetopsTest extends TestCase
{

    protected DBClient $dbClient;
    protected TwitchManager $twitchManager;
    protected TwitchTokenProvider $tokenProvider;
    protected TopsOfTheTopsService $topsService;
    protected TopGamesService $topGamesService;
    protected TopVideoService $topVideosService;
    protected TopsofthetopsController $topsController;

    public function setUp(): void
    {
        parent::setUp();

        $this->dbClient      = Mockery::mock(DBClient::class);
        $this->twitchManager     = Mockery::mock(TwitchManager::class);
        $this->tokenProvider = Mockery::mock(TwitchTokenProvider::class);

        $this->topGamesService = new TopGamesService(
            $this->dbClient,
            $this->twitchManager,
            $this->tokenProvider
        );

        $this->topVideosService = new TopVideoService(
            $this->dbClient,
            $this->twitchManager,
            $this->tokenProvider
        );

        $this->topsService = new TopsOfTheTopsService(
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
        $this->twitchManager->shouldReceive('updateGames')->andReturn($mockTopGames);
        $this->dbClient->shouldReceive('saveGames')->andReturnNull();
        $topGameMock = Mockery::mock('alias:' . TopGame::class);
        $topGameMock->shouldReceive('pluck')->andReturn(collect([1]));
        $topOfTheTopMock = Mockery::mock('alias:' . TopOfTheTop::class);
        $topOfTheTopMock->shouldReceive('whereIn')->andReturnSelf();
        $topOfTheTopMock->shouldReceive('get')->andReturn(collect($mockTopOfTheTops));

        $response = $this->getJson('analytics/topsofthetops?since=600');

        $response->assertStatus(200)
            ->assertJson($expectedResponse);
    }

    /**
     * @test
     */
    public function errorWhenInvalidParameterFormat()
    {
        $response = $this->getJson('analytics/topsofthetops?since="123"');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'El parametro "since" debe ser un entero.',
            ]);
    }
}
