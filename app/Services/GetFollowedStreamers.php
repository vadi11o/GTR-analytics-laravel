<?php

namespace App\Services;

use App\Infrastructure\Clients\ApiClient;
use PhpParser\Node\Expr\Array_;

class GetFollowedStreamers
{
    protected ApiClient $apiClient;
    private mixed $twitchStreamsUrl;

    public function __construct(ApiClient $apiClient = null, $twitchStreamsUrl = null)
    {
        $this->apiClient        = $apiClient        ?? new ApiClient(new TokenProvider());
        $this->twitchStreamsUrl = $twitchStreamsUrl ?? env('TWITCH_URL') . '/streams/followed?user_id=';
    }

    public function execute(String $userId): array
    {
        $this->twitchStreamsUrl = $this->twitchStreamsUrl . $userId;
        return $this->apiClient->fetchUserFollowedStreamers($this->twitchStreamsUrl);
    }
}
