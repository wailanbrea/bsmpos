<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Appointment\Actions\CreateAppointmentAction;
use App\Modules\Appointment\Http\Requests\StoreAppointmentRequest;
use App\Modules\Appointment\Http\Requests\UpdateAppointmentStatusRequest;
use App\Modules\Appointment\Http\Resources\AppointmentResource;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\Employee\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AppointmentController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'appointments.view');

        $query = Appointment::query()
            ->with(['customer', 'employee', 'services.service'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderBy('scheduled_at');

        if (is_string($date = $request->query('date')) && $date !== '') {
            $query->whereDate('scheduled_at', $date);
        }

        if (is_string($status = $request->query('status')) && $status !== '') {
            $query->where('status', $status);
        }

        if (is_string($employee = $request->query('employee_id')) && $employee !== '') {
            $employeeId = Employee::query()
                ->where('company_id', $currentCompany->company()->getKey())
                ->where('public_id', $employee)
                ->value('id');
            $query->where('employee_id', $employeeId);
        }

        return ApiResponse::success(AppointmentResource::collection($query->get()));
    }

    public function store(StoreAppointmentRequest $request, CurrentCompany $currentCompany, CreateAppointmentAction $action): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'appointments.manage');

        $appointment = $action->execute(
            $currentCompany->company(),
            $currentCompany->branch()->getKey(),
            $request->validated(),
        );

        return ApiResponse::success(
            new AppointmentResource($appointment->load(['customer', 'employee', 'services'])),
            'Cita agendada.',
            201,
        );
    }

    public function updateStatus(string $publicId, UpdateAppointmentStatusRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'appointments.manage');

        $appointment = $this->find($publicId, $currentCompany);
        $target = (string) $request->validated()['status'];

        if ($appointment->status !== $target && ! $appointment->canTransitionTo($target)) {
            throw new ApiException(
                ErrorCode::Conflict,
                "No se puede cambiar la cita de '{$appointment->status}' a '{$target}'.",
                409,
            );
        }

        $from = $appointment->status;
        $appointment->update(['status' => $target]);
        $appointment->audit('appointment.status_changed', ['status' => $from], ['status' => $target]);

        return ApiResponse::success(
            new AppointmentResource($appointment->load(['customer', 'employee', 'services'])),
            'Estado de la cita actualizado.',
        );
    }

    public function show(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'appointments.view');

        $appointment = $this->find($publicId, $currentCompany)->load(['customer', 'employee', 'services']);

        return ApiResponse::success(new AppointmentResource($appointment));
    }

    private function find(string $publicId, CurrentCompany $currentCompany): Appointment
    {
        return Appointment::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->firstOr(fn () => throw new ApiException(ErrorCode::NotFound, 'Cita no encontrada.', 404));
    }

    private function authorize(Request $request, CurrentCompany $currentCompany, string $permission): void
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), $permission)) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para esta acción.', 403);
        }
    }
}
