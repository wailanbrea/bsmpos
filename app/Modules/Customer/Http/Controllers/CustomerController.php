<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Customer\Actions\CreateCustomerAction;
use App\Modules\Customer\Actions\UpdateCustomerAction;
use App\Modules\Customer\Http\Requests\RecordCreditRequest;
use App\Modules\Customer\Http\Requests\StoreCustomerRequest;
use App\Modules\Customer\Http\Requests\UpdateCustomerRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Services\CustomerCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerController
{
    use AuthorizesApiRequest;

    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'customers.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver clientes.', 403);
        }

        $query = Customer::query()->where('company_id', $currentCompany->company()->getKey());

        if (is_string($search = $request->query('search')) && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('is_generic')->orderBy('name')->paginate(20);

        return ApiResponse::success(
            CustomerResource::collection($customers),
            null,
            200,
            ['pagination' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
            ]],
        );
    }

    public function store(StoreCustomerRequest $request, CreateCustomerAction $action, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'customers.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar clientes.', 403);
        }

        $customer = $action->execute($currentCompany->company(), $request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'Cliente creado.', 201);
    }

    public function show(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $customer = $this->findCustomer($publicId, $currentCompany);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'view', $customer);

        return ApiResponse::success(new CustomerResource($customer));
    }

    public function update(string $publicId, UpdateCustomerRequest $request, UpdateCustomerAction $action, CurrentCompany $currentCompany): JsonResponse
    {
        $customer = $this->findCustomer($publicId, $currentCompany);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'update', $customer);
        $customer = $action->execute($customer, $request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'Cliente actualizado.');
    }

    public function recordCredit(string $publicId, RecordCreditRequest $request, CustomerCreditService $service, CurrentCompany $currentCompany): JsonResponse
    {
        $customer = $this->findCustomer($publicId, $currentCompany);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'manageCredit', $customer);

        $service->record($customer, $request->validated('type'), (string) $request->validated('amount'), $user, $request->validated('notes'));

        return ApiResponse::success(new CustomerResource($customer->fresh()), 'Movimiento registrado.');
    }

    private function findCustomer(string $publicId, CurrentCompany $currentCompany): Customer
    {
        $customer = Customer::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($customer === null) {
            throw new ApiException(ErrorCode::NotFound, 'El cliente no existe.', 404);
        }

        return $customer;
    }
}
