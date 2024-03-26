<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Token;


class TwitchTokenService
{
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->clientId = env('TWITCH_CLIENT_ID');
        $this->clientSecret = env('TWITCH_CLIENT_SECRET');
    }

    public function getTokenFromTwitch()
    {
        $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        return $response->json()['access_token'] ?? null;
    }

    public function updateTokenDataBase($newToken)
    {
        if (!$newToken) {
            throw new \Exception("Error al solicitar el token.");
        }

        $tokenModel = Token::find(1); // Asume que el ID del token que quieres actualizar es 1
        if ($tokenModel) {
            $tokenModel->access_token = $newToken;
            $tokenModel->save();
            echo "Token actualizado con éxito.";
        } else {
            echo "Token no encontrado.";
        }
    }
}
