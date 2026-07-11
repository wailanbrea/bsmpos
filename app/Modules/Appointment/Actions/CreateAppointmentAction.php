<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\Models\Appointment;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Employee\Models\Employee;
use App\Modules\Service\Models\Service;
use App\Modules\Setting\Models\Tax;
use Illuminate\Support\Facades\DB;

final class CreateAppointmentAction
{
    /**
     * @param  array{customer_id?: string|null, employee_id?: string|null, scheduled_at: string, duration_minutes?: int|null, notes?: string|null, reminder_at?: string|null, services: list<array{service_id: string}>}  $data
     */
    public function execute(Company $company, int $branchId, array $data): Appointment
    {
        return DB::transaction(function () use ($company, $branchId, $data): Appointment {
            $companyId = $company->getKey();

            $customerId = isset($data['customer_id'])
                ? Customer::query()->where('company_id', $companyId)->where('public_id', $data['customer_id'])->value('id')
                : null;

            $employeeId = isset($data['employee_id'])
                ? Employee::query()->where('company_id', $companyId)->where('public_id', $data['employee_id'])->value('id')
                : null;

            $lines = [];
            $total = '0';
            $durationFromServices = 0;

            foreach ($data['services'] as $line) {
                $service = Service::query()
                    ->where('company_id', $companyId)
                    ->where('public_id', $line['service_id'])
                    ->firstOrFail();

                $rate = $service->tax_id !== null
                    ? (string) (Tax::query()->whereKey($service->tax_id)->value('rate') ?? '0')
                    : '0';

                // Total con impuesto, en DECIMAL vía bcmath para no perder centavos.
                $price = (string) $service->price;
                $withTax = bcadd($price, bcdiv(bcmul($price, $rate, 4), '100', 4), 2);
                $total = bcadd($total, $withTax, 2);
                $durationFromServices += (int) $service->duration_minutes;

                $lines[] = [
                    'service_id' => $service->getKey(),
                    'name' => $service->name,
                    'price' => $price,
                    'tax_id' => $service->tax_id,
                    'tax_rate' => $rate,
                    'duration_minutes' => (int) $service->duration_minutes,
                ];
            }

            $appointment = Appointment::query()->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'employee_id' => $employeeId,
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'] ?? ($durationFromServices > 0 ? $durationFromServices : 30),
                'status' => 'pendiente',
                'total' => $total,
                'reminder_at' => $data['reminder_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $appointment->services()->createMany($lines);
            $appointment->audit('appointment.created', [], $appointment->only(['scheduled_at', 'total', 'status']));

            return $appointment->load('services');
        });
    }
}
