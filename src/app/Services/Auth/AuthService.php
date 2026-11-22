<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Auth\DTO\LoginDTO;
use App\Services\Auth\DTO\RegisterDTO;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Tymon\JWTAuth\JWTGuard;

final readonly class AuthService
{
    public function __construct(
        private AuthFactory $auth,
    ) {}

    public function login(LoginDTO $data): ?string
    {
        return $this->guard()->attempt(credentials: $data->toArray()) ?: null;
    }

    public function register(RegisterDTO $data): string
    {
        $user = User::create($data->toArray())->refresh();

        return $this->guard()->login(user: $user);
    }

    public function user(): ?User
    {
        $user = $this->guard()->user();

        return $user instanceof User ? $user : null;
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    public function refresh(): string
    {
        $guard = $this->guard();
        $token = $guard->refresh();
        $guard->setToken($token);

        return $token;
    }

    public function tokenTtl(): int
    {
        return (int) $this->guard()->factory()->getTTL() * 60;
    }

    private function guard(): JWTGuard
    {
        $guard = $this->auth->guard('api');

        if (! $guard instanceof JWTGuard) {
            throw new \LogicException(message: 'Guard "api" must be an instance of ' . JWTGuard::class . '.');
        }

        return $guard;
    }
}
