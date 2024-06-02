<?php

use App\Services\TopVideoService;
use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use PHPUnit\Framework\TestCase;

class TopVideoServiceTest extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function testExecuteSuccessfully()
    {
        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');

        $mockApiClient = $this->createMock(ApiClient::class);
        $mockApiClient->method('updateVideos')->willReturn([['id' => '456', 'title' => 'Fake Video']]);

        $mockDbClient = $this->createMock(DBClient::class);
        $mockDbClient->expects($this->once())->method('saveVideos')->with([['id' => '456', 'title' => 'Fake Video']], '123');

        $service = new TopVideoService($mockDbClient, $mockApiClient, $mockTokenProvider);
        $service->execute('123');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExecuteThrowsExceptionWhenNoToken()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch');

        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));

        $mockApiClient = $this->createMock(ApiClient::class);
        $mockDbClient = $this->createMock(DBClient::class);

        $service = new TopVideoService($mockDbClient, $mockApiClient, $mockTokenProvider);
        $service->execute('123');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExecuteThrowsExceptionWhenNoVideos()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se encontraron datos válidos en la respuesta de la API de Twitch.');

        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');

        $mockApiClient = $this->createMock(ApiClient::class);
        $mockApiClient->method('updateVideos')->willReturn([]);

        $mockDbClient = $this->createMock(DBClient::class);

        $service = new TopVideoService($mockDbClient, $mockApiClient, $mockTokenProvider);
        $service->execute('123');
    }
}
