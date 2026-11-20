<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    // GET /api/products (index)
    public function test_index_returns_paginated_products_list(): void
    {
        Product::factory()->count(8)->create();

        $response = $this->getJson('/api/products?limit=5&page=1');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 8)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'price', 'weight', 'category'],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    // GET /api/products (index)
    public function test_index_fails_with_invalid_pagination_parameters(): void
    {
        $response = $this->getJson('/api/products?limit=-10&page=abc');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit', 'page']);
    }

    // POST /api/products (store)
    public function test_store_creates_new_product_with_valid_data(): void
    {
        $payload = [
            'name' => 'Пицца Пепперони',
            'description' => 'Острая пепперони, моцарелла, томатный соус',
            'price' => 690_00,
            'weight' => 550,
            'category' => 'Пицца',
        ];

        $response = $this->postJson('/api/products', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Пицца Пепперони')
            ->assertJsonPath('data.price', 690_00);

        $this->assertDatabaseHas('products', [
            'name' => 'Пицца Пепперони',
            'description' => 'Острая пепперони, моцарелла, томатный соус',
            'price' => 690_00,
            'weight' => 550,
            'category' => 'Пицца',
        ]);
    }

    // POST /api/products (store)
    public function test_store_fails_validation_when_payload_is_invalid(): void
    {
        $payload = [
            'name' => '',
            'price' => -500,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'weight', 'category']);

        $this->assertDatabaseCount('products', 0);
    }

    // GET /api/products/{product} (show)
    public function test_show_returns_single_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Пицца Пепперони',
            'price' => 500_00,
            'weight' => 1_000,
            'category' => 'Пицца'
        ]);

        $response = $this->getJson("api/products/{$product->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Пицца Пепперони')
            ->assertJsonPath('data.price', 500_00)
            ->assertJsonPath('data.weight', 1_000)
            ->assertJsonPath('data.category', 'Пицца');
    }

    // GET /api/products/{product} (show)
    public function test_show_returns_404_for_non_existent_or_invalid_bigint_id(): void
    {
        $this->getJson('/api/products/999999')
            ->assertNotFound();

        $this->getJson('api/products/99999999999999999')
            ->assertNotFound();

        $this->getJson('api/products/0123')
            ->assertNotFound();

        $this->getJson('api/products/abc')
            ->assertNotFound();
    }

    // PUT /api/products/{product} (update)
    public function test_update_modifies_existing_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Пицца Пепперони',
            'price' => 500_00,
            'weight' => 1_000,
            'category' => 'Пицца',
        ]);

        $payload = [
            'name' => 'Пицца Маргарита',
            'price' => 900_00,
            'weight' => 900
        ];

        $response = $this->patchJson("api/products/{$product->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Пицца Маргарита')
            ->assertJsonPath('data.weight', 900)
            ->assertJsonPath('data.price', 900_00);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Пицца Маргарита',
            'weight' => 900,
            'price' => 900_00,
        ]);
    }

    // PUT /api/products/{product} (update)
    public function test_update_fails_on_non_existent_id_or_invalid_data(): void
    {
        $this->patchJson('/api/products/999999', ['name' => 'Пицца'])
            ->assertNotFound();

        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}", ['price' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    // DELETE /api/products/{product} (destroy)
    public function test_destroy_deletes_existing_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    // DELETE /api/products/{product} (destroy)
    public function test_destroy_returns_404_when_product_does_not_exist(): void
    {
        $this->deleteJson('/api/products/999999')
            ->assertNotFound();

        $this->deleteJson('/api/products/abc')
            ->assertNotFound();
    }
}
