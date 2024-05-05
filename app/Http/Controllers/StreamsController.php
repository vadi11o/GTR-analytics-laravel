<?php
namespace App\Http\Controllers;

use App\Services\StreamsService;
use Illuminate\Http\JsonResponse;

class StreamsController extends Controller
{
    private $StreamsService;

    public function __construct(StreamsService $StreamsService)
    {
        $this->StreamsService = $StreamsService;
    }

    public function index():jsonResponse
    {
        $streams = $this->StreamsService->execute();

        return $streams;

    }


}
