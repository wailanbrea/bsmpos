<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoiceSetting;
use App\Modules\ElectronicInvoice\Services\ElectronicInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class ElectronicInvoiceController
{
    public function __construct(private readonly ElectronicInvoiceService $service) {}

    public function index(CurrentCompany $currentCompany): JsonResponse
    {
        $records = ElectronicInvoice::query()
            ->with('invoice.customer')
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return ApiResponse::success($records->map(fn (ElectronicInvoice $r): array => $this->mapRecord($r)));
    }

    public function settings(CurrentCompany $currentCompany): JsonResponse
    {
        $settings = ElectronicInvoiceSetting::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->first();

        return ApiResponse::success([
            'provider_code' => $settings->provider_code ?? 'mock',
            'environment' => $settings->environment ?? 'test',
            'is_active' => $settings->is_active ?? false,
        ]);
    }

    public function updateSettings(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $data = $request->validate([
            'provider_code' => ['required', Rule::in(['null', 'mock'])],
            'environment' => ['required', Rule::in(['test', 'cert', 'prod'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $settings = ElectronicInvoiceSetting::query()->updateOrCreate(
            ['company_id' => $currentCompany->company()->getKey()],
            $data,
        );

        $currentCompany->company()->audit('einvoice.settings.updated', [], $data);

        return ApiResponse::success([
            'provider_code' => $settings->provider_code,
            'environment' => $settings->environment,
            'is_active' => $settings->is_active,
        ], 'Configuración de facturación electrónica guardada.');
    }

    public function retry(string $publicId, CurrentCompany $currentCompany): JsonResponse
    {
        $record = ElectronicInvoice::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($record === null) {
            throw new ApiException(ErrorCode::NotFound, 'El comprobante electrónico no existe.', 404);
        }

        if (in_array($record->status, ['accepted', 'canceled'], true)) {
            throw new ApiException(ErrorCode::Conflict, 'El comprobante ya fue procesado.', 409);
        }

        $record = $this->service->retry($record);
        $record->audit('einvoice.retried', [], ['status' => $record->status]);

        return ApiResponse::success($this->mapRecord($record->load('invoice.customer')), 'Reintento ejecutado.');
    }

    public function logs(string $publicId, CurrentCompany $currentCompany): JsonResponse
    {
        $record = ElectronicInvoice::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($record === null) {
            throw new ApiException(ErrorCode::NotFound, 'El comprobante electrónico no existe.', 404);
        }

        return ApiResponse::success($record->logs()->orderByDesc('id')->limit(50)->get()->map(fn ($log): array => [
            'action' => $log->action,
            'status_code' => $log->status_code,
            'error_message' => $log->error_message,
            'response' => $log->response_payload,
            'created_at' => $log->created_at?->toIso8601String(),
        ]));
    }

    /** @return array<string, mixed> */
    private function mapRecord(ElectronicInvoice $r): array
    {
        return [
            'id' => $r->public_id,
            'invoice_number' => $r->invoice?->invoice_number,
            'ncf' => $r->invoice?->ncf,
            'customer_name' => $r->invoice?->customer?->name,
            'total' => $r->invoice?->total,
            'provider_code' => $r->provider_code,
            'environment' => $r->environment,
            'status' => $r->status,
            'track_id' => $r->track_id,
            'qr_data' => $r->qr_data,
            'last_error' => $r->last_error,
            'accepted_at' => $r->accepted_at ? Carbon::parse($r->accepted_at)->toIso8601String() : null,
            'created_at' => $r->created_at ? Carbon::parse($r->created_at)->toIso8601String() : null,
        ];
    }
}
