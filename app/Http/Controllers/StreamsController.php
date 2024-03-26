<?php
namespace App\Http\Controllers;

use App\Models\Token;
use App\Services\TwitchTokenService; // Asegúrate de importar tu servicio
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class StreamsController extends Controller
{
    protected $twitchTokenService;

    public function __construct(TwitchTokenService $twitchTokenService)
    {
        $this->twitchTokenService = $twitchTokenService;
    }

    public function index()
    {
        // Aquí asumo que adaptas Token::obtainFromDatabase() para que use el servicio.
        $token = $this->twitchTokenService->getTokenFromTwitch();

        if (!$token) {
            return response()->json(['error' => 'No se pudo obtener el token de Twitch.'], 400);
        }

        $url = env('TWITCH_URL') . '/streams';
        $response = $this->curlPetition($url, $token, env('TWITCH_CLIENT_ID'));

        if ($response['status'] != 200) {
            return response()->json(['error' => 'Error al comunicarse con Twitch'], $response['status']);
        }

        $activeStreams = $this->verifyActiveStreams(json_decode($response['body'], true));

        return response()->json($activeStreams);
    }

    protected function curlPetition($url, $token, $client_id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Client-Id' => $client_id,
        ])->get($url);

        return [
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }

    protected function verifyActiveStreams($data)
    {
        $streams = [];

        if (isset($data['data']) && !empty($data['data'])) {
            foreach ($data['data'] as $stream) {
                $streams[] = [
                    'title' => $stream['title'],
                    'user_name' => $stream['user_name'],
                    // Añade más campos según necesites
                ];
            }
        } else {
            return ['message' => 'No hay streams activos en este momento.'];
        }

        return $streams;
    }
}