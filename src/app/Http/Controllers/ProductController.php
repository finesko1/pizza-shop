<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductPaginationRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * GET /api/products
     *
     */
    public function index(ProductPaginationRequest $request): JsonResponse
    {
        $pagination = $request->validated();

        $products = Product::query()
            ->paginate(
                perPage: $pagination['limit'],
                page: $pagination['page'],
            );

        return ProductResource::collection($products)->response();
    }

    /**
     * POST /api/products
     *
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/products/{product}
     *
     */
    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product))->response();
    }

    /**
     * PUT/PATCH /api/products/{product}
     *
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return (new ProductResource($product))->response();
    }

    /**
     * DELETE /api/products/{product}
     *
     */
    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
