<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Setting\Http\Requests\StorePaymentMethodRequest;
use App\Modules\Setting\Http\Requests\StoreTaxRequest;
use App\Modules\Setting\Http\Requests\UpdateTaxRequest;
use App\Modules\Setting\Http\Resources\PaymentMethodResource;
use App\Modules\Setting\Http\Resources\TaxResource;
use App\Modules\Setting\Models\DocumentType;
use App\Modules\Setting\Models\PaymentMethod;
use App\Modules\Setting\Models\Tax;
use Illuminate\Http\JsonResponse;

final class SettingController
{
    public function fiscal(CurrentCompany $currentCompany): JsonResponse
    {
        $companyId = $currentCompany->company()->getKey();

        return ApiResponse::success([
            'taxes' => TaxResource::collection(
                Tax::query()->where('company_id', $companyId)->orderBy('sort_order')->get(),
            ),
            'payment_methods' => PaymentMethodResource::collection(
                PaymentMethod::query()->where('company_id', $companyId)->orderBy('sort_order')->get(),
            ),
            'document_types' => DocumentType::query()->orderBy('sort_order')->get(['code', 'name', 'is_electronic', 'requires_customer_tax_id']),
            'currencies' => $currentCompany->company()->currency_code,
        ]);
    }

    public function storeTax(StoreTaxRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $tax = Tax::query()->create([
            'company_id' => $currentCompany->company()->getKey(),
            ...$request->validated(),
        ]);
        $tax->audit('tax.created', [], $tax->only(['code', 'rate']));

        return ApiResponse::success(new TaxResource($tax), 'Impuesto creado.', 201);
    }

    public function updateTax(string $publicId, UpdateTaxRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $tax = $this->findTax($publicId, $currentCompany);
        $before = $tax->only(['name', 'rate', 'is_active']);
        $tax->update($request->validated());
        $tax->audit('tax.updated', $before, $tax->only(['name', 'rate', 'is_active']));

        return ApiResponse::success(new TaxResource($tax), 'Impuesto actualizado.');
    }

    public function storePaymentMethod(StorePaymentMethodRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $method = PaymentMethod::query()->create([
            'company_id' => $currentCompany->company()->getKey(),
            ...$request->validated(),
        ]);
        $method->audit('payment_method.created', [], $method->only(['code']));

        return ApiResponse::success(new PaymentMethodResource($method), 'Método de pago creado.', 201);
    }

    private function findTax(string $publicId, CurrentCompany $currentCompany): Tax
    {
        $tax = Tax::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($tax === null) {
            throw new ApiException(ErrorCode::NotFound, 'El impuesto no existe.', 404);
        }

        return $tax;
    }
}
