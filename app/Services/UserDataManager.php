<?php

namespace App\Services;

use App\Infrastructure\Clients\DBClient;

class UserDataManager
{
    protected DBClient $dBClient;
    protected GetUserService $getUserService;

    public function __construct(GetUserService $getUserService, DBClient $dBClient)
    {
        $this->getUserService = $getUserService;
        $this->dBClient       = $dBClient;
    }

    /**
     * @throws \Exception
     */
    public function execute(String $userId)
    {
        $userFromDB = $this->dBClient->getUserByIdFromDB($userId);
        if (!$userFromDB) {
            return $this->getUserService->execute($userId);
        }
        return response()->json($userFromDB)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
