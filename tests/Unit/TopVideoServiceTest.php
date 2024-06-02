<?php

use App\Services\TopVideoService;
use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use PHPUnit\Framework\TestCase;
use Exception;

class TopVideoServiceTest extends TestCase
{
    protected TwitchTokenProvider $mockTokenProvider;
    protected ApiClient $mockApiClient;
    protected DBClient $mockDbClient;
    protected TopVideoService $service;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->mockApiClient     = $this->createMock(ApiClient::class);
        $this->mockDbClient      = $this->createMock(DBClient::class);

        $this->service = new TopVideoService($this->mockDbClient, $this->mockApiClient, $this->mockTokenProvider);
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function itShouldSaveVideosSuccessfullyWhenTokenAndVideosAreValid()
    {
        $this->mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->mockApiClient->method('updateVideos')->willReturn([['id' => '456', 'title' => 'Fake Video']]);
        $this->mockDbClient->expects($this->once())->method('saveVideos')->with([['id' => '456', 'title' => 'Fake Video']], '123');

        $this->service->execute('123');
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionWhenTokenRetrievalFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch');
        $this->mockTokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));

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
        $this->mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->mockApiClient->method('updateVideos')->willReturn([]);

        $this->service->execute('123');
    }
}
