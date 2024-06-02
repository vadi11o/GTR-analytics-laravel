<?php

use App\Services\TopGamesService;
use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\Client\ConnectionException;

class TopGamesServiceTest extends TestCase
{
    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function executeSuccessfully()
    {
        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');

        $mockApiClient = $this->createMock(ApiClient::class);
        $mockApiClient->method('updateGames')->willReturn([['id' => '123', 'name' => 'Fake Game']]);

        $mockDbClient = $this->createMock(DBClient::class);
        $mockDbClient->expects($this->once())->method('saveGames')->with([['id' => '123', 'name' => 'Fake Game']]);

        $service = new TopGamesService($mockDbClient, $mockApiClient, $mockTokenProvider);
        $service->execute();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function testExecuteThrowsExceptionWhenNoToken()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch');

        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));

        $mockApiClient = $this->createMock(ApiClient::class);
        $mockDbClient = $this->createMock(DBClient::class);

        $service = new TopGamesService($mockDbClient, $mockApiClient, $mockTokenProvider);
        $service->execute();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function testExecuteThrowsExceptionWhenNoGames()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se encontraron datos válidos en la respuesta de la API de Twitch.');

        $mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');

        $mockApiClient = $this->createMock(ApiClient::class);
        $mockApiClient->method('updateGames')->willReturn([]);

        $mockDbClient = $this->createMock(DBClient::class);

        $service = new TopGamesService($mockDbClient, $mockApiClient, $mockTokenProvider);
        $service->execute();
    }
}
