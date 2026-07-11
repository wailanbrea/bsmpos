<?php

declare(strict_types=1);

namespace App\Modules\Report\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Support\PdfTableDocument;
use App\Core\Support\XlsxWriter;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Company\Models\Branch;
use App\Modules\Report\Http\Requests\ExportDgii608Request;
use App\Modules\Report\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController
{
    public function __construct(private readonly ReportService $reports) {}

    public function sales(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->sales($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function cash(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->cash($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function annulments(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->annulments($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function salesByProduct(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->salesByProduct($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function salesByCategory(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->salesByCategory($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function salesByPaymentMethod(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->salesByPaymentMethod($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function salesByCashier(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->salesByCashier($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function salesByCustomer(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->salesByCustomer($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function salesTaxes(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->salesTaxes($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function salesDiscounts(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success($this->reports->salesDiscounts($currentCompany->company(), $this->filters($request, $currentCompany)));
    }

    public function exportSalesCsv(Request $request, CurrentCompany $currentCompany): StreamedResponse
    {
        $report = $this->reports->sales($currentCompany->company(), $this->filters($request, $currentCompany));

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['Fecha', 'Factura', 'NCF', 'Tipo', 'Sucursal', 'Cliente', 'Total']);
            foreach ($report['rows'] as $row) {
                fputcsv($stream, [$row['issued_at'], $row['invoice_number'], $row['ncf'], $row['document_type_code'], $row['branch'], $row['customer'], $row['total']]);
            }
            fclose($stream);
        }, $this->salesFilename($request).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportSalesXlsx(Request $request, CurrentCompany $currentCompany): Response
    {
        $report = $this->reports->sales($currentCompany->company(), $this->filters($request, $currentCompany));
        [$headers, $rows] = $this->salesTable($report);

        return response(XlsxWriter::build($headers, $rows, 'Ventas'), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->salesFilename($request).'.xlsx"',
        ]);
    }

    public function exportSalesPdf(Request $request, CurrentCompany $currentCompany): Response
    {
        $report = $this->reports->sales($currentCompany->company(), $this->filters($request, $currentCompany));
        [$headers, $rows] = $this->salesTable($report);
        $title = 'Reporte de ventas '.$request->query('from', now()->toDateString()).' a '.$request->query('to', now()->toDateString());

        return response(PdfTableDocument::build($title, $headers, $rows), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->salesFilename($request).'.pdf"',
        ]);
    }

    /**
     * @param  array{rows: list<array<string, mixed>>}  $report
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function salesTable(array $report): array
    {
        $headers = ['Fecha', 'Factura', 'NCF', 'Tipo', 'Sucursal', 'Cliente', 'Total'];
        $rows = array_map(static fn (array $row): array => [
            (string) $row['issued_at'],
            (string) $row['invoice_number'],
            (string) $row['ncf'],
            (string) $row['document_type_code'],
            (string) $row['branch'],
            (string) $row['customer'],
            (string) $row['total'],
        ], $report['rows']);

        return [$headers, $rows];
    }

    private function salesFilename(Request $request): string
    {
        return 'ventas-'.$request->query('from', now()->toDateString()).'-'.$request->query('to', now()->toDateString());
    }

    public function exportDgii608(ExportDgii608Request $request, CurrentCompany $currentCompany): StreamedResponse
    {
        $period = $request->validated('period');
        $content = $this->reports->dgii608($currentCompany->company(), $period);

        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        }, "608-{$period}.txt", ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function exportDgii606(ExportDgii608Request $request, CurrentCompany $currentCompany): StreamedResponse
    {
        $period = $request->validated('period');
        $content = $this->reports->dgii606($currentCompany->company(), $period);

        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        }, "606-{$period}.txt", ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function exportDgii607(ExportDgii608Request $request, CurrentCompany $currentCompany): StreamedResponse
    {
        $period = $request->validated('period');
        $content = $this->reports->dgii607($currentCompany->company(), $period);

        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        }, "607-{$period}.txt", ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** @return array{from: string, to: string, branch_id?: string|null} */
    private function filters(Request $request, CurrentCompany $currentCompany): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'ulid'],
        ]);
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $branchId = $data['branch_id'] ?? null;
        if ($branchId !== null) {
            $branchId = Branch::query()->where('company_id', $currentCompany->company()->getKey())->where('public_id', $branchId)->sole()->getKey();
        }

        return ['from' => $from, 'to' => $to, 'branch_id' => $branchId];
    }
}
