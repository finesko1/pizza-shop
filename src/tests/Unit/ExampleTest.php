<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\UserRoleEnum;
use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testAdminRoleHasExpectedValue(): void
    {
        self::assertSame('admin', UserRoleEnum::Admin->value);
    }
}
