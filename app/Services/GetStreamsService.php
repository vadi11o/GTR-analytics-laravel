<?php

namespace App\Services;

use App\Infrastructure\Clients\ApiClient;
use Illuminate\Http\Client\ConnectionException;

class GetStreamsService
{
    protected ApiClient $apiClient;
    private mixed $twitchStreamsUrl;

    public function __construct(ApiClient $apiClient = null, $twitchStreamsUrl = null)
    {
        $this->apiClient        = $apiClient        ?? new ApiClient(new TokenProvider());
        $this->twitchStreamsUrl = $twitchStreamsUrl ?? env('TWITCH_URL') . '/streams';
    }

    /**
     * @throws ConnectionException
     */
    public function execute(): \Illuminate\Http\JsonResponse
    {
        $streamsResponse = $this->apiClient->sendCurlPetitionToTwitch($this->twitchStreamsUrl);

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
