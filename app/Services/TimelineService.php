<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;
use App\Managers\TwitchManager;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TimelineService
{
    protected DBClient $dbClient;
    protected TwitchManager $apiClient;

    public function __construct(DBClient $dbClient, TwitchManager $apiClient)
    {
        $this->dbClient  = $dbClient;
        $this->apiClient = $apiClient;
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function execute($userId): array
    {
        $userAnalytics = $this->dbClient->getUserAnalyticsByIdFromDB($userId);

        if (!$userAnalytics) {
            throw new NotFoundHttpException('User not found');
        }

        $followedStreamers = json_decode($userAnalytics->followed_streamers, true);
        return $this->sortStreams($followedStreamers);
    }

    /**
     * @throws ConnectionException
     */
    public function sortStreams($followedStreamers): array
    {
        $streams = [];

        foreach ($followedStreamers as $streamer) {
            $streamerId   = $streamer['id'];
            $streamerName = $streamer['display_name'];

            $streamData = $this->apiClient->getStreamsByUserId($streamerId);

            $streamData = array_slice($streamData, 0, 5);

            foreach ($streamData as $stream) {
                $streams[] = [
                    'streamerId'   => $streamerId,
                    'streamerName' => $streamerName,
                    'title'        => $stream['title'],
                    'viewerCount'  => $stream['view_count'],
                    'startedAt'    => $stream['created_at'],
                ];
            }
        }
        usort($streams, function ($streamA, $streamB) {
            return strtotime($streamB['startedAt']) - strtotime($streamA['startedAt']);
        });
        return $streams;
    }
}
