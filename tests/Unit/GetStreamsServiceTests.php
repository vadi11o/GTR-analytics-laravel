<?php

namespace Tests\Unit;

use App\Infrastructure\Clients\ApiClient;
use ReflectionClass;
use Tests\TestCase;
use App\Services\GetStreamsService;
use Mockery;



class GetStreamsServiceTests extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function testTreatDataFormatsDataCorrectly()
    {
        $service = new GetStreamsService(new ApiClient());

        $reflection = new ReflectionClass(GetStreamsService::class);
        $method = $reflection->getMethod('treatData');

        $rawData = json_encode(['data' => [
            ['title' => 'Stream 1', 'user_name' => 'Streamer1'],
            ['title' => 'Stream 2', 'user_name' => 'Streamer2']
        ]]);

        $result = $method->invokeArgs($service, [$rawData]);

        $expectedJson = json_encode([
            ['title' => 'Stream 1', 'user_name' => 'Streamer1'],
            ['title' => 'Stream 2', 'user_name' => 'Streamer2']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expectedJson, $result->getContent());
    }
    public function testExecuteReturnsProcessedData()
    {
        $mockApiClient = $this->createMock(ApiClient::class);
        $mockApiClient->method('getTokenFromTwitch')->willReturn('dummy-token');
        $mockApiClient->method('sendCurlPetitionToTwitch')->willReturn([
            'body' => json_encode(['data' => [
                ['title' => 'Stream 1', 'user_name' => 'Streamer1'],
                ['title' => 'Stream 2', 'user_name' => 'Streamer2']
            ]])
        ]);

        $service = new GetStreamsService($mockApiClient);
        $result = $service->execute();

        $expectedJson = json_encode([
            ['title' => 'Stream 1', 'user_name' => 'Streamer1'],
            ['title' => 'Stream 2', 'user_name' => 'Streamer2']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expectedJson, $result->getContent());
    }
}
