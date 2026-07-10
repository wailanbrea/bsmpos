<?php

declare(strict_types=1);

use App\Modules\Report\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'permission:reports.view'])->group(function (): void {
    Route::get('reports/sales', [ReportController::class, 'sales']);
    Route::get('reports/sales/export.csv', [ReportController::class, 'exportSalesCsv']);
    Route::get('reports/dgii/608', [ReportController::class, 'exportDgii608']);
    Route::get('reports/dgii/606', [ReportController::class, 'exportDgii606']);
    Route::get('reports/dgii/607', [ReportController::class, 'exportDgii607']);
    Route::get('reports/sales/by-product', [ReportController::class, 'salesByProduct']);
    Route::get('reports/sales/by-category', [ReportController::class, 'salesByCategory']);
    Route::get('reports/sales/by-payment-method', [ReportController::class, 'salesByPaymentMethod']);
    Route::get('reports/sales/by-cashier', [ReportController::class, 'salesByCashier']);
    Route::get('reports/sales/by-customer', [ReportController::class, 'salesByCustomer']);
    Route::get('reports/sales/taxes', [ReportController::class, 'salesTaxes']);
    Route::get('reports/sales/discounts', [ReportController::class, 'salesDiscounts']);
    Route::get('reports/cash', [ReportController::class, 'cash']);
    Route::get('reports/annulments', [ReportController::class, 'annulments']);
});
