<?php
namespace App\Infrastructure\Controllers;

use App\Services\StreamsDataManager;
use Illuminate\Http\JsonResponse;

class StreamsController extends Controller
{
    private $getsStreams;

    public function __construct(StreamsDataManager $streamsDataManager)
    {
        $this->getsStreams = $streamsDataManager;
    }

    public function __invoke():jsonResponse
    {
        return $this->getsStreams->execute();
    }
}
