<?php

namespace App\Managers;

use App\Infrastructure\Clients\DBClient;
use App\Services\GetStreamerService;
use Exception;

class StreamerDataManager
{
    protected DBClient $dBClient;
    protected GetStreamerService $getStreamerService;

    public function __construct(GetStreamerService $getStreamerService, DBClient $dBClient)
    {
        $this->getStreamerService = $getStreamerService;
        $this->dBClient           = $dBClient;
    }

    /**
     * @throws Exception
     */
    public function execute(String $streamerId)
    {
        $streamerFromDB = $this->dBClient->getStreamerByIdFromDB($streamerId);
        if (!$streamerFromDB) {
            return $this->getStreamerService->execute($streamerId);
        }
        return response()->json($streamerFromDB)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
