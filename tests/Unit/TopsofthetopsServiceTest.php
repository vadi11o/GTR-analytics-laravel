<?php

use App\Services\TopsofthetopsService;
use App\Providers\TwitchTokenProvider;
use App\Infrastructure\Clients\DBClient;
use App\Services\TopGamesService;
use App\Services\TopVideoService;
use Illuminate\Http\Client\ConnectionException;
use PHPUnit\Framework\TestCase;
use Exception;


class TopsofthetopsServiceTest extends TestCase
{
    protected $tokenProvider;
    protected $topGamesService;
    protected $topVideosService;
    protected $dbClient;
    protected $service;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->topGamesService = $this->createMock(TopGamesService::class);
        $this->topVideosService = $this->createMock(TopVideoService::class);
        $this->dbClient = $this->createMock(DBClient::class);
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
        $this->service = new TopsofthetopsService($this->dbClient, $this->tokenProvider, $this->topVideosService, $this->topGamesService);
        $this->topGamesService->expects($this->once())->method('execute');

        $this->service->execute(600);
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function itShouldThrowExceptionWhenTokenRetrievalFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch');
        $this->tokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));
        $this->service = new TopsofthetopsService($this->dbClient, $this->tokenProvider, $this->topVideosService, $this->topGamesService);

        $this->service->execute(600);
    }
}
