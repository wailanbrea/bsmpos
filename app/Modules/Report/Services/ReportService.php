<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Modules\Company\Models\Company;
use App\Modules\Inventory\Models\Purchase;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\POS\Models\CashSession;
use App\Modules\POS\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ReportService
{
    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function sales(Company $company, array $filters): array
    {
        $query = $this->salesQuery($company, $filters);
        $totals = $query->clone()
            ->selectRaw('COUNT(*) as invoice_count, COALESCE(SUM(subtotal), 0) as subtotal, COALESCE(SUM(discount_total), 0) as discount_total, COALESCE(SUM(tax_total), 0) as tax_total, COALESCE(SUM(tip_total), 0) as tip_total, COALESCE(SUM(total), 0) as total')
            ->firstOrFail();
        $invoices = $query->with(['customer', 'branch'])->orderBy('created_at')->get();

        return [
            'summary' => [
                'invoice_count' => $invoices->count(),
                'subtotal' => (string) $totals->subtotal,
                'discount_total' => (string) $totals->discount_total,
                'tax_total' => (string) $totals->tax_total,
                'tip_total' => (string) $totals->tip_total,
                'total' => (string) $totals->total,
            ],
            'rows' => $invoices->map(fn (Invoice $invoice): array => [
                'id' => $invoice->public_id,
                'issued_at' => $invoice->created_at?->toIso8601String(),
                'invoice_number' => $invoice->invoice_number,
                'ncf' => $invoice->ncf,
                'document_type_code' => $invoice->document_type_code,
                'branch' => $invoice->branch?->name,
                'customer' => $invoice->customer?->name,
                'total' => $invoice->total,
            ])->all(),
        ];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function cash(Company $company, array $filters): array
    {
        $query = $this->cashQuery($company, $filters);
        $totals = $query->clone()
            ->selectRaw('COUNT(*) as session_count, COALESCE(SUM(expected_amount), 0) as expected_amount, COALESCE(SUM(counted_amount), 0) as counted_amount, COALESCE(SUM(difference), 0) as difference')
            ->firstOrFail();
        $sessions = $query
            ->with('register')
            ->orderBy('closed_at')
            ->get();

        return [
            'summary' => [
                'session_count' => $sessions->count(),
                'expected_amount' => (string) $totals->expected_amount,
                'counted_amount' => (string) $totals->counted_amount,
                'difference' => (string) $totals->difference,
            ],
            'rows' => $sessions->map(fn (CashSession $session): array => [
                'id' => $session->public_id,
                'closed_at' => $session->closed_at ? Carbon::parse($session->closed_at)->toIso8601String() : null,
                'register' => $session->register?->name,
                'expected_amount' => $session->expected_amount,
                'counted_amount' => $session->counted_amount,
                'difference' => $session->difference,
            ])->all(),
        ];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function salesByProduct(Company $company, array $filters): array
    {
        $rows = $this->invoiceItemsQuery($company, $filters)
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->selectRaw('products.public_id as product_id, products.name as product_name, products.sku, SUM(invoice_items.quantity) as quantity, COALESCE(SUM(invoice_items.total), 0) as total')
            ->groupBy('products.id', 'products.public_id', 'products.name', 'products.sku')
            ->orderByDesc('total')
            ->get();

        return ['rows' => $rows->map(static fn (object $row): array => [
            'product_id' => $row->product_id,
            'product_name' => $row->product_name,
            'sku' => $row->sku,
            'quantity' => (string) $row->quantity,
            'total' => (string) $row->total,
        ])->all()];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function salesByCategory(Company $company, array $filters): array
    {
        $rows = $this->invoiceItemsQuery($company, $filters)
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw("categories.public_id as category_id, COALESCE(categories.name, 'Sin categoría') as category_name, SUM(invoice_items.quantity) as quantity, COALESCE(SUM(invoice_items.total), 0) as total")
            ->groupBy('categories.id', 'categories.public_id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        return ['rows' => $rows->map(static fn (object $row): array => [
            'category_id' => $row->category_id,
            'category_name' => $row->category_name,
            'quantity' => (string) $row->quantity,
            'total' => (string) $row->total,
        ])->all()];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function salesByPaymentMethod(Company $company, array $filters): array
    {
        $rows = DB::table('payments')
            ->where('payments.company_id', $company->getKey())
            ->whereExists(function ($query) use ($company, $filters): void {
                $query->selectRaw('1')
                    ->from('invoices')
                    ->whereColumn('invoices.order_id', 'payments.order_id')
                    ->where('invoices.company_id', $company->getKey())
                    ->where('invoices.status', 'paid')
                    ->whereDate('invoices.created_at', '>=', $filters['from'])
                    ->whereDate('invoices.created_at', '<=', $filters['to'])
                    ->when($filters['branch_id'] ?? null, fn ($subquery, string $branchId) => $subquery->where('invoices.branch_id', $branchId));
            })
            ->selectRaw('payments.payment_method_code, payments.currency_code, COALESCE(SUM(payments.amount_in_base - payments.change_amount), 0) as total')
            ->groupBy('payments.payment_method_code', 'payments.currency_code')
            ->orderByDesc('total')
            ->get();

        return ['rows' => $rows->map(static fn (object $row): array => [
            'payment_method_code' => $row->payment_method_code,
            'currency_code' => $row->currency_code,
            'total' => (string) $row->total,
        ])->all()];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function salesByCashier(Company $company, array $filters): array
    {
        $rows = $this->paidInvoicesQuery($company, $filters)
            ->join('users', 'users.id', '=', 'invoices.created_by')
            ->selectRaw('users.public_id as cashier_id, users.name as cashier_name, COUNT(invoices.id) as invoice_count, COALESCE(SUM(invoices.total), 0) as total')
            ->groupBy('users.id', 'users.public_id', 'users.name')
            ->orderByDesc('total')
            ->get();

        return ['rows' => $rows->map(static fn (object $row): array => [
            'cashier_id' => $row->cashier_id,
            'cashier_name' => $row->cashier_name,
            'invoice_count' => (int) $row->invoice_count,
            'total' => (string) $row->total,
        ])->all()];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function salesByCustomer(Company $company, array $filters): array
    {
        $rows = $this->paidInvoicesQuery($company, $filters)
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->selectRaw('customers.public_id as customer_id, customers.name as customer_name, COUNT(invoices.id) as invoice_count, COALESCE(SUM(invoices.total), 0) as total')
            ->groupBy('customers.id', 'customers.public_id', 'customers.name')
            ->orderByDesc('total')
            ->get();

        return ['rows' => $rows->map(static fn (object $row): array => [
            'customer_id' => $row->customer_id,
            'customer_name' => $row->customer_name,
            'invoice_count' => (int) $row->invoice_count,
            'total' => (string) $row->total,
        ])->all()];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function salesTaxes(Company $company, array $filters): array
    {
        $rows = $this->invoiceItemsQuery($company, $filters)->leftJoin('taxes', 'taxes.id', '=', 'invoice_items.tax_id')
            ->selectRaw("COALESCE(taxes.code, 'sin_impuesto') as tax_code, COALESCE(taxes.name, 'Sin impuesto') as tax_name, COALESCE(taxes.rate, 0) as rate, COALESCE(SUM(invoice_items.tax_amount), 0) as tax_total, COALESCE(SUM(invoice_items.total - invoice_items.tax_amount), 0) as taxable_amount")
            ->groupBy('taxes.id', 'taxes.code', 'taxes.name', 'taxes.rate')->orderByDesc('tax_total')->get();

        return ['rows' => $rows->map(static fn (object $row): array => ['tax_code' => $row->tax_code, 'tax_name' => $row->tax_name, 'rate' => (string) $row->rate, 'taxable_amount' => (string) $row->taxable_amount, 'tax_total' => (string) $row->tax_total])->all()];
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function salesDiscounts(Company $company, array $filters): array
    {
        $query = $this->salesQuery($company, $filters)->where('discount_total', '>', 0);
        $total = $query->clone()->selectRaw('COALESCE(SUM(discount_total), 0) as discount_total')->firstOrFail();
        $invoices = $query->with(['customer', 'branch'])->orderBy('created_at')->get();

        return ['summary' => ['invoice_count' => $invoices->count(), 'discount_total' => (string) $total->discount_total], 'rows' => $invoices->map(static fn (Invoice $invoice): array => ['id' => $invoice->public_id, 'issued_at' => $invoice->created_at?->toIso8601String(), 'invoice_number' => $invoice->invoice_number, 'customer' => $invoice->customer?->name, 'branch' => $invoice->branch?->name, 'discount_total' => $invoice->discount_total, 'total' => $invoice->total])->all()];
    }

    /**
     * Reporte operativo de facturas anuladas en el período (base del 608).
     *
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function annulments(Company $company, array $filters): array
    {
        $invoices = Invoice::query()
            ->with(['customer', 'branch'])
            ->where('company_id', $company->getKey())
            ->where('status', 'canceled')
            ->whereDate('canceled_at', '>=', $filters['from'])
            ->whereDate('canceled_at', '<=', $filters['to'])
            ->when($filters['branch_id'] ?? null, fn (Builder $query, string $branchId): Builder => $query->where('branch_id', $branchId))
            ->orderBy('canceled_at')
            ->get();

        return [
            'summary' => [
                'invoice_count' => $invoices->count(),
                'total' => (string) $invoices->sum(fn (Invoice $invoice): float => (float) $invoice->total),
            ],
            'rows' => $invoices->map(static fn (Invoice $invoice): array => [
                'id' => $invoice->public_id,
                'canceled_at' => $invoice->canceled_at ? Carbon::parse($invoice->canceled_at)->toIso8601String() : null,
                'invoice_number' => $invoice->invoice_number,
                'ncf' => $invoice->ncf,
                'reason_code' => $invoice->cancellation_reason_code,
                'branch' => $invoice->branch?->name,
                'customer' => $invoice->customer?->name,
                'total' => $invoice->total,
            ])->all(),
        ];
    }

    /**
     * Genera el contenido TXT del Formato 608 de NCF anulados.
     *
     * @throws ApiException
     */
    public function dgii608(Company $company, string $period): string
    {
        $issuerTaxId = (string) $company->tax_id;
        if (preg_match('/^\d{9}(\d{2})?$/', $issuerTaxId) !== 1) {
            throw new ApiException(ErrorCode::ValidationFailed, 'La empresa debe tener un RNC o cédula numérico válido para exportar el formato 608.', 422);
        }

        $date = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $invoices = Invoice::query()
            ->where('company_id', $company->getKey())
            ->where('status', 'canceled')
            ->whereBetween('canceled_at', [$date, $date->clone()->endOfMonth()])
            ->orderBy('canceled_at')
            ->get(['ncf', 'canceled_at', 'cancellation_reason_code']);

        foreach ($invoices as $invoice) {
            if (! is_string($invoice->ncf) || preg_match('/^B\d{2}\d{8}$/', $invoice->ncf) !== 1 || $invoice->canceled_at === null || $invoice->cancellation_reason_code === null) {
                throw new ApiException(ErrorCode::ValidationFailed, 'No se puede exportar 608: existen anulaciones sin NCF tradicional completo, fecha o motivo DGII.', 422);
            }
        }

        $lines = [implode('|', ['608', $issuerTaxId, $date->format('Ym'), (string) $invoices->count()])];
        foreach ($invoices as $invoice) {
            $lines[] = implode('|', [
                $invoice->ncf,
                Carbon::parse($invoice->canceled_at)->format('Ymd'),
                (string) $invoice->cancellation_reason_code,
            ]);
        }

        return implode("\r\n", $lines)."\r\n";
    }

    /** Genera el contenido TXT del Formato 606 de compras y gastos. */
    public function dgii606(Company $company, string $period): string
    {
        $issuerTaxId = (string) $company->tax_id;
        if (preg_match('/^\d{9}(\d{2})?$/', $issuerTaxId) !== 1) {
            throw new ApiException(ErrorCode::ValidationFailed, 'La empresa debe tener un RNC o cédula numérico válido para exportar el formato 606.', 422);
        }

        $date = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $purchases = Purchase::query()
            ->with('fiscalData')
            ->where('company_id', $company->getKey())
            ->where('status', 'confirmed')
            ->whereBetween('purchase_date', [$date->toDateString(), $date->clone()->endOfMonth()->toDateString()])
            ->orderBy('purchase_date')
            ->get();

        $lines = [implode('|', ['606', $issuerTaxId, $date->format('Ym'), (string) $purchases->count()])];
        foreach ($purchases as $purchase) {
            $fiscal = $purchase->fiscalData;
            if ($fiscal === null || preg_match('/^\d{9}(\d{2})?$/', $fiscal->supplier_tax_id) !== 1 || preg_match('/^B\d{2}\d{8}$/', $fiscal->ncf) !== 1) {
                throw new ApiException(ErrorCode::ValidationFailed, 'No se puede exportar 606: existen compras confirmadas sin datos fiscales completos.', 422);
            }

            $lines[] = implode('|', [
                $fiscal->supplier_tax_id, $fiscal->supplier_tax_id_type === 'rnc' ? '1' : '2', (string) $fiscal->expense_type_code,
                $fiscal->ncf, $fiscal->affected_ncf ?? '', Carbon::parse($purchase->purchase_date)->format('Ymd'), $fiscal->payment_date ? Carbon::parse($fiscal->payment_date)->format('Ymd') : '',
                $fiscal->services_amount, $fiscal->goods_amount, $fiscal->total_billed, $fiscal->itbis_invoiced, $fiscal->itbis_withheld,
                $fiscal->itbis_proportional, $fiscal->itbis_cost, $fiscal->itbis_advance, $fiscal->itbis_perceived,
                $fiscal->isr_withholding_type ?? '', $fiscal->isr_withheld, $fiscal->isr_perceived, $fiscal->selective_tax,
                $fiscal->other_taxes, $fiscal->legal_tip, (string) $fiscal->payment_form_code,
            ]);
        }

        return implode("\r\n", $lines)."\r\n";
    }

    /** Genera el contenido TXT del Formato 607 de ventas y operaciones. */
    public function dgii607(Company $company, string $period): string
    {
        $issuerTaxId = (string) $company->tax_id;
        if (preg_match('/^\d{9}(\d{2})?$/', $issuerTaxId) !== 1) {
            throw new ApiException(ErrorCode::ValidationFailed, 'La empresa debe tener un RNC o cédula numérico válido para exportar el formato 607.', 422);
        }
        $date = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $invoices = Invoice::query()->with('customer')->where('company_id', $company->getKey())->where('status', 'paid')->whereBetween('created_at', [$date, $date->clone()->endOfMonth()])->where('document_type_code', 'like', 'B%')->orderBy('created_at')->get();
        $orderIds = $invoices->pluck('order_id')->filter()->all();
        $payments = Payment::query()->where('company_id', $company->getKey())->whereIn('order_id', $orderIds)->get()->groupBy('order_id');
        $lines = [];
        foreach ($invoices as $invoice) {
            if (! is_string($invoice->ncf) || preg_match('/^B\d{2}\d{8}$/', $invoice->ncf) !== 1) {
                throw new ApiException(ErrorCode::ValidationFailed, 'No se puede exportar 607: existen facturas sin NCF válido.', 422);
            }
            if ($invoice->document_type_code === 'B02' && bccomp((string) $invoice->total, '50000.00', 2) < 0) {
                continue;
            }
            $customer = $invoice->customer;
            if ($customer === null) {
                throw new ApiException(ErrorCode::ValidationFailed, 'No se puede exportar 607: existen facturas sin cliente fiscal.', 422);
            }
            $idType = match ($customer->tax_id_type) {
                'rnc' => '1', 'cedula' => '2', 'passport' => '3', default => null
            };
            if ($idType === null || ! is_string($customer->tax_id) || $customer->tax_id === '') {
                throw new ApiException(ErrorCode::ValidationFailed, 'No se puede exportar 607: la factura requiere identificación fiscal del cliente.', 422);
            }
            $forms = array_fill(0, 7, '0.00');
            foreach ($payments->get($invoice->order_id, collect()) as $payment) {
                $index = match ($payment->payment_method_code) {
                    'cash' => 0, 'transfer' => 1, 'card' => 2, 'credit' => 3, default => 6
                };
                $forms[$index] = bcadd($forms[$index], bcsub((string) $payment->amount_in_base, (string) $payment->change_amount, 2), 2);
            }
            $lines[] = implode('|', [$customer->tax_id, $idType, $invoice->ncf, $invoice->affected_ncf ?? '', '1', Carbon::parse($invoice->created_at)->format('Ymd'), '', bcsub((string) $invoice->subtotal, (string) $invoice->discount_total, 2), $invoice->tax_total, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', $invoice->tip_total, ...$forms]);
        }

        return implode("\r\n", [implode('|', ['607', $issuerTaxId, $date->format('Ym'), (string) count($lines)]), ...$lines])."\r\n";
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return Builder<CashSession>
     */
    private function cashQuery(Company $company, array $filters): Builder
    {
        return CashSession::query()
            ->where('company_id', $company->getKey())
            ->where('status', 'closed')
            ->whereDate('closed_at', '>=', $filters['from'])
            ->whereDate('closed_at', '<=', $filters['to'])
            ->when($filters['branch_id'] ?? null, fn (Builder $query, string $branchId): Builder => $query->where('branch_id', $branchId));
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     * @return Builder<Invoice>
     */
    private function salesQuery(Company $company, array $filters): Builder
    {
        return Invoice::query()
            ->where('company_id', $company->getKey())
            ->where('status', 'paid')
            ->whereDate('created_at', '>=', $filters['from'])
            ->whereDate('created_at', '<=', $filters['to'])
            ->when($filters['branch_id'] ?? null, fn (Builder $query, string $branchId): Builder => $query->where('branch_id', $branchId));
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     */
    private function invoiceItemsQuery(Company $company, array $filters): \Illuminate\Database\Query\Builder
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.company_id', $company->getKey())
            ->where('invoices.status', 'paid')
            ->whereDate('invoices.created_at', '>=', $filters['from'])
            ->whereDate('invoices.created_at', '<=', $filters['to'])
            ->when($filters['branch_id'] ?? null, fn ($query, string $branchId) => $query->where('invoices.branch_id', $branchId));
    }

    /**
     * @param  array{from: string, to: string, branch_id?: string|null}  $filters
     */
    private function paidInvoicesQuery(Company $company, array $filters): \Illuminate\Database\Query\Builder
    {
        return DB::table('invoices')
            ->where('invoices.company_id', $company->getKey())
            ->where('invoices.status', 'paid')
            ->whereDate('invoices.created_at', '>=', $filters['from'])
            ->whereDate('invoices.created_at', '<=', $filters['to'])
            ->when($filters['branch_id'] ?? null, fn ($query, string $branchId) => $query->where('invoices.branch_id', $branchId));
    }
}
