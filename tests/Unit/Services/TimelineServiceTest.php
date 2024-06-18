<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use App\Services\TimelineService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TimelineServiceTest extends TestCase
{
    protected DBClient $dbClient;
    protected TwitchManager $twitchManager;
    protected TimelineService $timelineService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbClient  = Mockery::mock(DBClient::class);
        $this->twitchManager = Mockery::mock(TwitchManager::class);

        $this->timelineService = new TimelineService($this->dbClient, $this->twitchManager);
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function userNotFound()
    {
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->with(1)
            ->andReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->timelineService->execute(1);
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function streamsAreSortedInTheCorrectOrder()
    {
        $expected = [
            [
                'streamerId'   => '123',
                'streamerName' => 'Streamer1',
                'title'        => 'Stream2',
                'viewerCount'  => 200,
                'startedAt'    => '2023-01-02T10:00:00Z'
            ],
            [
                'streamerId'   => '123',
                'streamerName' => 'Streamer1',
                'title'        => 'Stream3',
                'viewerCount'  => 150,
                'startedAt'    => '2023-01-01T12:00:00Z'
            ],
            [
                'streamerId'   => '123',
                'streamerName' => 'Streamer1',
                'title'        => 'Stream1',
                'viewerCount'  => 100,
                'startedAt'    => '2023-01-01T10:00:00Z'
            ]
        ];
        $this->dbClient->shouldReceive('getUserAnalyticsByIdFromDB')
            ->with(1)
            ->andReturn((object)['followed_streamers' => json_encode([['id' => '123', 'display_name' => 'Streamer1']])]);
        $this->twitchManager->shouldReceive('getStreamsByUserId')
            ->with('123')
            ->andReturn([
                ['title' => 'Stream1', 'view_count' => 100, 'created_at' => '2023-01-01T10:00:00Z'],
                ['title' => 'Stream2', 'view_count' => 200, 'created_at' => '2023-01-02T10:00:00Z'],
                ['title' => 'Stream3', 'view_count' => 150, 'created_at' => '2023-01-01T12:00:00Z']
            ]);

        $result = $this->timelineService->execute(1);

        $this->assertEquals($expected, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
