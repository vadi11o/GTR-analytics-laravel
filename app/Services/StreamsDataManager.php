<?php

namespace App\Services;

class StreamsDataManager
{
    protected GetStreamsService $getStreamsService;

    public function __construct(GetStreamsService $getStreamsService)
    {
        $this->getStreamsService = $getStreamsService;
    }

    public function execute(): \Illuminate\Http\JsonResponse
    {
        return $this->getStreamsService->execute();
    }
}
