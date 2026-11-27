<?php

namespace App\Console\Commands;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-admin')]
#[Description('Command description')]
class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {--email= : Email администратора}
        {--name= : Имя администратора}
        {--password= : Пароль для неинтерактивного запуска}
        {--force : Не спрашивать подтверждение в production}';

    protected $description = 'Создать администратора';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: $this->ask('Email'));
        $name = (string) ($this->option('name') ?: $this->ask('Имя', 'Administrator'));
        $password = (string) ($this->option('password') ?: $this->secret('Пароль'));

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->option('force')) {
            if (! $this->confirm("Создать администратора {$email} в production?")) {
                return self::FAILURE;
            }
        }

        $user = new User();
        $user->forceFill([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => UserRoleEnum::Admin,
        ])->save();

        $this->info("Администратор создан: {$user->email}");

        return self::SUCCESS;
    }
}
