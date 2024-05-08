<?php

namespace App\Services;

class StreamsDataManager
{
    protected $getStreamsService;

    public function __construct(GetStreamsService $getStreamsService)
    {
        $this->getStreamsService = $getStreamsService;
    }

    public function execute()
    {
        return $this->getStreamsService->execute();
    }
}
