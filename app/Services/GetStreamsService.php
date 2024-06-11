<?php

namespace App\Services;

use App\Managers\TwitchManager;
use App\Providers\TwitchTokenProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

class GetStreamsService
{
    protected TwitchManager $apiClient;
    private TwitchTokenProvider $tokenProvider;

    public function __construct(TwitchManager $apiClient = null, TwitchTokenProvider $tokenProvider = null)
    {
        $this->tokenProvider = $tokenProvider ?? new TwitchTokenProvider();
        $this->apiClient     = $apiClient     ?? new TwitchManager($this->tokenProvider);
    }

    /**
     * @throws ConnectionException
     * @throws \Exception
     */
    public function execute(): JsonResponse
    {
        $streamsResponse = $this->apiClient->fetchStreamsFromTwitch();

        return $this->treatData($streamsResponse['body']);
    }

    private function treatData($rawData): JsonResponse
    {
        $data           = json_decode($rawData, true);
        $treatedStreams = [];

        if (!empty($data['data'])) {
            foreach ($data['data'] as $stream) {
                $treatedStreams[] = [
                    'title'     => $stream['title'],
                    'user_name' => $stream['user_name'],
                ];
            }
        }

        return response()->json($treatedStreams)
            ->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
