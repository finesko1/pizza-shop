<?php

namespace App\Http\Requests;

use App\Services\Auth\DTO\LoginDTO;

class LoginRequest extends PublicFormRequest
{
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toDTO(): LoginDTO
    {
        return LoginDTO::fromArray($this->validated());
    }
}
