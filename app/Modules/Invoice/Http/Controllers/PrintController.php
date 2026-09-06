<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Support\TicketBuilder;
use App\Modules\POS\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

final class PrintController
{
    public function printHtml(string $publicId, Request $request, CurrentCompany $currentCompany): Response
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver facturas.', 403);
        }

        $invoice = Invoice::query()
            ->with(['items.product', 'customer', 'branch'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($invoice === null) {
            throw new ApiException(ErrorCode::NotFound, 'La factura no existe.', 404);
        }

        $isA4 = $request->query('format') === 'A4';

        $html = $isA4 ? $this->renderA4Html($invoice) : $this->renderTicketHtml($invoice);

        return response($html)->header('Content-Type', 'text/html');
    }

    public function printRawInvoice(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para imprimir facturas.', 403);
        }

        $invoice = Invoice::query()
            ->with(['items.product', 'customer', 'branch'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($invoice === null) {
            throw new ApiException(ErrorCode::NotFound, 'La factura no existe.', 404);
        }

        // Obtener ancho de papel de la caja (si tiene asignada en request/session)
        $paperWidth = $request->query('width', '80mm');
        $builder = new TicketBuilder($paperWidth);

        // Diseñar ticket ESC/POS
        $builder->align('center')->bold()->doubleHeight()->line($this->branchName($invoice, 'EMPRESA SAAS'));
        $builder->bold(false)->doubleHeight(false)->line('RNC: '.$this->customerTaxId($invoice, 'NO FISCAL'));
        $builder->line('Tel: 809-555-0199');
        $builder->separator();

        $builder->align('left');
        $builder->line('Factura: '.$invoice->invoice_number);
        $builder->line('NCF: '.($invoice->ncf ?? 'B0200000000'));
        if ($invoice->ncf_expires_at) {
            $builder->line('Vence: '.$this->date($invoice->ncf_expires_at));
        }
        $builder->line('Fecha: '.$this->dateTime($invoice->created_at));
        $builder->line('Cliente: '.$this->customerName($invoice, 'Cliente Genérico'));
        $builder->separator();

        $builder->bold();
        $builder->row('Cant/Desc', 'Total');
        $builder->bold(false);
        $builder->separator('.');

        foreach ($invoice->items as $item) {
            $desc = number_format((float) $item->quantity, 0).' x '.$item->product?->name;
            $builder->row($desc, 'RD$ '.number_format((float) $item->total, 2));
            if ($item->batch_number) {
                $builder->line('   Lote: '.$item->batch_number);
            }
        }
        $builder->separator();

        $builder->row('Subtotal:', 'RD$ '.number_format((float) $invoice->subtotal, 2));
        if ((float) $invoice->discount_total > 0) {
            $builder->row('Descuento:', '-RD$ '.number_format((float) $invoice->discount_total, 2));
        }
        $builder->row('ITBIS (18%):', 'RD$ '.number_format((float) $invoice->tax_total, 2));
        if ((float) $invoice->tip_total > 0) {
            $builder->row('Propina (10%):', 'RD$ '.number_format((float) $invoice->tip_total, 2));
        }
        $builder->bold()->row('TOTAL:', 'RD$ '.number_format((float) $invoice->total, 2))->bold(false);
        $builder->separator();

        $builder->align('center')->bold()->line('¡GRACIAS POR SU COMPRA!')->bold(false);
        $builder->line('BSM-POS Modular SaaS');
        $builder->cut();

        return ApiResponse::success(['commands' => base64_encode($builder->build())]);
    }

    public function printTextInvoice(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para imprimir facturas.', 403);
        }

        $invoice = Invoice::query()
            ->with(['items.product', 'customer', 'branch'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($invoice === null) {
            throw new ApiException(ErrorCode::NotFound, 'La factura no existe.', 404);
        }

        $width = $request->query('width', '80mm') === '58mm' ? 32 : 42;
        $sep = str_repeat('=', $width);
        $dash = str_repeat('-', $width);

        $lines = [];
        $lines[] = $sep;
        $companyName = $invoice->branch->name;
        $lines[] = str_pad(mb_substr($companyName, 0, $width), $width, ' ', STR_PAD_BOTH);
        $rnc = $this->customerTaxId($invoice, 'NO FISCAL');
        $lines[] = str_pad("RNC: {$rnc}", $width, ' ', STR_PAD_BOTH);
        $lines[] = $sep;
        $lines[] = "Factura: {$invoice->invoice_number}";
        $lines[] = 'NCF: '.($invoice->ncf ?? 'B0200000000');
        if ($invoice->ncf_expires_at) {
            $lines[] = 'Vence: '.$this->date($invoice->ncf_expires_at);
        }
        $lines[] = 'Fecha: '.$this->dateTime($invoice->created_at);
        $lines[] = 'Cliente: '.$this->customerName($invoice, 'Cliente Genérico');
        $lines[] = $sep;

        foreach ($invoice->items as $item) {
            $qty = number_format((float) $item->quantity, 0);
            $name = mb_substr($item->product->name, 0, $width - 16);
            $tot = 'RD$ '.number_format((float) $item->total, 2);
            $left = "{$qty} x {$name}";
            $space = max(1, $width - mb_strlen($left) - mb_strlen($tot));
            $lines[] = $left.str_repeat(' ', $space).$tot;
            if ($item->batch_number) {
                $lines[] = "  Lote: {$item->batch_number}";
            }
        }

        $lines[] = $dash;
        $subtotal = 'RD$ '.number_format((float) $invoice->subtotal, 2);
        $lines[] = 'Subtotal:'.str_repeat(' ', max(1, $width - 9 - mb_strlen($subtotal))).$subtotal;

        if ((float) $invoice->discount_total > 0) {
            $disc = '-RD$ '.number_format((float) $invoice->discount_total, 2);
            $lines[] = 'Descuento:'.str_repeat(' ', max(1, $width - 10 - mb_strlen($disc))).$disc;
        }

        $tax = 'RD$ '.number_format((float) $invoice->tax_total, 2);
        $lines[] = 'ITBIS (18%):'.str_repeat(' ', max(1, $width - 12 - mb_strlen($tax))).$tax;

        if ((float) $invoice->tip_total > 0) {
            $tip = 'RD$ '.number_format((float) $invoice->tip_total, 2);
            $lines[] = 'Propina (10%):'.str_repeat(' ', max(1, $width - 14 - mb_strlen($tip))).$tip;
        }

        $lines[] = $sep;
        $total = 'RD$ '.number_format((float) $invoice->total, 2);
        $lines[] = 'TOTAL:'.str_repeat(' ', max(1, $width - 6 - mb_strlen($total))).$total;
        $lines[] = $sep;
        $lines[] = str_pad('¡GRACIAS POR SU COMPRA!', $width, ' ', STR_PAD_BOTH);
        $lines[] = str_pad('BSM-POS Modular SaaS', $width, ' ', STR_PAD_BOTH);

        return ApiResponse::success([
            'content' => implode("\n", $lines),
        ]);
    }

    public function printRawSession(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para imprimir cierres.', 403);
        }

        $session = CashSession::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($session === null) {
            throw new ApiException(ErrorCode::NotFound, 'La sesión de caja no existe.', 404);
        }

        $builder = new TicketBuilder('80mm');
        $builder->align('center')->bold()->doubleHeight()->line('ARQUEO DE CAJA');
        $builder->bold(false)->doubleHeight(false)->line('Caja: '.data_get($session, 'register.name', 'Caja'));
        $builder->line('Cajero: '.data_get($session, 'openedBy.name', 'Cajero'));
        $builder->separator();

        $builder->align('left');
        $builder->line('Apertura: '.$this->dateTime($session->opened_at));
        if ($session->closed_at) {
            $builder->line('Cierre: '.$this->dateTime($session->closed_at));
        }
        $builder->separator();

        $builder->row('Monto Apertura:', 'RD$ '.number_format((float) $session->opening_amount, 2));
        $builder->row('Monto Esperado:', 'RD$ '.number_format((float) $session->expected_amount, 2));
        $builder->row('Monto Real (Contado):', 'RD$ '.number_format((float) $session->counted_amount, 2));

        $diff = (float) $session->difference;
        $diffLabel = $diff >= 0 ? 'Sobrante:' : 'Faltante:';
        $builder->bold()->row($diffLabel, 'RD$ '.number_format(abs($diff), 2))->bold(false);

        $builder->separator();
        $builder->align('center')->line('Firma del Cajero y Supervisor');
        $builder->line("\n\n\n______________________\nSupervisor");
        $builder->cut();

        return ApiResponse::success(['commands' => base64_encode($builder->build())]);
    }

    private function renderTicketHtml(Invoice $inv): string
    {
        $itemsHtml = '';
        foreach ($inv->items as $item) {
            $desc = number_format((float) $item->quantity, 0).' x '.htmlspecialchars((string) data_get($item, 'product.name', 'Prod'));
            $lote = $item->batch_number ? "<span style='display:block;font-size:9px;color:#666;'>Lote: ".htmlspecialchars($item->batch_number).'</span>' : '';
            $itemsHtml .= "
                <tr>
                    <td style='padding:4px 0;'>{$desc}{$lote}</td>
                    <td style='padding:4px 0;text-align:right;'>RD$ ".number_format((float) $item->total, 2).'</td>
                </tr>
            ';
        }

        $discHtml = (float) $inv->discount_total > 0 ? "
            <div style='display:flex;justify-content:between;color:red;'>
                <span>Descuento:</span>
                <span style='margin-left:auto;'>-RD$ ".number_format((float) $inv->discount_total, 2).'</span>
            </div>
        ' : '';

        $tipHtml = (float) $inv->tip_total > 0 ? "
            <div style='display:flex;justify-content:between;'>
                <span>Propina (10%):</span>
                <span style='margin-left:auto;'>RD$ ".number_format((float) $inv->tip_total, 2).'</span>
            </div>
        ' : '';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Ticket #{$inv->invoice_number}</title>
            <style>
                @page { margin: 0; }
                body {
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 12px;
                    line-height: 1.4;
                    width: 80mm;
                    margin: 0;
                    padding: 10px;
                    box-sizing: border-box;
                    background-color: white;
                }
                .text-center { text-align: center; }
                .bold { font-weight: bold; }
                .divider { border-bottom: 1px dashed black; margin: 8px 0; }
                @media print {
                    body { width: 80mm; padding: 0; margin: 0; }
                }
            </style>
        </head>
        <body onload='window.print()'>
            <div class='text-center'>
                <span class='bold' style='font-size:14px;'>".htmlspecialchars($this->branchName($inv, 'Mi Sucursal')).'</span><br>
                RNC: '.htmlspecialchars($this->customerTaxId($inv, 'NO FISCAL'))."<br>
                Tel: 809-555-0199
            </div>
            <div class='divider'></div>
            <div>
                Factura: {$inv->invoice_number}<br>
                NCF: ".htmlspecialchars($inv->ncf ?? 'B0200000000').'<br>
                Tipo: '.($inv->document_type_code === 'B01' ? 'Crédito Fiscal (B01)' : 'Consumidor Final (B02)').'<br>
                Fecha: '.$this->dateTime($inv->created_at).'<br>
                Cliente: '.htmlspecialchars($this->customerName($inv, 'Consumidor Final'))."
            </div>
            <div class='divider'></div>
            <table style='width:100%;border-collapse:collapse;'>
                <thead>
                    <tr style='border-bottom:1px dashed black;'>
                        <th style='text-align:left;padding-bottom:4px;'>Cant/Desc</th>
                        <th style='text-align:right;padding-bottom:4px;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>
            <div class='divider'></div>
            <div style='display:flex;flex-direction:column;gap:2px;'>
                <div style='display:flex;justify-content:between;'>
                    <span>Subtotal:</span>
                    <span style='margin-left:auto;'>RD$ ".number_format((float) $inv->subtotal, 2)."</span>
                </div>
                {$discHtml}
                <div style='display:flex;justify-content:between;'>
                    <span>ITBIS (18%):</span>
                    <span style='margin-left:auto;'>RD$ ".number_format((float) $inv->tax_total, 2)."</span>
                </div>
                {$tipHtml}
                <div class='bold' style='display:flex;justify-content:between;font-size:13px;border-top:1px dashed black;padding-top:4px;'>
                    <span>TOTAL:</span>
                    <span style='margin-left:auto;'>RD$ ".number_format((float) $inv->total, 2)."</span>
                </div>
            </div>
            <div class='divider'></div>
            <div class='text-center bold' style='margin-top:12px;'>
                ¡GRACIAS POR SU COMPRA!<br>
                BSM-POS Modular SaaS
            </div>
        </body>
        </html>
        ";
    }

    private function renderA4Html(Invoice $inv): string
    {
        $itemsHtml = '';
        foreach ($inv->items as $item) {
            $desc = htmlspecialchars((string) data_get($item, 'product.name', 'Prod'));
            $lote = $item->batch_number ? "<span style='display:block;font-size:10px;color:#555;'>Lote: ".htmlspecialchars($item->batch_number).'</span>' : '';
            $itemsHtml .= "
                <tr style='border-bottom: 1px solid #e2e8f0;'>
                    <td style='padding:10px;'>".number_format((float) $item->quantity, 2)."</td>
                    <td style='padding:10px;'>{$desc}{$lote}</td>
                    <td style='padding:10px;text-align:right;'>RD$ ".number_format((float) $item->price, 2)."</td>
                    <td style='padding:10px;text-align:right;'>RD$ ".number_format((float) $item->discount_amount, 2)."</td>
                    <td style='padding:10px;text-align:right;'>RD$ ".number_format((float) $item->tax_amount, 2)."</td>
                    <td style='padding:10px;text-align:right;font-weight:bold;'>RD$ ".number_format((float) $item->total, 2).'</td>
                </tr>
            ';
        }

        $discHtml = (float) $inv->discount_total > 0 ? "
            <div style='display:flex;justify-content:space-between;padding:4px 0;'>
                <span style='color:#718096;'>Descuento:</span>
                <span style='font-weight:bold;'>-RD$ ".number_format((float) $inv->discount_total, 2).'</span>
            </div>
        ' : '';

        $tipHtml = (float) $inv->tip_total > 0 ? "
            <div style='display:flex;justify-content:space-between;padding:4px 0;'>
                <span style='color:#718096;'>Propina Legal (10%):</span>
                <span style='font-weight:bold;'>RD$ ".number_format((float) $inv->tip_total, 2).'</span>
            </div>
        ' : '';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Factura #{$inv->invoice_number}</title>
            <style>
                body {
                    font-family: 'Segoe UI', system-ui, sans-serif;
                    color: #2d3748;
                    margin: 0;
                    padding: 40px;
                    background-color: white;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                }
                .header {
                    display: flex;
                    justify-content: space-between;
                    border-bottom: 2px solid #e2e8f0;
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                }
                .title {
                    font-size: 24px;
                    font-weight: bold;
                    color: #1a365d;
                }
                .grid {
                    display: grid;
                    grid-template-cols: 1fr 1fr;
                    gap: 20px;
                    margin-bottom: 30px;
                }
                .label {
                    font-size: 11px;
                    font-weight: bold;
                    color: #718096;
                    text-transform: uppercase;
                    margin-bottom: 2px;
                }
                .value {
                    font-size: 13px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                }
                th {
                    background-color: #f7fafc;
                    font-size: 11px;
                    text-transform: uppercase;
                    color: #718096;
                    padding: 10px;
                    border-bottom: 2px solid #e2e8f0;
                }
                .summary {
                    display: flex;
                    justify-content: flex-end;
                }
                .summary-box {
                    width: 300px;
                    border-top: 2px solid #e2e8f0;
                    padding-top: 10px;
                }
                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body onload='window.print()'>
            <div class='container'>
                <div class='header'>
                    <div>
                        <span class='title'>".htmlspecialchars($this->branchName($inv, 'Empresa SaaS'))."</span><br>
                        <span style='font-size:13px;color:#718096;'>Factura Comercial Dominicana</span>
                    </div>
                    <div style='text-align:right;font-size:13px;'>
                        <span class='bold' style='color:#1a365d;font-size:16px;'>NCF: ".htmlspecialchars($inv->ncf ?? 'B0200000000').'</span><br>
                        Vence: '.($inv->ncf_expires_at ? $this->date($inv->ncf_expires_at) : 'N/A').'<br>
                        Tipo: '.($inv->document_type_code === 'B01' ? 'Crédito Fiscal (B01)' : 'Consumidor Final (B02)')."
                    </div>
                </div>

                <div class='grid'>
                    <div>
                        <div class='label'>Emisor</div>
                        <div class='value'>
                            <strong>".htmlspecialchars($this->branchName($inv, 'Empresa Principal'))."</strong><br>
                            RNC: 131793916<br>
                            Tel: 809-555-0199
                        </div>
                    </div>
                    <div>
                        <div class='label'>Cliente</div>
                        <div class='value'>
                            <strong>".htmlspecialchars($this->customerName($inv, 'Consumidor Final')).'</strong><br>
                            RNC/Cédula: '.htmlspecialchars($this->customerTaxId($inv, 'N/A')).'<br>
                            Fecha Emisión: '.$inv->created_at?->toDateTimeString()."
                        </div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style='text-align:left;'>Cant</th>
                            <th style='text-align:left;'>Descripción</th>
                            <th style='text-align:right;'>Precio</th>
                            <th style='text-align:right;'>Desc.</th>
                            <th style='text-align:right;'>ITBIS</th>
                            <th style='text-align:right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>

                <div class='summary'>
                    <div class='summary-box'>
                        <div style='display:flex;justify-content:space-between;padding:4px 0;'>
                            <span style='color:#718096;'>Subtotal:</span>
                            <span style='font-weight:bold;'>RD$ ".number_format((float) $inv->subtotal, 2)."</span>
                        </div>
                        {$discHtml}
                        <div style='display:flex;justify-content:space-between;padding:4px 0;'>
                            <span style='color:#718096;'>ITBIS (18%):</span>
                            <span style='font-weight:bold;'>RD$ ".number_format((float) $inv->tax_total, 2)."</span>
                        </div>
                        {$tipHtml}
                        <div style='display:flex;justify-content:space-between;padding:8px 0;border-top:1px solid #e2e8f0;margin-top:8px;font-size:16px;'>
                            <span style='color:#1a365d;font-weight:bold;'>TOTAL:</span>
                            <span style='color:#1a365d;font-weight:bold;'>RD$ ".number_format((float) $inv->total, 2).'</span>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ';
    }

    private function branchName(Invoice $invoice, string $fallback): string
    {
        return (string) data_get($invoice, 'branch.name', $fallback);
    }

    private function customerName(Invoice $invoice, string $fallback): string
    {
        return (string) data_get($invoice, 'customer.name', $fallback);
    }

    private function customerTaxId(Invoice $invoice, string $fallback): string
    {
        return (string) data_get($invoice, 'customer.tax_id', $fallback);
    }

    private function date(mixed $value): string
    {
        return Carbon::parse($value)->toDateString();
    }

    private function dateTime(mixed $value): string
    {
        return $value === null ? '' : Carbon::parse($value)->toDateTimeString();
    }
}
