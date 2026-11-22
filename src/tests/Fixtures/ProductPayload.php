<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Enums\ProductCategoryEnum;

final class ProductPayload
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function pepperoni(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Пицца Пепперони',
            'description' => 'Острая пепперони, моцарелла, томатный соус',
            'price' => 69_000,
            'weight' => 550,
            'category' => ProductCategoryEnum::Pizza->value,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function margaritaUpdate(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Пицца Маргарита',
            'price' => 90_000,
            'weight' => 900,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    public static function invalid(): array
    {
        return [
            'name' => '',
            'price' => -500,
        ];
    }
}
