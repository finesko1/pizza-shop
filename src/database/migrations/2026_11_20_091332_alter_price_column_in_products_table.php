<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Изменение типа колонки на integer.
     */
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table): void {
            $table->integer('price')->change();
        });
    }

    /**
     * Изменение типа колонки на biginteger.
     */
    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table): void {
            $table->bigInteger('price')->change();
        });
    }
};
