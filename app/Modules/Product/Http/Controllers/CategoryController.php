<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Product\Http\Resources\CategoryResource;
use App\Modules\Product\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CategoryController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $query = Category::query()->where('company_id', $currentCompany->company()->getKey());

        if (is_string($kind = $request->query('kind')) && $kind !== '') {
            $query->where('kind', $kind);
        }

        return ApiResponse::success(
            CategoryResource::collection($query->orderBy('sort_order')->orderBy('name')->get()),
        );
    }

    public function store(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(['product', 'service'])],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('company_id', $currentCompany->company()->getKey())],
        ]);

        $category = Category::query()->create(['company_id' => $currentCompany->company()->getKey(), ...$data]);
        $category->audit('category.created', [], $category->only(['name', 'kind']));

        return ApiResponse::success(new CategoryResource($category), 'Categoría creada.', 201);
    }
}
