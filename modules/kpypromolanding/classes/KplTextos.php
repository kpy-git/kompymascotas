<?php
/**
 * Los textos que se escriben solos: el título y la explicación de cada banner, las fechas en
 * cristiano («Hasta el 5 de noviembre», «quedan 17 días») y los porcentajes. Todo lo que diga
 * aquí se puede cambiar a mano en el paso 6 de la promoción; esto es lo que sale si no se toca.
 */
class KplTextos
{
    private const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    /** «10» o «12,5». */
    public static function pct(float $porcentaje): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($porcentaje, 1, '.', ''), '0'), '.'));
    }

    /** «5 de noviembre». */
    public static function fecha(?string $fecha): string
    {
        $t = self::ts($fecha);
        if ($t === null) {
            return '';
        }

        return (int) date('j', $t) . ' de ' . self::MESES[(int) date('n', $t) - 1];
    }

    /** «5/11». */
    public static function fechaCorta(?string $fecha): string
    {
        $t = self::ts($fecha);

        return $t === null ? '' : date('j/n', $t);
    }

    /**
     * Lo que se dice de las fechas en un banner: «Hasta el 5 de noviembre», o «Mientras haya stock»
     * si no hay fin.
     */
    public static function hasta(?string $hasta): string
    {
        $f = self::fecha($hasta);

        return $f === '' ? 'Mientras haya stock' : 'Hasta el ' . $f;
    }

    /**
     * La duración de la promoción, para su página: «Del 15 al 31 de octubre · quedan 17 días».
     *
     * @return array{texto: string, quedan: string}
     */
    public static function duracion(?string $desde, ?string $hasta, ?int $ahora = null): array
    {
        $ahora = $ahora ?? time();
        $td = self::ts($desde);
        $th = self::ts($hasta);
        $texto = '';
        if ($td !== null && $th !== null) {
            $texto = date('n', $td) === date('n', $th)
                ? 'Del ' . (int) date('j', $td) . ' al ' . self::fecha($hasta)
                : 'Del ' . self::fecha($desde) . ' al ' . self::fecha($hasta);
        } elseif ($th !== null) {
            $texto = 'Hasta el ' . self::fecha($hasta);
        } elseif ($td !== null) {
            $texto = 'Desde el ' . self::fecha($desde);
        }
        $quedan = '';
        if ($th !== null && $th >= $ahora) {
            $dias = (int) ceil(($th - $ahora) / 86400);
            $quedan = $dias <= 1 ? 'último día' : 'quedan ' . $dias . ' días';
        }

        return ['texto' => $texto, 'quedan' => $quedan];
    }

    /**
     * El título del banner, según el tipo. `$marca` es el nombre de la marca (o '' si no es de una),
     * `$mascota` 'perro', 'gato' o ''.
     *
     * @param array{tipo: string, marca: string, mascota: string, pct: float, codigo: string, tamanos?: string, n?: int} $d
     */
    public static function titulo(array $d): string
    {
        $marca = trim((string) ($d['marca'] ?? ''));
        $mascota = (string) ($d['mascota'] ?? '');
        $pct = self::pct((float) ($d['pct'] ?? 0));
        $para = $mascota !== '' ? ' para ' . KplClaves::mascotaTexto($mascota) : '';
        $tamanos = self::tamanos((string) ($d['tamanos'] ?? ''));
        switch ((string) ($d['tipo'] ?? '')) {
            case 'special_price':
                if ($marca === '') {
                    return 'Special Price: −' . $pct . ' %' . $para;
                }
                if ($tamanos !== '') {
                    return $marca . $para . ': −' . $pct . ' % en ' . $tamanos;
                }

                return $mascota !== ''
                    ? $marca . $para . ': −' . $pct . ' % en toda la gama'
                    : 'Special Price: −' . $pct . ' % en toda la gama ' . $marca;
            case 'cupon':
                $codigo = trim((string) ($d['codigo'] ?? ''));

                return '−' . $pct . ' % en ' . ($marca !== '' ? $marca : 'estos productos') . $para
                    . ($codigo !== '' ? ' con el código ' . $codigo : '');
            case 'segunda_unidad':
                return '2ª unidad al −' . $pct . ' %' . ($marca !== '' ? ' en ' . $marca : '') . $para;
            case 'kilos_gratis':
                if (!empty($d['kpc'])) {
                    // Una promoción de regalo de kpycoupons que regala el saco pequeño de la misma ficha.
                    return $marca !== '' ? $marca . $para . ': kilos GRATIS con tu saco' : 'Kilos GRATIS con tu saco' . $para;
                }

                return $marca !== '' ? $marca . ': sacos con kilos gratis' : 'Kilos gratis: sacos con kilos de regalo';
            case 'regalos':
                if (!empty($d['kpc'])) {
                    return $marca !== '' ? $marca . $para . ': un regalo con tu compra' : 'Un regalo con tu compra' . $para;
                }

                return 'Packs con regalo';
            case 'outlet':
                return 'Outlet y liquidación: últimas unidades';
        }

        return trim($marca . ' ' . (KplClaves::NOMBRE_TIPO[$d['tipo'] ?? ''] ?? ''));
    }

    /** La explicación corta de debajo del título. */
    public static function texto(array $d): string
    {
        $pct = self::pct((float) ($d['pct'] ?? 0));
        switch ((string) ($d['tipo'] ?? '')) {
            case 'special_price':
                return 'El precio ya va rebajado en cada ficha. Sin códigos ni condiciones.';
            case 'cupon':
                return 'Se aplica en la cesta. Pulsa «Aplicar cupón» y queda puesto mientras compras.';
            case 'segunda_unidad':
                return 'Llévate dos y el segundo sale un ' . $pct . ' % más barato. Se aplica solo en la cesta.';
            case 'kilos_gratis':
                if (!empty($d['kpc'])) {
                    $ejemplos = trim((string) ($d['ejemplos'] ?? ''));

                    return $ejemplos !== ''
                        ? $ejemplos . ': el saco pequeño, gratis en tu cesta.'
                        : 'Elige en la ficha la tarjeta «+ kg gratis»: el saco pequeño entra en la cesta a 0 €.';
                }

                return 'Sacos con kilos de regalo: más comida por el mismo precio.';
            case 'regalos':
                if (!empty($d['kpc'])) {
                    return 'Elige en la ficha la tarjeta «+ Regalo»: entra en la cesta a 0 €.';
                }

                return 'Un detalle de regalo dentro del pack.';
            case 'outlet':
                return 'Formatos que se van y liquidaciones puntuales de todas las marcas. Hasta agotar existencias.';
        }

        return '';
    }

    /** «12 kg» o «2,5 kg» del nombre de un formato («Saco de 2.5 Kg»); vacío si no dice kilos. */
    public static function kilos(string $nombre): string
    {
        if (!preg_match('/(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?)\b/iu', $nombre, $m)) {
            return '';
        }

        $numero = str_replace(',', '.', $m[1]);
        // Los ceros de detrás, solo en los decimales: «2.50» → «2,5», «10» se queda en «10».
        if (strpos($numero, '.') !== false) {
            $numero = rtrim(rtrim($numero, '0'), '.');
        }

        return str_replace('.', ',', $numero) . ' kg';
    }

    /** «a, b y c». */
    public static function lista(array $partes): string
    {
        $partes = array_values(array_filter(array_map('trim', $partes), 'strlen'));
        if (count($partes) <= 1) {
            return $partes[0] ?? '';
        }
        $ultima = array_pop($partes);

        return implode(', ', $partes) . ' y ' . $ultima;
    }

    /**
     * «sacos de 8 a 15 kg» a partir de los tamaños de la promoción («saco 12 kg», uno por línea).
     * Vacío si no hay tamaños o son de varios envases.
     */
    public static function tamanos(string $tamanos): string
    {
        $tamanos = trim($tamanos);
        if ($tamanos === '') {
            return '';
        }
        $envases = [];
        $kilos = [];
        foreach (preg_split('/[\r\n,;]+/', $tamanos) ?: [] as $linea) {
            $linea = mb_strtolower(trim($linea));
            if ($linea === '') {
                continue;
            }
            if (preg_match('/^(saco|lata|bolsa|caja|sobre)s?\s+([\d.,]+)\s*(kg|g)/u', $linea, $m)) {
                $envases[$m[1]] = true;
                $valor = (float) str_replace(',', '.', $m[2]);
                $kilos[] = $m[3] === 'g' ? $valor / 1000 : $valor;
            } else {
                return '';
            }
        }
        if (count($envases) !== 1 || $kilos === []) {
            return '';
        }
        $envase = (string) array_key_first($envases);
        $plural = ['saco' => 'sacos', 'lata' => 'latas', 'bolsa' => 'bolsas', 'caja' => 'cajas', 'sobre' => 'sobres'][$envase];
        $min = min($kilos);
        $max = max($kilos);
        $f = static fn (float $k): string => $k >= 1 ? self::pct($k) . ' kg' : (int) round($k * 1000) . ' g';
        if (abs($max - $min) < 0.001) {
            return $plural . ' de ' . $f($min);
        }

        return $plural . ' de ' . self::pct($min) . ' a ' . $f($max);
    }

    /** «16 productos», «1 producto». */
    public static function productos(int $n): string
    {
        return $n === 1 ? '1 producto' : $n . ' productos';
    }

    private static function ts(?string $fecha): ?int
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '' || strpos($fecha, '0000-00-00') === 0) {
            return null;
        }
        $t = strtotime($fecha);

        return $t === false ? null : $t;
    }
}
