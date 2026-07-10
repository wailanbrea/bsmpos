<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Setting\Http\Requests\StoreExchangeRateRequest;
use App\Modules\Setting\Models\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExchangeRateController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $rates = ExchangeRate::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderByDesc('effective_date')
            ->limit(50)
            ->get(['currency_code', 'rate', 'effective_date']);

        $data = $rates->map(fn (ExchangeRate $rate): array => [
            'currency_code' => $rate->currency_code,
            'rate' => $rate->rate,
            'effective_date' => $rate->effective_date->toDateString(),
        ])->all();

        return ApiResponse::success($data);
    }

    public function store(StoreExchangeRateRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $rate = ExchangeRate::query()->updateOrCreate(
            [
                'company_id' => $currentCompany->company()->getKey(),
                'currency_code' => $data['currency_code'],
                'effective_date' => $data['effective_date'],
            ],
            ['rate' => $data['rate'], 'created_by_user_id' => $user->getKey()],
        );

        return ApiResponse::success(
            [
                'currency_code' => $rate->currency_code,
                'rate' => $rate->rate,
                'effective_date' => $rate->effective_date->toDateString(),
            ],
            'Tasa registrada.',
            201,
        );
    }
}
