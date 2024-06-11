<?php

namespace Services;

use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use App\Services\TopGamesService;
use App\Services\TopsOfTheTopsService;
use App\Services\TopVideoService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use PHPUnit\Framework\TestCase;

class TopsOfTheTopsServiceTest extends TestCase
{
    protected TwitchTokenProvider $tokenProvider;
    protected TopGamesService $topGamesService;
    protected TopVideoService $topVideosService;
    protected DBClient $dbClient;
    protected TopsOfTheTopsService $service;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenProvider    = $this->createMock(TwitchTokenProvider::class);
        $this->topGamesService  = $this->createMock(TopGamesService::class);
        $this->topVideosService = $this->createMock(TopVideoService::class);
        $this->dbClient         = $this->createMock(DBClient::class);
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function updatesTopOfTheTops()
    {
        $this->tokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->dbClient->expects($this->once())->method('updateGamesSince')->with(600, $this->topVideosService);
        $this->service = new TopsOfTheTopsService($this->dbClient, $this->tokenProvider, $this->topVideosService, $this->topGamesService);
        $this->topGamesService->expects($this->once())->method('execute');

        $this->service->execute(600);
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function errorIfTokenRetrievalFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch');
        $this->tokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));
        $this->service = new TopsOfTheTopsService($this->dbClient, $this->tokenProvider, $this->topVideosService, $this->topGamesService);

        $this->service->execute(600);
    }
}
