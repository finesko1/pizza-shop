<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // POST /api/auth/register (register)
    public function test_register_with_correct_credentials(): void
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
            ]);

        $this->assertNotEmpty($response->json('access_token'));

        $this->withHeader('Authorization', 'Bearer ' . $response->json('access_token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('name', $payload['name'])
            ->assertJsonPath('email', $payload['email']);

        $this->assertDatabaseHas('users', [
            'name' => $payload['name'],
            'email' => $payload['email'],
        ]);
    }

    public function test_register_with_non_unique_credentials(): void
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
            ->assertJsonValidationErrors(['email']);
    }

    //POST /api/auth/login (login)
    public function test_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'name' => 'name',
            'email' => 'email@mail.com',
            'password' => 'currentpassword'
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
                'user' => ['id', 'name', 'email', 'role']
            ]);

        $this->assertNotEmpty($response->json('access_token'));

        $this->withHeader('Authorization', 'Bearer ' . $response->json('access_token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('name', $user->name)
            ->assertJsonPath('email', $user->email);
    }

    public function test_login_with_incorrect_credentials(): void
    {
        $user = User::factory()->create([
            'name' => 'name',
            'email' => 'email@mail.com',
            'password' => 'currentpassword'
        ]);

        $payload = [
            'email' => 'email@mail.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('api/auth/login', $payload);

        $response->assertUnauthorized();
    }
}
