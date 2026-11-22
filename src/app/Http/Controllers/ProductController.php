<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProductPaginationRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class ProductController extends Controller
{
    /**
     * GET /api/products.
     */
    public function index(ProductPaginationRequest $request): JsonResponse
    {
        $pagination = $request->validated();

        $products = Product::query()
            ->paginate(
                perPage: $pagination['limit'] ?? 5,
                page: $pagination['page'] ?? 1,
            )
            ->appends(key: ['limit' => $pagination['limit'] ?? 5]);

        return ProductResource::collection(resource: $products)->response();
    }

    /**
     * POST /api/products.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return new ProductResource(resource: $product)
            ->response()
            ->setStatusCode(code: Response::HTTP_CREATED);
    }

    /**
     * GET /api/products/{product}.
     */
    public function show(Product $product): JsonResponse
    {
        return new ProductResource(resource: $product)->response();
    }

    /**
     * PUT/PATCH /api/products/{product}.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update(attributes: $request->validated());

        return new ProductResource(resource: $product)->response();
    }

    /**
     * DELETE /api/products/{product}.
     */
    public function destroy(Product $product): Response
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }
}
