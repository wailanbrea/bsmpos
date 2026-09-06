<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use App\Modules\Notification\Models\SystemNotification;
use App\Modules\POS\Models\CashSession;
use App\Modules\Setting\Models\NcfSequence;
use App\Modules\WorkOrder\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class NotificationService
{
    /**
     * Lista notificaciones de la empresa para el usuario autenticado
     * (incluye las globales de la empresa sin user_id).
     *
     * @return Collection<int, SystemNotification>
     */
    public function list(Company $company, ?User $user = null, int $limit = 20): Collection
    {
        return SystemNotification::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id');
                if ($user !== null) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(Company $company, ?User $user = null): int
    {
        return SystemNotification::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id');
                if ($user !== null) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->unread()
            ->count();
    }

    public function markAsRead(Company $company, string $publicId): bool
    {
        $notification = SystemNotification::query()
            ->where('company_id', $company->id)
            ->where('public_id', $publicId)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(Company $company, ?User $user = null): int
    {
        return SystemNotification::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id');
                if ($user !== null) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function delete(Company $company, string $publicId): bool
    {
        $notification = SystemNotification::query()
            ->where('company_id', $company->id)
            ->where('public_id', $publicId)
            ->first();

        if (! $notification) {
            return false;
        }

        return (bool) $notification->delete();
    }

    /**
     * Crea una notificación manual para la empresa.
     */
    public function createNotification(
        Company $company,
        string $title,
        string $message,
        string $category = 'system',
        string $type = 'info',
        ?string $actionUrl = null,
        ?int $branchId = null,
        ?int $userId = null
    ): SystemNotification {
        return SystemNotification::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'type' => $type,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Evalúa y sincroniza las alertas operativas automáticas para la empresa.
     * Seguro ante módulos no instalados (verifica tablas antes de consultar).
     */
    public function syncSmartAlerts(Company $company, ?Branch $branch = null): void
    {
        $this->syncLowStockAlert($company, $branch);
        $this->syncNcfAlerts($company, $branch);
        $this->syncAppointmentAlerts($company, $branch);
        $this->syncWorkOrderAlerts($company, $branch);
        $this->syncCashSessionAlerts($company, $branch);
    }

    // ----------------------------------------------------------------
    // Alertas individuales
    // ----------------------------------------------------------------

    private function syncLowStockAlert(Company $company, ?Branch $branch): void
    {
        if (! Schema::hasTable('products')
            || ! Schema::hasTable('product_inventory_settings')
            || ! Schema::hasTable('inventory_stock')) {
            return;
        }

        $lowStockCount = DB::table('products as p')
            ->join('product_inventory_settings as pis', 'pis.product_id', '=', 'p.id')
            ->where('p.company_id', $company->id)
            ->where('p.is_active', true)
            ->where('p.track_inventory', true)
            ->where('pis.requires_inventory', true)
            ->whereRaw('COALESCE((SELECT SUM(quantity) FROM inventory_stock WHERE product_id = p.id), 0) <= pis.stock_min')
            ->count();

        $title = 'Inventario en nivel critico';

        if ($lowStockCount > 0) {
            $msg = "{$lowStockCount} producto(s) en o por debajo del stock minimo requerido.";
            $this->upsertAlert($company, $branch, 'inventory', 'warning', $title, $msg, '/inventario');
        } else {
            $this->resolveAlert($company, 'inventory', $title);
        }
    }

    private function syncNcfAlerts(Company $company, ?Branch $branch): void
    {
        if (! Schema::hasTable('ncf_sequences')) {
            return;
        }

        $sequences = NcfSequence::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereRaw('(end_number - current_number) <= alert_threshold')
            ->get();

        $title = 'Secuencias NCF por agotarse';

        if ($sequences->isNotEmpty()) {
            $count = $sequences->count();
            /** @var NcfSequence $first */
            $first = $sequences->first();
            $remaining = $first->end_number - $first->current_number;
            $code = $first->document_type_code;
            $msg = "{$count} secuencia(s) de comprobantes fiscales cerca del limite. Ej: {$code} ({$remaining} restantes).";
            $this->upsertAlert($company, $branch, 'fiscal', 'warning', $title, $msg, '/configuracion');
        } else {
            $this->resolveAlert($company, 'fiscal', $title);
        }
    }

    private function syncAppointmentAlerts(Company $company, ?Branch $branch): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        $todayCount = Appointment::query()
            ->where('company_id', $company->id)
            ->whereDate('scheduled_at', Carbon::today())
            ->whereIn('status', ['pendiente', 'confirmada'])
            ->count();

        $title = 'Citas de servicio para hoy';

        if ($todayCount > 0) {
            $msg = "Tienes {$todayCount} cita(s) programadas para hoy.";
            $this->upsertAlert($company, $branch, 'appointment', 'info', $title, $msg, '/agenda');
        } else {
            $this->resolveAlert($company, 'appointment', $title);
        }
    }

    private function syncWorkOrderAlerts(Company $company, ?Branch $branch): void
    {
        if (! Schema::hasTable('work_orders')) {
            return;
        }

        $readyCount = WorkOrder::query()
            ->where('company_id', $company->id)
            ->where('status', 'lista')
            ->count();

        $title = 'Ordenes de taller listas';

        if ($readyCount > 0) {
            $msg = "Hay {$readyCount} orden(es) de trabajo listas para entrega o cobro.";
            $this->upsertAlert($company, $branch, 'work_order', 'success', $title, $msg, '/taller/ordenes');
        } else {
            $this->resolveAlert($company, 'work_order', $title);
        }
    }

    private function syncCashSessionAlerts(Company $company, ?Branch $branch): void
    {
        if (! Schema::hasTable('cash_sessions')) {
            return;
        }

        $longOpenCount = CashSession::query()
            ->where('company_id', $company->id)
            ->where('status', 'open')
            ->where('opened_at', '<', Carbon::now()->subHours(12))
            ->count();

        $title = 'Turno de caja prolongado';

        if ($longOpenCount > 0) {
            $msg = "Hay {$longOpenCount} turno(s) de caja abierto(s) por mas de 12 horas sin cierre.";
            $this->upsertAlert($company, $branch, 'cash', 'warning', $title, $msg, '/pos/caja');
        } else {
            $this->resolveAlert($company, 'cash', $title);
        }
    }

    // ----------------------------------------------------------------
    // Helpers de upsert/resolve
    // ----------------------------------------------------------------

    private function upsertAlert(
        Company $company,
        ?Branch $branch,
        string $category,
        string $type,
        string $title,
        string $message,
        string $actionUrl
    ): void {
        $existing = SystemNotification::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->where('title', $title)
            ->whereNull('read_at')
            ->first();

        if ($existing) {
            $existing->update([
                'type' => $type,
                'message' => $message,
                'action_url' => $actionUrl,
            ]);
        } else {
            SystemNotification::create([
                'company_id' => $company->id,
                'branch_id' => $branch?->id,
                'category' => $category,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
            ]);
        }
    }

    private function resolveAlert(Company $company, string $category, string $title): void
    {
        SystemNotification::query()
            ->where('company_id', $company->id)
            ->where('category', $category)
            ->where('title', $title)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
