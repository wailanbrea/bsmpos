<?php

declare(strict_types=1);

namespace App\Core\Support;

use RuntimeException;
use ZipArchive;

/**
 * Escritor mínimo de hojas .xlsx (Office Open XML) sin dependencias externas:
 * arma el paquete OOXML con la extensión `zip` de PHP. Una sola hoja, cadenas
 * en línea y celdas numéricas detectadas automáticamente. Suficiente para los
 * exportes tabulares de reportes (Excel nativo, no CSV renombrado).
 */
final class XlsxWriter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @return string Bytes del archivo .xlsx
     */
    public static function build(array $headers, array $rows, string $sheetName = 'Hoja1'): string
    {
        $sheet = self::sheetXml($headers, $rows);

        $files = [
            '[Content_Types].xml' => self::contentTypes(),
            '_rels/.rels' => self::rootRels(),
            'xl/workbook.xml' => self::workbook($sheetName),
            'xl/_rels/workbook.xml.rels' => self::workbookRels(),
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal para el Excel.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo abrir el paquete .xlsx.');
        }
        foreach ($files as $path => $contents) {
            $zip->addFromString($path, $contents);
        }
        $zip->close();

        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    private static function sheetXml(array $headers, array $rows): string
    {
        $allRows = array_merge([$headers], $rows);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($allRows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="'.$rowNumber.'">';
            foreach ($cells as $colIndex => $value) {
                $ref = self::columnLetter($colIndex).$rowNumber;
                // La fila 0 son encabezados: siempre texto. El resto: numérico si aplica.
                if ($rowIndex > 0 && is_numeric($value)) {
                    $xml .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                } else {
                    $text = htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $xml .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        $name = htmlspecialchars($sheetName, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }
}
