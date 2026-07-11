<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Generador PDF mínimo y sin dependencias para reportes tabulares: una o varias
 * páginas A4 con título y una tabla paginada, tipografía Helvetica estándar
 * (WinAnsi). No pretende ser un motor completo; cubre el export "PDF con
 * plantilla" de los reportes sin añadir librerías.
 */
final class PdfTableDocument
{
    private const PAGE_WIDTH = 595.28;   // A4 en puntos

    private const PAGE_HEIGHT = 841.89;

    private const MARGIN = 40.0;

    private const ROW_HEIGHT = 14.0;

    private const FONT_SIZE = 9.0;

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    public static function build(string $title, array $headers, array $rows): string
    {
        $contentWidth = self::PAGE_WIDTH - 2 * self::MARGIN;
        $columnWidth = $contentWidth / max(count($headers), 1);
        $maxChars = (int) max(4, floor($columnWidth / (self::FONT_SIZE * 0.5)));

        $topY = self::PAGE_HEIGHT - self::MARGIN - 24; // debajo del título
        $rowsPerPage = (int) max(1, floor(($topY - self::MARGIN) / self::ROW_HEIGHT) - 1);

        $chunks = array_chunk($rows, $rowsPerPage) ?: [[]];
        $pageContents = [];
        foreach ($chunks as $pageIndex => $pageRows) {
            $pageContents[] = self::pageStream(
                $title.'  ('.($pageIndex + 1).'/'.count($chunks).')',
                $headers,
                $pageRows,
                $columnWidth,
                $maxChars,
                $topY,
            );
        }

        return self::assemble($pageContents);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    private static function pageStream(string $title, array $headers, array $rows, float $columnWidth, int $maxChars, float $topY): string
    {
        $stream = 'BT /F1 14 Tf '.self::pt(self::MARGIN).' '.self::pt(self::PAGE_HEIGHT - self::MARGIN).' Td ('.self::escape($title).") Tj ET\n";

        $y = $topY;
        $stream .= self::rowText($headers, $columnWidth, $maxChars, $y, 10.0);
        $y -= self::ROW_HEIGHT;

        foreach ($rows as $row) {
            $cells = array_map(static fn ($cell): string => (string) $cell, $row);
            $stream .= self::rowText($cells, $columnWidth, $maxChars, $y, self::FONT_SIZE);
            $y -= self::ROW_HEIGHT;
        }

        return $stream;
    }

    /**
     * @param  list<string>  $cells
     */
    private static function rowText(array $cells, float $columnWidth, int $maxChars, float $y, float $size): string
    {
        $out = '';
        foreach ($cells as $index => $cell) {
            $x = self::MARGIN + $index * $columnWidth;
            $text = mb_substr($cell, 0, $maxChars, 'UTF-8');
            $out .= 'BT /F1 '.self::pt($size).' Tf '.self::pt($x).' '.self::pt($y).' Td ('.self::escape($text).") Tj ET\n";
        }

        return $out;
    }

    /** @param list<string> $pageContents */
    private static function assemble(array $pageContents): string
    {
        $objects = [];
        $pageCount = count($pageContents);

        // 1 Catalog, 2 Pages, 3 Font, luego por página: Page y Contents.
        $kids = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = (4 + 2 * $i).' 0 R';
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$pageCount.' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        foreach ($pageContents as $i => $content) {
            $pageObj = 4 + 2 * $i;
            $contentObj = 5 + 2 * $i;
            $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R '
                .'/MediaBox [0 0 '.self::pt(self::PAGE_WIDTH).' '.self::pt(self::PAGE_HEIGHT).'] '
                .'/Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObj.' 0 R >>';
            $objects[$contentObj] = '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream';
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 ".$count."\n0000000000 65535 f \n";
        for ($n = 1; $n < $count; $n++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
        }
        $pdf .= "trailer\n<< /Size ".$count." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    private static function pt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function escape(string $text): string
    {
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        if ($encoded === false) {
            $encoded = $text;
        }

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $encoded);
    }
}
