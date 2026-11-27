<?php

declare(strict_types=1);

namespace App\Models\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ValidatesBigIntRouteBinding
{
    public function resolveRouteBinding(mixed $value, mixed $field = null): ?Model
    {
        if (($field ?? $this->getRouteKeyName()) === 'id') {
            $modelName = class_basename(class: $this);

            if (! \is_string(value: $value) && ! \is_int(value: $value)) {
                $this->throwNotFound($modelName, 'ID должен быть строкой или целым числом.');
            }

            $stringValue = (string) $value;

            if (! ctype_digit(text: $stringValue) || str_starts_with(haystack: $stringValue, needle: '0')) {
                $this->throwNotFound(
                    $modelName,
                    "Некорректный формат ID '{$stringValue}': ожидается положительное целое число без ведущих нулей.",
                );
            }

            if (filter_var(value: $stringValue, filter: FILTER_VALIDATE_INT, options: [
                'options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX],
            ]) === false) {
                $this->throwNotFound(
                    $modelName,
                    "Значение ID '{$stringValue}' выходит за пределы допустимого диапазона BigInt (1.." . PHP_INT_MAX . ').',
                );
            }
        }

        return parent::resolveRouteBinding($value, $field);
    }

    private function throwNotFound(string $modelName, string $reason): never
    {
        throw new ModelNotFoundException(
            message: "Запись модели '{$modelName}' не найдена. Причина: {$reason}",
        );
    }
}
