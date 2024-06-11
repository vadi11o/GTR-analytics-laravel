<?php

use App\Services\TopVideoService;
use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use PHPUnit\Framework\TestCase;
use Exception;

class TopVideoServiceTest extends TestCase
{
    protected TwitchTokenProvider $tokenProvider;
    protected ApiClient $apiClient;
    protected DBClient $dbClient;
    protected TopVideoService $service;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->apiClient     = $this->createMock(ApiClient::class);
        $this->dbClient      = $this->createMock(DBClient::class);

        $this->service = new TopVideoService($this->dbClient, $this->apiClient, $this->tokenProvider);
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function itShouldSaveVideosSuccessfullyWhenTokenAndVideosAreValid()
    {
        $this->tokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->apiClient->method('updateVideos')->willReturn([['id' => '456', 'title' => 'Fake Video']]);
        $this->dbClient->expects($this->once())->method('saveVideos')->with([['id' => '456', 'title' => 'Fake Video']], '123');

        $this->service->execute('123');
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionWhenTokenRetrievalFails()
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
    public function itShouldThrowExceptionWhenNoVideosAreFoundInApiResponse()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        $this->tokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->apiClient->method('updateVideos')->willReturn([]);

        $this->service->execute('123');
    }
}
