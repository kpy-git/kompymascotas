<?php
/**
 * Un libro de Excel (.xlsx) sin librerías: el formato es un zip con unos XML, y PHP trae
 * ZipArchive. Textos, enteros y dinero (con dos decimales); cabecera en negrita; anchos de
 * columna. Si no hubiera ZipArchive, un CSV que Excel abre bien (UTF-8 con BOM y punto y coma).
 * Puro: se prueba con `tests/excel.php`.
 */
class KsExcel
{
    public const TIPO_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    public const TIPO_CSV = 'text/csv; charset=UTF-8';

    /**
     * @param array<int, array{nombre: string, cabecera: string[], filas: array<int, array<int, mixed>>, anchos?: int[]}> $hojas
     *                                                                                                                      cada celda: string (texto), int (entero), float (dinero, dos decimales) o null (vacía)
     *
     * @return string|null el fichero, o null si no hay ZipArchive
     */
    public static function libro(array $hojas): ?string
    {
        if (!class_exists('ZipArchive') || $hojas === []) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'kps');
        if ($tmp === false) {
            return null;
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);

            return null;
        }
        $hojas = array_values($hojas);
        $n = count($hojas);
        $tipos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $libro = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $relaciones = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $n; ++$i) {
            $tipos .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $libro .= '<sheet name="' . self::xml(self::nombreHoja((string) ($hojas[$i - 1]['nombre'] ?? ('Hoja ' . $i)), $i)) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $relaciones .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $i . '.xml', self::hoja($hojas[$i - 1]));
        }
        $tipos .= '</Types>';
        $libro .= '</sheets></workbook>';
        $relaciones .= '<Relationship Id="rIdEstilos" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('[Content_Types].xml', $tipos);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $libro);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relaciones);
        $zip->addFromString('xl/styles.xml', self::estilos());
        $zip->close();
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes !== '' ? $bytes : null;
    }

    /** La misma hoja como CSV para Excel en español: UTF-8 con BOM, punto y coma, coma decimal. */
    public static function csv(array $hoja): string
    {
        $lineas = [self::lineaCsv((array) ($hoja['cabecera'] ?? []))];
        foreach ((array) ($hoja['filas'] ?? []) as $fila) {
            $lineas[] = self::lineaCsv((array) $fila);
        }

        return "\xEF\xBB\xBF" . implode("\r\n", $lineas) . "\r\n";
    }

    private static function lineaCsv(array $fila): string
    {
        $celdas = [];
        foreach ($fila as $v) {
            if ($v === null) {
                $celdas[] = '';
            } elseif (is_int($v)) {
                $celdas[] = (string) $v;
            } elseif (is_float($v)) {
                $celdas[] = number_format($v, 2, ',', '');
            } else {
                $t = (string) $v;
                // Que Excel no tome un texto que empieza por =, +, - o @ por una fórmula al abrir el CSV.
                if ($t !== '' && in_array($t[0], ['=', '+', '-', '@'], true)) {
                    $t = ' ' . $t;
                }
                $celdas[] = '"' . str_replace('"', '""', $t) . '"';
            }
        }

        return implode(';', $celdas);
    }

    /** Un nombre de hoja válido: hasta 31 caracteres y sin los que Excel no admite. */
    public static function nombreHoja(string $nombre, int $i = 1): string
    {
        $nombre = str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $nombre);
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? $nombre);
        $nombre = trim(mb_substr($nombre, 0, 31));

        return $nombre !== '' ? $nombre : 'Hoja ' . $i;
    }

    private static function hoja(array $h): string
    {
        $cabecera = array_values((array) ($h['cabecera'] ?? []));
        $filas = (array) ($h['filas'] ?? []);
        $anchos = (array) ($h['anchos'] ?? []);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $columnas = count($cabecera);
        foreach ($filas as $fila) {
            $columnas = max($columnas, count((array) $fila));
        }
        if ($columnas > 0) {
            $xml .= '<cols>';
            for ($c = 1; $c <= $columnas; ++$c) {
                $ancho = (int) ($anchos[$c - 1] ?? 0);
                if ($ancho <= 0) {
                    $ancho = max(10, min(60, mb_strlen((string) ($cabecera[$c - 1] ?? '')) + 4));
                }
                $xml .= '<col min="' . $c . '" max="' . $c . '" width="' . $ancho . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        $r = 1;
        if ($cabecera !== []) {
            $xml .= '<row r="1">';
            foreach ($cabecera as $c => $texto) {
                $xml .= '<c r="' . self::col($c + 1) . '1" t="inlineStr" s="1"><is><t' . self::espacios((string) $texto) . '>' . self::xml((string) $texto) . '</t></is></c>';
            }
            $xml .= '</row>';
            $r = 2;
        }
        foreach ($filas as $fila) {
            $xml .= '<row r="' . $r . '">';
            foreach (array_values((array) $fila) as $c => $v) {
                $xml .= self::celda(self::col($c + 1) . $r, $v);
            }
            $xml .= '</row>';
            ++$r;
        }
        $xml .= '</sheetData>';
        if ($cabecera !== [] && $columnas > 0) {
            $xml .= '<autoFilter ref="A1:' . self::col($columnas) . max(1, $r - 1) . '"/>';
        }

        return $xml . '</worksheet>';
    }

    private static function celda(string $ref, $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if (is_int($v)) {
            return '<c r="' . $ref . '"><v>' . $v . '</v></c>';
        }
        if (is_float($v)) {
            return '<c r="' . $ref . '" s="2"><v>' . self::numero($v) . '</v></c>';
        }
        $t = (string) $v;

        return '<c r="' . $ref . '" t="inlineStr"><is><t' . self::espacios($t) . '>' . self::xml($t) . '</t></is></c>';
    }

    private static function numero(float $v): string
    {
        $t = rtrim(rtrim(sprintf('%.6F', $v), '0'), '.');

        return $t === '' || $t === '-0' ? '0' : $t;
    }

    private static function espacios(string $t): string
    {
        return $t !== trim($t) ? ' xml:space="preserve"' : '';
    }

    /** A, B, …, Z, AA, AB… */
    public static function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = (int) (($n - 1) / 26);
        }

        return $s;
    }

    private static function xml(string $t): string
    {
        // Lo que XML no admite (caracteres de control) se quita; lo demás se escapa.
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $t) ?? $t;

        return htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Tres estilos: normal (0), cabecera en negrita (1) y dinero con dos decimales (2). */
    private static function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
