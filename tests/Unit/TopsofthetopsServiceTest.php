<?php

use App\Services\TopsofthetopsService;
use App\Providers\TwitchTokenProvider;
use App\Infrastructure\Clients\DBClient;
use App\Services\TopGamesService;
use App\Services\TopVideoService;
use Illuminate\Http\Client\ConnectionException;
use PHPUnit\Framework\TestCase;

class TopsofthetopsServiceTest extends TestCase
{
    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function itShouldExecuteSuccessfullyWhenTokenAndServicesAreValid()
    {
        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $mockTopGamesService = $this->createMock(TopGamesService::class);
        $mockTopGamesService->expects($this->once())->method('execute');
        $mockTopVideoService = $this->createMock(TopVideoService::class);
        $mockDbClient = $this->createMock(DBClient::class);
        $mockDbClient->expects($this->once())->method('updateGamesSince')->with(600, $mockTopVideoService);
        $service = new TopsofthetopsService($mockDbClient, $mockTokenProvider, $mockTopVideoService, $mockTopGamesService);

        $service->execute(600);
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
        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));
        $mockTopGamesService = $this->createMock(TopGamesService::class);
        $mockTopVideoService = $this->createMock(TopVideoService::class);
        $mockDbClient = $this->createMock(DBClient::class);
        $service = new TopsofthetopsService($mockDbClient, $mockTokenProvider, $mockTopVideoService, $mockTopGamesService);

        $service->execute(600);
    }
}
