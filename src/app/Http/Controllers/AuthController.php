<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\DTO\RegisterDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->auth->login($request->toDTO());

        if ($token === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, $this->auth->user());
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->auth->register(RegisterDTO::fromArray($request->validated()));

        return $this->respondWithToken($token, $this->auth->user(), Response::HTTP_CREATED);
    }

    public function me(): JsonResponse
    {
        return response()->json($this->auth->user());
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken($this->auth->refresh(), $this->auth->user());
    }

    protected function respondWithToken(string $token, ?User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->auth->tokenTtl(),
            'user' => $user ? new UserResource($user) : null,
        ], $status);
    }
}
