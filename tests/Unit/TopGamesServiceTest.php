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
    protected TwitchTokenProvider $tokenProvider;
    protected ApiClient $apiClient;
    protected DBClient $dbClient;
    protected TopGamesService $service;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenProvider = $this->createMock(TwitchTokenProvider::class);
        $this->apiClient     = $this->createMock(ApiClient::class);
        $this->dbClient      = $this->createMock(DBClient::class);

        $this->service = new TopGamesService($this->dbClient, $this->apiClient, $this->tokenProvider);
    }

    /**
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ConnectionException
     */
    public function itShouldSaveGamesSuccessfullyWhenTokenAndGamesAreValid()
    {
        $this->tokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->apiClient->method('updateGames')->willReturn([['id' => '123', 'name' => 'Fake Game']]);
        $this->dbClient->expects($this->once())->method('saveGames')->with([['id' => '123', 'name' => 'Fake Game']]);

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
        $this->tokenProvider->method('getTokenFromTwitch')->will($this->throwException(new Exception('Failed to retrieve access token from Twitch')));

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
        $this->tokenProvider->method('getTokenFromTwitch')->willReturn('fake_token');
        $this->apiClient->method('updateGames')->willReturn([]);

        $this->service->execute();
    }
}
