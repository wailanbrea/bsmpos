<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Product\Actions\SaveProductAction;
use App\Modules\Product\Http\Requests\StoreProductRequest;
use App\Modules\Product\Http\Requests\UpdateProductRequest;
use App\Modules\Product\Http\Resources\ProductResource;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController
{
    use AuthorizesApiRequest;

    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'products.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver productos.', 403);
        }

        $query = Product::query()
            ->with('category')
            ->where('company_id', $currentCompany->company()->getKey());

        if (is_string($search = $request->query('search')) && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(20);

        return ApiResponse::success(
            ProductResource::collection($products),
            null,
            200,
            ['pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ]],
        );
    }

    public function store(StoreProductRequest $request, SaveProductAction $action, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'products.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar productos.', 403);
        }

        $product = $action->execute($currentCompany->company(), $request->validated());

        return ApiResponse::success(new ProductResource($product->load(['category', 'variants', 'modifiers.options', 'combos.child'])), 'Producto creado.', 201);
    }

    public function update(string $publicId, UpdateProductRequest $request, SaveProductAction $action, CurrentCompany $currentCompany): JsonResponse
    {
        $product = $this->findProduct($publicId, $currentCompany);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'update', $product);
        $product = $action->execute($currentCompany->company(), $request->validated(), $product);

        return ApiResponse::success(new ProductResource($product->load(['category', 'variants', 'modifiers.options', 'combos.child'])), 'Producto actualizado.');
    }

    private function findProduct(string $publicId, CurrentCompany $currentCompany): Product
    {
        $product = Product::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($product === null) {
            throw new ApiException(ErrorCode::NotFound, 'El producto no existe.', 404);
        }

        return $product;
    }
}
