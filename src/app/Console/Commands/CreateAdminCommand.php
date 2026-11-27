<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-admin
    {--email= : Email администратора}
    {--name= : Имя администратора}
    {--password= : Пароль для неинтерактивного запуска}
    {--force : Не спрашивать подтверждение в production}')]
#[Description('Создать администратора')]
final class CreateAdminCommand extends Command
{
    public function handle(): int
    {
        $email = (string) ($this->option(key: 'email') ?: $this->ask(question: 'Email'));
        $name = (string) ($this->option(key: 'name') ?: $this->ask(question: 'Имя', default: 'Administrator'));
        $password = (string) ($this->option(key: 'password') ?: $this->secret(question: 'Пароль'));

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error(string: $error);
            }

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->option(key: 'force')) {
            if (! $this->confirm(question: "Создать администратора {$email} в production?")) {
                return self::FAILURE;
            }
        }

        $user = new User();
        $user->forceFill(attributes: [
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => UserRoleEnum::Admin,
        ])->save();

        $this->info(string: "Администратор создан: {$user->email}");

        return self::SUCCESS;
    }
}
