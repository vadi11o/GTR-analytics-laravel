<?php

namespace App\Services;

use App\Http\Clients\DBClient;
class UserService
{
    protected DBClient $dBClient;
    protected GetUserService $getUserService;

    public function __construct(GetUserService $getUserService, DBClient $dBClient)
    {
        $this->getUserService = $getUserService;
        $this->dBClient = $dBClient;
    }

    public function execute(String $userId)
    {
        $userFromDB = $this->dBClient->getUserByIdFromDB($userId);
        if (!$userFromDB) {
            return response()->json($this->getUserService->execute($userId))->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return response()->json($userFromDB)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

}
