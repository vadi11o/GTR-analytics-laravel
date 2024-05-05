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
        $activeStreams = json_decode($this->getStreamsService->execute(), true);

        $streams = [];

        if (!empty($activeStreams['data'])) {
            foreach ($activeStreams['data'] as $stream) {
                $streams[] = [
                    'title' => $stream['title'],
                    'user_name' => $stream['user_name'],
                ];
            }
        } else {
            return ['message' => 'No hay streams activos en este momento.'];
        }

        return response()->json($streams)
            ->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
