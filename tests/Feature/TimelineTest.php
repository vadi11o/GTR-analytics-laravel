<?php

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Exception;
/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TimelineTest extends TestCase
{
    protected DBClient $dbClient;
    protected TwitchManager $twitchManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbClient  = Mockery::mock(DBClient::class);
        $this->twitchManager = Mockery::mock(TwitchManager::class);
        $this->app->instance(DBClient::class, $this->dbClient);
        $this->app->instance(TwitchManager::class, $this->twitchManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function returnsTimelineWhenUserExists()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->andReturn((object) ['followed_streamers' => json_encode([['id' => '123', 'display_name' => 'Streamer1']])]);
        $this->twitchManager->shouldReceive('getStreamsByUserId')
            ->andReturn([['title' => 'Stream1', 'view_count' => 100, 'created_at' => '2023-01-01T00:00:00Z']]);

        $response = $this->getJson('analytics/timeline?userId=1');

        $response->assertStatus(200);
        $response->assertJson([[
            'streamerId'   => '123',
            'streamerName' => 'Streamer1',
            'title'        => 'Stream1',
            'viewerCount'  => 100,
            'startedAt'    => '2023-01-01T00:00:00Z',
        ]]);
    }

    /**
     * @test
     */
    public function errorOnServerFailure()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->andThrow(new Exception('Failed to retrieve data'));

        $response = $this->getJson('analytics/timeline?userId=1');

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Server error']);
    }

    /**
     * @test
     */
    public function errorWhenNull()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->andReturn(null);

        $response = $this->getJson('analytics/timeline?userId=1');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'El usuario especificado (userId: 1) no existe']);
    }

    /**
     * @test
     */
    public function errorWhenUserDoesNotExist()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->andThrow(new NotFoundHttpException('El usuario especificado (userId: 1) no existe'));

        $response = $this->getJson('analytics/timeline?userId=1');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'El usuario especificado (userId: 1) no existe']);
    }

    /**
     * @test
     */
    public function userExistsButIsNotFollowingAnyStreamers()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->andReturn((object) ['followed_streamers' => json_encode([])]);

        $response = $this->getJson('analytics/timeline?userId=1');

        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    /**
     * @test
     */
    public function errorWhenMissingParameter()
    {
        $response = $this->getJson('analytics/timeline');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'The userId parameter is required',
            ]);
    }
}
