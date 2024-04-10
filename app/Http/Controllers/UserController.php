<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\TwitchTokenService;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->query('id');
        if (!$userId) {
            return response()->json(['error' => 'El parámetro "id" no se proporcionó en la URL.'], 400);
        }

        $user = User::where('twitch_id', $userId)->first();

        if (!$user) {
            $tokenService = new TwitchTokenService();
            $token = $tokenService->getTokenFromTwitch();

            if (!$token) {
                return response()->json(['error' => 'No se pudo obtener el token de acceso desde la base de datos.'], 500);
            }

            $userData = $this->fetchUserDataFromTwitch($token, $userId);

            if ($userData) {
                User::create($userData);
                return response()->json($userData)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                return response()->json(['error' => 'No se encontraron datos de usuario para el ID proporcionado.'], 404);
            }
        }

        return response()->json($user)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function fetchUserDataFromTwitch($token, $userId)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Client-Id' => env('TWITCH_CLIENT_ID'),
        ])->get("https://api.twitch.tv/helix/users?id=$userId");

        if ($response->successful()) {
            $data = $response->json();
            $user = $data['data'][0] ?? null;
            if ($user) {
                return [
                    'twitch_id' => $user['id'],
                    'login' => $user['login'],
                    'display_name' => $user['display_name'],
                    'type' => $user['type'],
                    'broadcaster_type' => $user['broadcaster_type'],
                    'description' => $user['description'],
                    'profile_image_url' => $user['profile_image_url'],
                    'offline_image_url' => $user['offline_image_url'],
                    'view_count' => $user['view_count'],
                ];
            }
        }

        return null;
    }
}

