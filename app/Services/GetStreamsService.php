<?php

namespace App\Services;

use App\Infrastructure\Clients\ApiClient;
use App\Providers\TwitchTokenProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

class GetStreamsService
{
    protected ApiClient $apiClient;
    private TwitchTokenProvider $tokenProvider;

    public function __construct(ApiClient $apiClient = null, TwitchTokenProvider $tokenProvider = null)
    {
        $this->tokenProvider = $tokenProvider ?? new TwitchTokenProvider();
        $this->apiClient     = $apiClient     ?? new ApiClient($this->tokenProvider);
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
