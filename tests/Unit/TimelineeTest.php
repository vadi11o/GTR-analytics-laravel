<?php

use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\APIClient;
use App\Services\TimelineService;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TimelineeTest extends TestCase
{
    protected DBClient $dbClientMock;
    protected APIClient $apiClientMock;
    protected TimelineService $timelineService;
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbClientMock  = Mockery::mock(DBClient::class);
        $this->apiClientMock = Mockery::mock(APIClient::class);

        $this->timelineService = new TimelineService($this->dbClientMock, $this->apiClientMock);
    }

    public function testSortStreamsSortsWell()
    {
        $this->apiClientMock->shouldReceive('getStreamsByUserId')
            ->with('123')
            ->andReturn([
                ['title' => 'Stream1', 'view_count' => 100, 'created_at' => '2023-01-01T00:00:00Z'],
                ['title' => 'Stream2', 'view_count' => 200, 'created_at' => '2023-01-02T00:00:00Z']
            ]);

        $expected = [
            [
                'streamerId'   => '123',
                'streamerName' => 'Streamer1',
                'title'        => 'Stream2',
                'viewerCount'  => 200,
                'startedAt'    => '2023-01-02T00:00:00Z'
            ],
            [
                'streamerId'   => '123',
                'streamerName' => 'Streamer1',
                'title'        => 'Stream1',
                'viewerCount'  => 100,
                'startedAt'    => '2023-01-01T00:00:00Z'
            ]
        ];

        $followedStreamers = [['id' => '123', 'display_name' => 'Streamer1']];

        $result = $this->timelineService->sortStreams($followedStreamers);

        $this->assertEquals($expected, $result);
    }

    public function testUserNotFound()
    {
        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->with(1)
            ->andReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->timelineService->execute(1);
    }

    public function testExecuteSortsStreamsByStartedAt()
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

        $this->dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->with(1)
            ->andReturn((object) ['followed_streamers' => json_encode([['id' => '123', 'display_name' => 'Streamer1']])]);

        $this->apiClientMock->shouldReceive('getStreamsByUserId')
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
