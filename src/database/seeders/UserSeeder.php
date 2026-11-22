<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['name' => 'admin'],
            [
                'email'     => 'admin@mail.ru',
                'role'      => UserRoleEnum::Admin,
                'password'  => 'adminpassword'
            ]
        );

        User::query()->firstOrCreate(
            ['name' => 'user'],
            [
                'email'     => 'user@mail.ru',
                'role'      => UserRoleEnum::User,
                'password'  => 'userpassword'
            ]
        );

        User::query()->firstOrCreate(
            ['name' => 'guest'],
            [
                'email'     => 'guest@mail.ru',
                'role'      => UserRoleEnum::Guest,
                'password'  => 'guestpassword'
            ]
        );

        if (app()->environment(['local', 'testing'])) {
            User::factory()->count(10)->create();
        }
    }
}
