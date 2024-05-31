<?php

namespace App\Services;

use App\Infrastructure\Clients\ApiClient;
use App\Providers\TwitchTokenProvider;
use Illuminate\Http\Client\ConnectionException;

class GetStreamsService
{
    protected ApiClient $apiClient;
    private mixed $twitchStreamsUrl;

    private TwitchTokenProvider $tokenProvider;

    public function __construct(ApiClient $apiClient = null, $twitchStreamsUrl = null)
    {
        $this->tokenProvider    = $tokenProvider    ?? new TwitchTokenProvider();
        $this->apiClient        = $apiClient        ?? new ApiClient($tokenProvider);
        $this->twitchStreamsUrl = $twitchStreamsUrl ?? env('TWITCH_URL') . '/streams';
    }

    /**
     * @throws ConnectionException
     */
    public function execute(): \Illuminate\Http\JsonResponse
    {
        $tokenFromTwitch = $this->tokenProvider->getTokenFromTwitch();
        $streamsResponse = $this->apiClient->sendCurlPetitionToTwitch($this->twitchStreamsUrl, $tokenFromTwitch);

        return $this->treatData($streamsResponse['body']);
    }

    protected function treatData($rawData): \Illuminate\Http\JsonResponse
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
