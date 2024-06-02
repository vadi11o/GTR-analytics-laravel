<?php

use App\Services\TopGamesService;
use App\Infrastructure\Clients\ApiClient;
use App\Infrastructure\Clients\DBClient;
use App\Providers\TwitchTokenProvider;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\Client\ConnectionException;
use Exception;

class TopGamesServiceTest extends TestCase
{
    protected TwitchTokenProvider $mockTokenProvider;
    protected ApiClient $mockApiClient;
    protected DBClient $mockDbClient;
    protected TopGamesService $service;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->mockApiClient     = $this->createMock(ApiClient::class);
        $this->mockDbClient      = $this->createMock(DBClient::class);

        $this->service = new TopGamesService($this->mockDbClient, $this->mockApiClient, $this->mockTokenProvider);
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function itShouldSaveGamesSuccessfullyWhenTokenAndGamesAreValid()
    {
        $this->mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->mockApiClient->method('updateGames')->willReturn([['id' => '123', 'name' => 'Fake Game']]);
        $this->mockDbClient->expects($this->once())->method('saveGames')->with([['id' => '123', 'name' => 'Fake Game']]);

        $this->service->execute();
    }

    /**
     * @test
     * @throws ConnectionException
     */
    public function itShouldThrowExceptionWhenTokenRetrievalFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve access token from Twitch');
        $this->mockTokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));

        $this->service->execute();
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function itShouldThrowExceptionWhenNoGamesAreFoundInApiResponse()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se encontraron datos válidos en la respuesta de la API de Twitch.');
        $this->mockTokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->mockApiClient->method('updateGames')->willReturn([]);

        $this->service->execute();
    }
}
