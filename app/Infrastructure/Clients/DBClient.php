<?php

namespace App\Infrastructure\Clients;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class DBClient
{
    private mixed $clientId;
    private mixed $clientSecret;

    public function __construct($clientId = null, $clientSecret = null)
    {
        $this->clientId     = $clientId     ?? env('TWITCH_CLIENT_ID');
        $this->clientSecret = $clientSecret ?? env('TWITCH_CLIENT_SECRET');
    }
    public function getUserByIdFromDB(String $userId)
    {
        $user = User::where('twitch_id', $userId)->first();

        if ($user) {
            $user->makeHidden(['id']);

            $user  = $user->toArray();
            $newId = $user['twitch_id'];
            unset($user['twitch_id']);

            $user = ['id' => $newId] + $user;

            return $user;
        }
        return null;
    }
    public function insertUserToDB(array $userData): void
    {
        User::create($userData);
    }
    public function getTokenFromTwitch()
    {
        try {
            $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
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
