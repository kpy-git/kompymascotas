<?php
/**
 * Lee el nombre de un formato («Saco de 15 Kg», «Pack 2 x Saco de 12 Kg», «Pack 24 Latas 85 gr»,
 * «Saco de 12 + 2 Kg», «Saco de 12 Kg - OUTLET») y saca:
 *   - `corto`: el nombre que cabe en una tarjeta («15 kg», «2 x 12 kg», «Lata 400 g»);
 *   - `gramos` (o mililitros) en total, para el precio por kilo (o litro);
 *   - `unidades` y `envase`, si se vende por latas, sobres…, para el precio por lata.
 *
 * Si un nombre no se entiende, `corto` es el nombre tal cual y no hay precio por kilo: nunca
 * falla. No depende de PrestaShop. Es el lector que tenía `kpyproductbuy` (`KpbFormato`), que se
 * retira el 23/10/2026: la ficha la pinta ahora este módulo (`KpcFicha`).
 */
class KpcEnvase
{
    private const ENVASES = 'latas?|sobres?|tarrinas?|bolsas?|bolsitas?|sacos?|botes?|barritas?|sticks?|bandejas?|pouch(?:es)?|terrinas?';

    private const PLURAL = ['lata' => 'latas', 'sobre' => 'sobres', 'tarrina' => 'tarrinas', 'bolsa' => 'bolsas',
        'saco' => 'sacos', 'bote' => 'botes', 'barrita' => 'barritas', 'stick' => 'sticks', 'bandeja' => 'bandejas',
        'pouch' => 'pouches', 'terrina' => 'terrinas'];

    /**
     * @param float $pesoKg el peso que PrestaShop guarda en la combinación (`product_attribute.
     *              weight`). Cuando lo hay, es el que manda para el precio por kilo: es el dato del
     *              catálogo, no una lectura del texto. El nombre solo se mira para lo que no está
     *              en ninguna tabla: cuántas unidades trae el pack y en qué envase vienen.
     *
     * @return array{nombre: string, corto: string, gramos: ?float, litros: bool, unidades: ?int, envase: string, pack: int}
     */
    public static function leer(string $nombre, float $pesoKg = 0.0): array
    {
        $limpio = trim(preg_replace('/\s+/u', ' ', $nombre));
        $salida = ['nombre' => $limpio, 'corto' => $limpio, 'gramos' => null, 'litros' => false, 'unidades' => null, 'envase' => '', 'pack' => 1];
        if ($limpio === '') {
            return $salida;
        }

        // Coletilla: «- OUTLET» → «· Outlet».
        $coletilla = '';
        if (preg_match('/^(.*\d.*?)\s+-\s+([^\d]{2,20})$/u', $limpio, $m)) {
            $limpio = trim($m[1]);
            $coletilla = ' · ' . mb_convert_case(mb_strtolower(trim($m[2])), MB_CASE_TITLE);
        }

        $n = 1;
        $resto = $limpio;
        if (preg_match('/^pack\s+(?:de\s+)?(\d+)\s*(?:[x×]\s*)?(.+)$/iu', $limpio, $m)) {
            $n = max(1, (int) $m[1]);
            $resto = trim($m[2]);
        }

        // Cantidad de cada unidad, con regalo sumado: «12 + 2 Kg» = 14 kg.
        $gramosUno = null;
        $cantidad = '';
        $litros = false;
        // «de 2,5 a 5,5 Kg», «Pipeta más de 25 Kg», «S - 5 a 10 kg»: es el peso del animal, no lo
        // que pesa el producto. Sin peso, no hay precio por kilo.
        $esRango = preg_match('/\d\s*(?:a|-|–|hasta)\s*\d|\b(?:más|mas|menos)\s+de\b|\bhasta\b|[<>]/iu', $resto);
        if (!$esRango && preg_match('/(\d+(?:[.,]\d+)?)(?:\s*\+\s*(\d+(?:[.,]\d+)?))?\s*(kg|kilos?|g|gr|grs|gramos|ml|l|litros?)(?![a-záéíóú])\.?/iu', $resto, $m)) {
            $uno = (float) str_replace(',', '.', $m[1]);
            $mas = isset($m[2]) && $m[2] !== '' ? (float) str_replace(',', '.', $m[2]) : 0.0;
            $unidad = mb_strtolower($m[3]);
            $kilos = in_array($unidad, ['kg', 'kilo', 'kilos', 'l', 'litro', 'litros'], true);
            $litros = in_array($unidad, ['ml', 'l', 'litro', 'litros'], true);
            $gramosUno = ($uno + $mas) * ($kilos ? 1000 : 1);
            $sufijo = $litros ? ($kilos ? ' l' : ' ml') : ($kilos ? ' kg' : ' g');
            $cantidad = self::numero($uno) . ($mas > 0 ? ' + ' . self::numero($mas) : '') . $sufijo;
        }

        $envase = '';
        if (preg_match('/\b(' . self::ENVASES . ')\b/iu', $resto, $m)) {
            $envase = mb_strtolower($m[1]);
            $singular = array_search($envase, self::PLURAL, true);
            $envase = $singular !== false ? $singular : $envase;
            $envase = in_array($envase, ['bolsita', 'bolsitas'], true) ? 'bolsa' : $envase;
        }

        // Se vende por unidades si el envase se da entero (latas, sobres…), no un saco. En Kompy
        // «bolsa» es también el sobre de húmedo: hasta 200 g es un sobre; más, una bolsa de pienso.
        $porUnidades = $envase !== '' && $envase !== 'saco'
            && ($envase !== 'bolsa' || ($gramosUno !== null && $gramosUno <= 200));

        $corto = $limpio;
        // Solo se acorta lo que se entiende del todo; si no, el nombre de siempre.
        $entendido = $cantidad !== '' && preg_match('/^(?:(?:' . self::ENVASES . ')\s+)?(?:de\s+)?\d+(?:[.,]\d+)?(?:\s*\+\s*\d+(?:[.,]\d+)?)?\s*(?:kg|kilos?|g|gr|grs|gramos|ml|l|litros?)\.?$/iu', $resto);
        if ($entendido) {
            // Como Zooplus y Tiendanimal: el peso («12 kg», «2 x 12 kg», «24 x 85 g»). El envase
            // ya lo dicen el €/lata de la tarjeta y el nombre completo de encima; suelto, sí:
            // «Lata 400 g».
            if ($n > 1) {
                $corto = $n . ' x ' . $cantidad;
            } elseif ($envase !== '' && $porUnidades) {
                $corto = mb_strtoupper(mb_substr($envase, 0, 1)) . mb_substr($envase, 1) . ' ' . $cantidad;
            } else {
                $corto = $cantidad;
            }
        }

        // El peso del catálogo manda sobre lo leído en el texto: es el mismo que usa PrestaShop
        // para el envío, y con él el €/kg sale bien aunque el nombre esté puesto de otra manera.
        $gramosTotal = $pesoKg > 0 ? $pesoKg * 1000 : ($gramosUno !== null ? $gramosUno * $n : null);

        return [
            'nombre' => trim($salida['nombre']),
            'corto' => $corto . $coletilla,
            'gramos' => $litros && $pesoKg > 0 ? ($gramosUno !== null ? $gramosUno * $n : null) : $gramosTotal,
            'litros' => $litros,
            'unidades' => $porUnidades ? $n : null,
            'envase' => $porUnidades ? $envase : '',
            'pack' => $n,
        ];
    }

    private static function numero(float $n): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.'));
    }
}
