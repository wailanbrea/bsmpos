<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Setting\Http\Requests\StoreNcfSequenceRequest;
use App\Modules\Setting\Http\Resources\NcfSequenceResource;
use App\Modules\Setting\Models\NcfSequence;
use Illuminate\Http\JsonResponse;

final class NcfSequenceController
{
    public function index(CurrentCompany $currentCompany): JsonResponse
    {
        $sequences = NcfSequence::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderBy('document_type_code')
            ->get();

        return ApiResponse::success(NcfSequenceResource::collection($sequences));
    }

    public function store(StoreNcfSequenceRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $data = $request->validated();

        $sequence = NcfSequence::query()->create([
            'company_id' => $currentCompany->company()->getKey(),
            'branch_id' => $currentCompany->hasBranch() ? $currentCompany->branch()->getKey() : null,
            'document_type_code' => $data['document_type_code'],
            'series' => $data['series'] ?? substr((string) $data['document_type_code'], 0, 1),
            'start_number' => $data['start_number'],
            'end_number' => $data['end_number'],
            'current_number' => $data['start_number'] - 1,
            'expires_at' => $data['expires_at'] ?? null,
            'alert_threshold' => $data['alert_threshold'] ?? 50,
            'is_active' => true,
        ]);
        $sequence->audit('ncf_sequence.created', [], $sequence->only(['document_type_code', 'start_number', 'end_number']));

        return ApiResponse::success(new NcfSequenceResource($sequence), 'Secuencia NCF creada.', 201);
    }
}
