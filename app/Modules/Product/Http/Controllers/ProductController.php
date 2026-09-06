<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Support\SquareImage;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Product\Actions\SaveProductAction;
use App\Modules\Product\Http\Requests\StoreProductRequest;
use App\Modules\Product\Http\Requests\UpdateProductRequest;
use App\Modules\Product\Http\Resources\ProductResource;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            ->with(['category', 'tax', 'inventorySetting'])
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

        return ApiResponse::success(new ProductResource($product->load(['category', 'tax', 'variants', 'modifiers.options', 'combos.child'])), 'Producto creado.', 201);
    }

    public function update(string $publicId, UpdateProductRequest $request, SaveProductAction $action, CurrentCompany $currentCompany): JsonResponse
    {
        $product = $this->findProduct($publicId, $currentCompany);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'update', $product);
        $product = $action->execute($currentCompany->company(), $request->validated(), $product);

        return ApiResponse::success(new ProductResource($product->load(['category', 'tax', 'variants', 'modifiers.options', 'combos.child'])), 'Producto actualizado.');
    }

    /**
     * Sube (o reemplaza) la imagen de un producto. Multipart, campo `image`.
     * La imagen se guarda en el disco público bajo `products/` y se elimina la
     * anterior. Requiere `products.manage`.
     */
    public function uploadImage(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $product = $this->findProduct($publicId, $currentCompany);
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'products.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar productos.', 403);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $request->file('image');

        // Normalizar a un cuadrado estándar de catálogo (recorte centrado 600px).
        $square = SquareImage::fit((string) $file->getRealPath(), (string) $file->getMimeType());
        $path = 'products/'.$product->public_id.'-'.substr(md5($square['binary']), 0, 8).'.'.$square['extension'];

        $old = $product->image_path;
        Storage::disk('public')->put($path, $square['binary']);
        $product->update(['image_path' => $path]);

        if (is_string($old) && $old !== '') {
            Storage::disk('public')->delete($old);
        }

        $product->audit('product.image_updated', [], ['image_path' => $path]);

        return ApiResponse::success(new ProductResource($product->load(['category', 'tax'])), 'Imagen actualizada.');
    }

    /** Elimina la imagen de un producto. Requiere `products.manage`. */
    public function deleteImage(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $product = $this->findProduct($publicId, $currentCompany);
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'products.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar productos.', 403);
        }

        $old = $product->image_path;
        if (is_string($old) && $old !== '') {
            Storage::disk('public')->delete($old);
        }
        $product->update(['image_path' => null]);
        $product->audit('product.image_removed', [], []);

        return ApiResponse::success(new ProductResource($product->load(['category', 'tax'])), 'Imagen eliminada.');
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
