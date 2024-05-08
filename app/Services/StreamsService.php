<?php

namespace App\Services;

class StreamsService
{
    protected $getStreamsService;

    public function __construct(GetStreamsService $getStreamsService)
    {
        $this->getStreamsService = $getStreamsService;
    }

    public function execute(){
        $activeStreamsWithoutTreatment = json_decode($this->getStreamsService->execute(), true);

        $treatedStreams = [];

        if (!empty($activeStreamsWithoutTreatment['data'])) {
            foreach ($activeStreamsWithoutTreatment['data'] as $stream) {
                $treatedStreams[] = [
                    'title' => $stream['title'],
                    'user_name' => $stream['user_name'],
                ];
            }
        }

        return response()->json($treatedStreams)
            ->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
