<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    // POST /api/auth/register (register)
    public function testRegisterWithCorrectCredentials(): void
    {
        $payload = [
            'name' => 'name',
            'email' => 'email@mail.ru',
            'password' => 'currentpassword',
            'password_confirmation' => 'currentpassword',
        ];

        $response = $this->postJson('api/auth/register', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role']
            ])
            ->assertJsonPath('user.role', UserRoleEnum::Guest->value);

        self::assertNotEmpty($response->json(key: 'access_token'));

        $this->withHeader('Authorization', 'Bearer ' . $response->json(key: 'access_token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(path: 'name', expect: $payload['name'])
            ->assertJsonPath(path: 'email', expect: $payload['email'])
            ->assertJsonPath(path: 'role', expect: UserRoleEnum::Guest->value);

        $this->postJson('/api/auth/login', [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ])
            ->assertOk()
            ->assertJsonPath('user.role', UserRoleEnum::Guest->value);

        $this->assertDatabaseHas('users', [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => UserRoleEnum::Guest->value,
        ]);
    }

    public function testRegisterWithNonUniqueCredentials(): void
    {
        User::create([
            'name' => 'name',
            'email' => 'email@mail.ru',
            'password' => 'currentpassword',
        ]);

        $payload = [
            'name' => 'name',
            'email' => 'email@mail.ru',
            'password' => 'currentpassword',
            'password_confirmation' => 'currentpassword',
        ];

        $response = $this->postJson('api/auth/register', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(errors: ['email']);
    }

    // POST /api/auth/login (login)
    public function testLoginWithCorrectCredentials(): void
    {
        $user = User::factory()->create(attributes: [
            'name' => 'name',
            'email' => 'email@mail.com',
            'password' => 'currentpassword',
        ]);

        $payload = [
            'email' => 'email@mail.com',
            'password' => 'currentpassword',
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role'],
            ]);

        self::assertNotEmpty($response->json(key: 'access_token'));

        $this->withHeader('Authorization', 'Bearer ' . $response->json(key: 'access_token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(path: 'name', expect: $user->name)
            ->assertJsonPath(path: 'email', expect: $user->email);
    }

    public function testLoginWithIncorrectCredentials(): void
    {
        $user = User::factory()->create(attributes: [
            'name' => 'name',
            'email' => 'email@mail.com',
            'password' => 'currentpassword',
        ]);

        $payload = [
            'email' => 'email@mail.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertUnauthorized();
    }

    public function test_refresh_works_with_expired_access_token_within_refresh_ttl(): void
    {
        $user = User::factory()->user()->create();
        $oldToken = $this->tokenFor($user);

        $this->travel(61)->minutes();

        $response = $this->withToken($oldToken)
            ->postJson('/api/auth/refresh');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJsonPath('user.id', $user->id);

        $newToken = $response->json('access_token');

        $this->assertNotSame($oldToken, $newToken);

        $this->withToken($newToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->travelBack();
    }

    public function test_refresh_without_token_returns_401(): void
    {
        $this->postJson('/api/auth/refresh')
            ->assertUnauthorized();
    }

    public function test_refresh_fails_after_refresh_ttl(): void
    {
        $token = $this->tokenFor(User::factory()->user()->create());

        $this->travel(20161)->minutes();

        $this->withToken($token)
            ->postJson('/api/auth/refresh')
            ->assertUnauthorized();

        $this->travelBack();
    }
}
