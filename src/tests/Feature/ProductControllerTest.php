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

final class ProductControllerTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    // GET /api/products (index)
    public function testIndexReturnsPaginatedProductsList(): void
    {
        Product::factory()->count(8)->create();

        $response = $this->getJson('/api/products?limit=2&page=1');

        $response
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data')
            ->assertJsonPath(path: 'meta.total', expect: 8)
            ->assertJsonPath(path: 'meta.per_page', expect: 2)
            ->assertJsonPath(path: 'meta.current_page', expect: 1)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'price', 'weight', 'category'],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    // GET /api/products (index)
    public function testIndexFailsWithInvalidPaginationParameters(): void
    {
        $this->getJson('/api/products?limit=-10&page=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(errors: ['limit', 'page']);
    }

    // POST /api/products (store)
    public function testStoreCreatesNewProductWithValidData(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $payload = ProductPayload::pepperoni();

        $response = $this->withToken($token)
            ->postJson('/api/products', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath(path: 'data.name', expect: $payload['name'])
            ->assertJsonPath(path: 'data.description', expect: $payload['description'])
            ->assertJsonPath(path: 'data.price', expect: $payload['price'])
            ->assertJsonPath(path: 'data.weight', expect: $payload['weight'])
            ->assertJsonPath(path: 'data.category', expect: $payload['category']);

        $this->assertDatabaseHas('products', $payload);
    }

    // POST /api/products (store)
    public function testStoreFailsValidationWhenPayloadIsInvalid(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)
            ->postJson('/api/products', ProductPayload::invalid())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(errors: ['name', 'price', 'weight', 'category']);

        $this->assertDatabaseCount('products', 0);
    }

    // GET /api/products/{product} (show)
    public function testShowReturnsSingleProduct(): void
    {
        $product = Product::factory()->pepperoni()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response
            ->assertOk()
            ->assertJsonPath(path: 'data.id', expect: $product->id)
            ->assertJsonPath(path: 'data.name', expect: $product->name)
            ->assertJsonPath(path: 'data.description', expect: $product->description)
            ->assertJsonPath(path: 'data.price', expect: $product->price)
            ->assertJsonPath(path: 'data.weight', expect: $product->weight)
            ->assertJsonPath(path: 'data.category', expect: $product->category->value);
    }

    // GET /api/products/{product} (show)
    public function testShowReturns404ForNonExistentOrInvalidBigintId(): void
    {
        $this->getJson('/api/products/999999')
            ->assertNotFound();

        $this->getJson('/api/products/9223372036854775808')
            ->assertNotFound();

        $product = Product::factory()->create();
        $this->getJson("/api/products/000{$product->id}")
            ->assertNotFound();

        $this->getJson('/api/products/abc')
            ->assertNotFound();
    }

    // PUT/PATCH /api/products/{product} (update)
    public function testUpdateModifiesExistingProduct(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $product = Product::factory()->pepperoni()->create();
        $payload = ProductPayload::margaritaUpdate();

        $response = $this->withToken($token)
            ->patchJson("/api/products/{$product->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonPath(path: 'data.id', expect: $product->id)
            ->assertJsonPath(path: 'data.name', expect: $payload['name'])
            ->assertJsonPath(path: 'data.price', expect: $payload['price'])
            ->assertJsonPath(path: 'data.weight', expect: $payload['weight']);

        $this->assertDatabaseHas('products', ['id' => $product->id] + $payload);
    }

    // PUT/PATCH /api/products/{product} (update)
    public function testUpdateFailsOnNonExistentIdOrInvalidData(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)
            ->patchJson('/api/products/999999', ProductPayload::pepperoni())
            ->assertNotFound();

        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token)
            ->patchJson("/api/products/{$product->id}", ['price' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(errors: ['price']);
    }

    // POST/PATCH/DELETE /api/products/{product} (update)
    public function test_guest_without_token_cant_edit_product(): void
    {
        $product = Product::factory()->pepperoni()->create();

        $this->postJson("/api/products", ProductPayload::pepperoni())
            ->assertUnauthorized();

        $this->patchJson("/api/products/{$product->id}", ProductPayload::margaritaUpdate())
            ->assertUnauthorized();

        $this->delete("/api/products/{$product->id}")
            ->assertUnauthorized();
    }

    // DELETE /api/products/{product} (destroy)
    public function testDestroyDeletesExistingProduct(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token)
            ->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    // DELETE /api/products/{product} (destroy)
    public function testDestroyReturns404WhenProductDoesNotExist(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)
            ->deleteJson('/api/products/999999')
            ->assertNotFound();

        $this->withToken($token)
            ->deleteJson('/api/products/abc')
            ->assertNotFound();
    }

    public function testUnauthorizedUserCantReadProductsCatalog(): void
    {
        $product = Product::factory()->pepperoni()->create();

        $this->getJson('/api/products')
            ->assertOk();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk();
    }

    public function test_guest_without_token_cant_edit_products_catalog(): void
    {
        $product = Product::factory()->pepperoni()->create();

        $this->postJson('/api/products', ProductPayload::pepperoni())
            ->assertUnauthorized();

        $this->patchJson("/api/products/{$product->id}", ProductPayload::margaritaUpdate())
            ->assertUnauthorized();

        $this->deleteJson("/api/products/{$product->id}")
            ->assertUnauthorized();

        $this->assertDatabaseCount('products', 1);
    }

    #[DataProvider('provideAuthorizedUserCanReadProductsCatalogCases')]
    public function testAuthorizedUserCanReadProductsCatalog(UserRoleEnum $role): void
    {
        $token = $this->tokenFor(User::factory()->create(attributes: ['role' => $role]));
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token)
            ->getJson('/api/products')
            ->assertOk();

        $this->withToken($token)
            ->getJson("/api/products/{$product->id}")
            ->assertOk();
    }

    /**
     * @return iterable<string, array{UserRoleEnum}>
     */
    public static function provideAuthorizedUserCanReadProductsCatalogCases(): iterable
    {
        return [
            'guest' => [UserRoleEnum::Guest],
            'user' => [UserRoleEnum::User],
            'admin' => [UserRoleEnum::Admin],
        ];
    }

    #[DataProvider('provideUserAndGuestCantEditProductsCatalogCases')]
    public function testUserAndGuestCantEditProductsCatalog(UserRoleEnum $role): void
    {
        $token = $this->tokenFor(User::factory()->create(attributes: ['role' => $role]));
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token) // store
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertForbidden();

        $this->withToken($token) // update
            ->patchJson("/api/products/{$product->id}", ProductPayload::margaritaUpdate())
            ->assertForbidden();

        $this->withToken($token) // destroy
            ->deleteJson("/api/products/{$product->id}")
            ->assertForbidden();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name]);
    }

    /**
     * @return iterable<string, array{UserRoleEnum}>
     */
    public static function provideUserAndGuestCantEditProductsCatalogCases(): iterable
    {
        return [
            'guest' => [UserRoleEnum::Guest],
            'user' => [UserRoleEnum::User],
        ];
    }

    public function testAdminCanEditProductsCatalog(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());
        $product = Product::factory()->pepperoni()->create();

        $this->withToken($token) // store
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertCreated();

        $this->withToken($token) // update
            ->patchJson("/api/products/{$product->id}", ProductPayload::margaritaUpdate())
            ->assertOk();

        $this->withToken($token) // destroy
            ->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('products', 1);
    }

    public function testInvalidTokenIsRejectedOnStore(): void
    {
        $this->withToken('not-a-jwt')
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertUnauthorized()
            ->assertJsonPath(path: 'message', expect: 'Unauthenticated.');

        $this->assertDatabaseCount('products', 0);
    }

    public function testTamperedTokenIsRejectedOnUpdate(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        [$header, $payload, $signature] = explode(separator: '.', string: $token);
        $payload[0] = $payload[0] === 'e' ? 'f' : 'e';
        $tampered = "{$header}.{$payload}.{$signature}";

        $product = Product::factory()->pepperoni()->create();
        $updatePayload = ProductPayload::margaritaUpdate();

        $this->withToken($tampered)
            ->patchJson("/api/products/{$product->id}", $updatePayload)
            ->assertUnauthorized();

        $this->assertDatabaseMissing('products', ['name' => $updatePayload['name']]);
    }

    public function testExpiredTokenIsRejectedOnStore(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->travel(61)->minutes(); // JWT_TTL = 60 минут, leeway = 0

        $this->withToken($token)
            ->postJson('/api/products', ProductPayload::pepperoni())
            ->assertUnauthorized();

        $this->assertDatabaseCount('products', 0);

        $this->travelBack();
    }
}
