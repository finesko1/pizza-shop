<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('role')
                ->default('guest')
                ->after('email');
        });

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_role_check
            CHECK (role IN ('admin', 'user', 'guest'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        Schema::table('users', static function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
