<?php

namespace App\Services;

use App\Infrastructure\Clients\ApiClient;

class GetStreamsService
{
    protected $apiClient;
    protected $twitchStreamsUrl;

    public function __construct(ApiClient $apiClient = null, $twitchStreamsUrl = null)
    {
        $this->apiClient = $apiClient ?? new ApiClient();
        $this->twitchStreamsUrl = $twitchStreamsUrl ?? env('TWITCH_URL') . '/streams';
    }

    public function execute()
    {
        $tokenFromTwitch = $this->apiClient->getTokenFromTwitch();
        $streamsResponse = $this->apiClient->sendCurlPetitionToTwitch($this->twitchStreamsUrl, $tokenFromTwitch);

        return $this->treatData($streamsResponse['body']);
    }

    protected function treatData($rawData)
    {
        $data = json_decode($rawData, true);
        $treatedStreams = [];

        if (!empty($data['data'])) {
            foreach ($data['data'] as $stream) {
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
