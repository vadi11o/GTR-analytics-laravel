<?php

namespace Tests\Unit\Managers;

use Exception;
use Tests\TestCase;
use App\Managers\StreamerDataManager;
use App\Infrastructure\Clients\DBClient;
use App\Services\GetStreamerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class StreamerDataManagerTest extends TestCase
{
    protected $dbClient;
    protected $getStreamerService;
    protected $streamerDataManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbClient           = $this->createMock(DBClient::class);
        $this->getStreamerService = $this->createMock(GetStreamerService::class);

        $this->streamerDataManager = new StreamerDataManager($this->getStreamerService, $this->dbClient);

        Response::shouldReceive('json')
            ->andReturnUsing(function ($data, $status = 200) {
                return new JsonResponse($data, $status);
            });
    }
    /**
     * @test
     * @throws Exception
     */
    public function returnsStreamerIfOnDB()
    {
        $streamerId       = '123';
        $expectedStreamer = ['id' => $streamerId, 'name' => 'Streamer Name'];
        $this->dbClient->expects($this->once())
            ->method('getStreamerByIdFromDB')
            ->with($this->equalTo($streamerId))
            ->willReturn($expectedStreamer);

        $response     = $this->streamerDataManager->execute($streamerId);
        $responseData = json_decode($response->getContent(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($expectedStreamer, $responseData);
        $this->assertEquals(200, $response->status());
    }
    /**
     * @test
     * @throws Exception
     */
    public function errorIfStreamerIdNotFound()
    {
        $streamerId  = 'unknown_id';
        $fetchedData = ['id' => $streamerId, 'name' => 'New Streamer'];
        $this->dbClient->method('getStreamerByIdFromDB')
            ->willReturn(null);
        $this->getStreamerService->expects($this->once())
            ->method('execute')
            ->willReturn(new JsonResponse($fetchedData));

        $response = $this->streamerDataManager->execute($streamerId);

        $this->assertEquals(json_encode($fetchedData), $response->getContent());
        $this->assertEquals(200, $response->status());
    }
    /**
     * @test
     * @throws Exception
     */
    public function errorIfUserIdDoesNotExist()
    {
        $streamerId = 'unknown_id';
        $this->dbClient->method('getStreamerByIdFromDB')
            ->willReturn(null);
        $this->getStreamerService->method('execute')
            ->willThrowException(new Exception('No se encontraron datos de usuario para el ID proporcionado.'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se encontraron datos de usuario para el ID proporcionado.');

        $this->streamerDataManager->execute($streamerId);
    }
}
