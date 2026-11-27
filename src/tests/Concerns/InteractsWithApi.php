<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait InteractsWithApi
{
    /**
     * Выпуск токена без кеширования в guard.
     */
    protected function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }
}
