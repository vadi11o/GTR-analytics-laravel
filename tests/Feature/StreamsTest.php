<?php

namespace Tests\Feature;

use App\Infrastructure\Clients\ApiClient;
use App\Services\GetStreamsService;
use Tests\TestCase;
use Exception;

class StreamsTest extends TestCase
{
    /** @test
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception
     */
    public function GetsStreams()
    {
        $mockApiResponse = [
            'data' => [
                ['title' => 'MSI MAIN EVENT GENG VS TES - #MSI2024', 'user_name' => 'Caedrel'],
                ['title' => '#ZLAN2024 : 2e jour ! En direct de Montpellier, 198 jugadores s\'affrontent pour 52024€ de cashprize', 'user_name' => 'ZeratoR'],
                ['title' => 'GEN vs TES | DAY 10 | MSI 2024', 'user_name' => 'Riot Games'],
            ]
        ];
        $expectedResponse = [
            ['title' => 'MSI MAIN EVENT GENG VS TES - #MSI2024', 'user_name' => 'Caedrel'],
            ['title' => '#ZLAN2024 : 2e jour ! En direct de Montpellier, 198 jugadores s\'affrontent pour 52024€ de cashprize', 'user_name' => 'ZeratoR'],
            ['title' => 'GEN vs TES | DAY 10 | MSI 2024', 'user_name' => 'Riot Games'],
        ];
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->expects($this->once())
            ->method('fetchStreamsFromTwitch')
            ->willReturn([
                'status' => 200,
                'body'   => json_encode($mockApiResponse)
            ]);
        $streamsService = new GetStreamsService($apiClient);
        $this->app->instance(GetStreamsService::class, $streamsService);

        $response = $this->getJson('analytics/streams');

        $response->assertStatus(200);
        $response->assertJson($expectedResponse);
        $response->assertJsonStructure([
            '*' => ['title', 'user_name']
        ]);
    }
}
