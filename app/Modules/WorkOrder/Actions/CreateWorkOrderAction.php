<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Actions;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Employee\Models\Employee;
use App\Modules\Product\Models\Product;
use App\Modules\Service\Models\Service;
use App\Modules\Setting\Models\Tax;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\WorkOrder\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

final class CreateWorkOrderAction
{
    /**
     * @param  array{vehicle_id: string, customer_id?: string|null, employee_id?: string|null, diagnosis?: string|null, labor_amount?: float|string|null, notes?: string|null, services?: list<array{service_id: string}>, parts?: list<array{product_id?: string|null, name: string, quantity: float|string, price: float|string}>}  $data
     */
    public function execute(Company $company, int $branchId, array $data): WorkOrder
    {
        return DB::transaction(function () use ($company, $branchId, $data): WorkOrder {
            $companyId = $company->getKey();

            $vehicleId = Vehicle::query()->where('company_id', $companyId)->where('public_id', $data['vehicle_id'])->value('id');
            $customerId = isset($data['customer_id'])
                ? Customer::query()->where('company_id', $companyId)->where('public_id', $data['customer_id'])->value('id')
                : null;
            $employeeId = isset($data['employee_id'])
                ? Employee::query()->where('company_id', $companyId)->where('public_id', $data['employee_id'])->value('id')
                : null;

            $labor = (string) ($data['labor_amount'] ?? '0');
            $total = $labor;

            $serviceLines = [];
            foreach ($data['services'] ?? [] as $line) {
                $service = Service::query()->where('company_id', $companyId)->where('public_id', $line['service_id'])->firstOrFail();
                $rate = $service->tax_id !== null
                    ? (string) (Tax::query()->whereKey($service->tax_id)->value('rate') ?? '0')
                    : '0';
                $price = (string) $service->price;
                $withTax = bcadd($price, bcdiv(bcmul($price, $rate, 4), '100', 4), 2);
                $total = bcadd($total, $withTax, 2);

                $serviceLines[] = [
                    'service_id' => $service->getKey(),
                    'name' => $service->name,
                    'price' => $price,
                    'tax_id' => $service->tax_id,
                    'tax_rate' => $rate,
                ];
            }

            $partLines = [];
            foreach ($data['parts'] ?? [] as $part) {
                $productId = isset($part['product_id'])
                    ? Product::query()->where('company_id', $companyId)->where('public_id', $part['product_id'])->value('id')
                    : null;
                $qty = (string) $part['quantity'];
                $price = (string) $part['price'];
                $total = bcadd($total, bcmul($qty, $price, 2), 2);

                $partLines[] = [
                    'product_id' => $productId,
                    'name' => $part['name'],
                    'quantity' => $qty,
                    'price' => $price,
                ];
            }

            $order = WorkOrder::query()->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'employee_id' => $employeeId,
                'diagnosis' => $data['diagnosis'] ?? null,
                'status' => 'recibida',
                'labor_amount' => $labor,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->services()->createMany($serviceLines);
            $order->parts()->createMany($partLines);
            $order->audit('work_order.created', [], $order->only(['status', 'total']));

            return $order->load(['services', 'parts']);
        });
    }
}
