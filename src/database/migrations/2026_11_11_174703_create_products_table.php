<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('price');
            $table->integer('weight');
            $table->string('category');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_check CHECK (price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_weight_check CHECK (weight >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_price_check');
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_weight_check');

        Schema::dropIfExists('products');
    }
};
