<?php

use App\Infrastructure\Clients\DBClient;
use App\Infrastructure\Clients\APIClient;
use Tests\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TimelineTest extends TestCase
{
    /**
     * @Test
     */
    public function testTimelineSuccessWhenUserExists()
    {
        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->andReturn((object) ['followed_streamers' => json_encode([['id' => '123', 'display_name' => 'Streamer1']])]);
        $apiClientMock = Mockery::mock(APIClient::class);
        $apiClientMock->shouldReceive('getStreamsByUserId')
            ->andReturn([['title' => 'Stream1', 'view_count' => 100, 'created_at' => '2023-01-01T00:00:00Z']]);
        $this->app->instance(DBClient::class, $dbClientMock);
        $this->app->instance(ApiClient::class, $apiClientMock);

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
     * @Test
     */
    public function testReturnsErrorWhenUserNotFound()
    {
        $dbClientMock = Mockery::mock(DBClient::class);
        $dbClientMock->shouldReceive('getUserAnalyticsByIdFromDB')
            ->andReturn(false);
        $apiClientMock = Mockery::mock(APIClient::class);
        $this->app->instance(DBClient::class, $dbClientMock);
        $this->app->instance(ApiClient::class, $apiClientMock);

        $response = $this->getJson('analytics/timeline?userId=1');

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Server error']);
    }
}
