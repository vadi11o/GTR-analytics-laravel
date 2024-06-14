<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use App\Providers\TwitchTokenProvider;
use App\Services\TopVideoService;
use Exception;
use PHPUnit\Framework\TestCase;

class TopVideoServiceTest extends TestCase
{
    protected TwitchTokenProvider $tokenProvider;
    protected TwitchManager $twitchManager;
    protected DBClient $dbClient;
    protected TopVideoService $service;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->twitchManager     = $this->createMock(TwitchManager::class);
        $this->dbClient      = $this->createMock(DBClient::class);

        $this->service = new TopVideoService($this->dbClient, $this->twitchManager, $this->tokenProvider);
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function updatesVideos()
    {
        $this->tokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->twitchManager->method('updateVideos')->willReturn([['id' => '456', 'title' => 'Fake Video']]);
        $this->dbClient->expects($this->once())->method('saveVideos')->with([['id' => '456', 'title' => 'Fake Video']], '123');

        $this->service->execute('123');
    }

    /**
     * @test
     */
    public function errorIfTokenRetrievalFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch');
        $this->tokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));

        $this->service->execute('123');
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function errorIfVideosAreNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        $this->tokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->twitchManager->method('updateVideos')->willReturn([]);

        $this->service->execute('123');
    }
}
