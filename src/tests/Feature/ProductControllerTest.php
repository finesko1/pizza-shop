<?php

namespace Tests\Feature;

use App\Enums\UserRoleEnum;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithApi;
use Tests\Fixtures\ProductPayload;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    // GET /api/products (index)
    public function test_index_returns_paginated_products_list(): void
    {
        $token = $this->tokenFor(User::factory()->user()->create());

        Product::factory()->count(8)->create();

        $response = $this->withToken($token)
            ->getJson('/api/products?limit=2&page=1');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 8)
            ->assertJsonPath('meta.per_page', 2)
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
        $token = $this->tokenFor(User::factory()->user()->create());

        $this->withToken($token)
            ->getJson('/api/products?limit=-10&page=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit', 'page']);
    }

    // POST /api/products (store)
    public function test_store_creates_new_product_with_valid_data(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $payload = ProductPayload::pepperoni();

        $response = $this->withToken($token)
            ->postJson('/api/products', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.description', $payload['description'])
            ->assertJsonPath('data.price', $payload['price'])
            ->assertJsonPath('data.weight', $payload['weight'])
            ->assertJsonPath('data.category', $payload['category']);

        $this->assertDatabaseHas('products', $payload);
    }

    // POST /api/products (store)
    public function test_store_fails_validation_when_payload_is_invalid(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)
            ->postJson('/api/products', ProductPayload::invalid())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'weight', 'category']);

        $this->assertDatabaseCount('products', 0);
    }

    // GET /api/products/{product} (show)
    public function test_show_returns_single_product(): void
    {
        $token = $this->tokenFor(User::factory()->user()->create());
        $product = Product::factory()->pepperoni()->create();

        $response = $this->withToken($token)
            ->getJson("/api/products/{$product->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonPath('data.description', $product->description)
            ->assertJsonPath('data.price', $product->price)
            ->assertJsonPath('data.weight', $product->weight)
            ->assertJsonPath('data.category', $product->category->value);
    }

    // GET /api/products/{product} (show)
    public function test_show_returns_404_for_non_existent_or_invalid_bigint_id(): void
    {
        $token = $this->tokenFor(User::factory()->user()->create());

        $this->withToken($token)
            ->getJson('/api/products/999999')
            ->assertNotFound();

        $this->withToken($token)
            ->getJson('/api/products/9223372036854775808')
            ->assertNotFound();

        $product = Product::factory()->create();
        $this->withToken($token)
            ->getJson("/api/products/000{$product->id}")
            ->assertNotFound();

        $this->withToken($token)
            ->getJson('/api/products/abc')
            ->assertNotFound();
    }

    // PUT/PATCH /api/products/{product} (update)
    public function test_update_modifies_existing_product(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $product = Product::factory()->pepperoni()->create();
        $payload = ProductPayload::margaritaUpdate();

        $response = $this->withToken($token)
            ->patchJson("/api/products/{$product->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.price', $payload['price'])
            ->assertJsonPath('data.weight', $payload['weight']);

        $this->assertDatabaseHas('products', ['id' => $product->id] + $payload);
    }

    // PUT/PATCH /api/products/{product} (update)
    public function test_update_fails_on_non_existent_id_or_invalid_data(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)
            ->patchJson('/api/products/999999', ProductPayload::pepperoni())
            ->assertNotFound();

        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token)
            ->patchJson("/api/products/{$product->id}", ['price' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    // DELETE /api/products/{product} (destroy)
    public function test_destroy_deletes_existing_product(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token)
            ->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    // DELETE /api/products/{product} (destroy)
    public function test_destroy_returns_404_when_product_does_not_exist(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)
            ->deleteJson('/api/products/999999')
            ->assertNotFound();

        $this->withToken($token)
            ->deleteJson('/api/products/abc')
            ->assertNotFound();
    }

    public function test_unauthorized_user_cant_read_products_catalog(): void
    {
        $product = Product::factory()->pepperoni()->create();

        $this->getJson('/api/products')
            ->assertUnauthorized();

        $this->getJson("/api/products/{$product->id}")
            ->assertUnauthorized();
    }

    #[DataProvider('readableRoles')]
    public function test_authorized_user_can_read_products_catalog(UserRoleEnum $role): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => $role]));
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token)
            ->getJson('/api/products')
            ->assertOk();

        $this->withToken($token)
            ->getJson("/api/products/{$product->id}")
            ->assertOk();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_user_and_guest_cant_edit_products_catalog(UserRoleEnum $role): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => $role]));
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token) // store
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertForbidden();

        $this->withToken($token) // update
            ->putJson("/api/products/{$product->id}", ProductPayload::margaritaUpdate())
            ->assertForbidden();

        $this->withToken($token) // destroy
            ->deleteJson("/api/products/{$product->id}")
            ->assertForbidden();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name]);
    }

    public function test_admin_can_edit_products_catalog(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token) // store
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertCreated();

        $this->withToken($token) // update
            ->putJson("/api/products/{$product->id}", ProductPayload::margaritaUpdate())
            ->assertOk();

        $this->withToken($token) // destroy
            ->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('products', 1);
    }

    public function test_invalid_token_is_rejected_on_store(): void
    {
        $this->withToken('not-a-jwt')
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_tampered_token_is_rejected_on_update(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        [$header, $payload, $signature] = explode('.', $token);
        $payload[0] = $payload[0] === 'e' ? 'f' : 'e';
        $tampered = "{$header}.{$payload}.{$signature}";

        $product = Product::factory()->pepperoni()->create();
        $updatePayload = ProductPayload::margaritaUpdate();

        $this->withToken($tampered)
            ->patchJson("/api/products/{$product->id}", $updatePayload)
            ->assertUnauthorized();

        $this->assertDatabaseMissing('products', ['name' => $updatePayload['name']]);
    }

    public function test_expired_token_is_rejected_on_store(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->travel(61)->minutes(); // JWT_TTL = 60 минут, leeway = 0

        $this->withToken($token)
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertUnauthorized();

        $this->assertDatabaseCount('products', 0);

        $this->travelBack();
    }

    public static function readableRoles(): array
    {
        return [
            'guest' => [UserRoleEnum::Guest],
            'user' => [UserRoleEnum::User],
            'admin' => [UserRoleEnum::Admin],
        ];
    }

    public static function nonAdminRoles(): array
    {
        return [
            'guest' => [UserRoleEnum::Guest],
            'user' => [UserRoleEnum::User],
        ];
    }
}
