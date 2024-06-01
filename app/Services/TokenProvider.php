<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class TokenProvider
{
    public function __construct()
    {

    }
    public function getTokenFromTwitch()
    {
        try {
            $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
                'client_id'     => env('TWITCH_CLIENT_ID'),
                'client_secret' => env('TWITCH_CLIENT_SECRET'),
                'grant_type'    => 'client_credentials',
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }
            if ($response->status() === 500) {
                throw new Exception('Error al conectar con Twitch');
            }
            return null;

        } catch (\Exception) {
            return response()->json(['error' => 'No se puede establecer conexión con Twitch en este momento'], 503);
        }
    }
}
