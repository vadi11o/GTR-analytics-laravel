<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\StreamsDataManager;
use Illuminate\Http\JsonResponse;
use Exception;

class StreamsTest extends TestCase
{
    /** @test
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception
     */
    public function testStreamsReturnsProcessedJsonResponse()
    {
        $mockData = [
            ['title' => 'MSI MAIN EVENT GENG VS TES - #MSI2024', 'user_name' => 'Caedrel'],
            ['title' => '#ZLAN2024 : 2e jour ! En direct de Montpellier, 198 joueurs s\'affrontent pour 52024€ de cashprize', 'user_name' => 'ZeratoR'],
            ['title' => 'GEN vs TES | DAY 10 | MSI 2024', 'user_name' => 'Riot Games'],
        ];

        $mockService = $this->createMock(StreamsDataManager::class);
        $mockService->expects($this->once())
            ->method('execute')
            ->willReturn(new JsonResponse($mockData, 200));

        $this->app->instance(StreamsDataManager::class, $mockService);

        $response = $this->getJson('analytics/streams');

        $response->assertStatus(200);
        $response->assertJson($mockData);
        $response->assertJsonStructure([
            '*' => ['title', 'user_name']
        ]);
    }

    /** @test
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testStreamsReturnsErrorResponse()
    {
        $mockService = $this->createMock(StreamsDataManager::class);
        $mockService->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new Exception('Failed to process stream data')));

        $this->app->instance(StreamsDataManager::class, $mockService);

        $response = $this->getJson('analytics/streams');

        $response->assertStatus(503);
        $response->assertJson([
            'error' => 'No se pueden devolver streams en este momento, inténtalo más tarde'
        ]);
    }
}
