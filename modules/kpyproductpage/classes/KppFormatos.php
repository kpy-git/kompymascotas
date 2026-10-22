<?php
/**
 * Lee el nombre de cada combinación («Saco de 15 Kg», «Pack 2 x 15 Kg», «Pack 24 x Lata de 85
 * gr») y saca cuánto pesa en total y cuántas unidades lleva. Con eso la ración diaria puede decir
 * «el saco de 15 kg le dura unos 45 días» o «el pack de 24 latas, unos 5 días».
 *
 * Si un nombre no se entiende, ese formato sale sin peso ni unidades y la ficha simplemente no
 * calcula su duración. No depende de PrestaShop.
 */
class KppFormatos
{
    private const ENVASES = 'latas?|sobres?|tarrinas?|bolsas?|bolsitas?|sacos?|botes?|barritas?|sticks?|bandejas?';

    /**
     * @param array<int, array{id: int, nombre: string}> $combinaciones
     *
     * @return array<int, array{id: int, nombre: string, corto: string, gramos: ?float, unidades: ?int, envase: string}>
     */
    public static function leer(array $combinaciones): array
    {
        $salida = [];
        foreach ($combinaciones as $c) {
            $salida[] = ['id' => (int) $c['id']] + self::uno((string) $c['nombre']);
        }

        return $salida;
    }

    /** @return array{nombre: string, corto: string, gramos: ?float, unidades: ?int, envase: string} */
    public static function uno(string $nombre): array
    {
        $limpio = trim(preg_replace('/\s+/u', ' ', $nombre));
        $n = 1;
        $resto = $limpio;
        if (preg_match('/^pack\s+(\d+)\s*[x×]\s*(.+)$/iu', $limpio, $m)) {
            $n = max(1, (int) $m[1]);
            $resto = $m[2];
        }

        $gramosUno = null;
        $cantidad = '';
        // «de 2,5 a 5,5 Kg», «Pipeta más de 25 Kg»: es el peso del animal, no el del producto.
        $esRango = preg_match('/\d\s*(?:a|-|–|hasta)\s*\d|\b(?:más|mas|menos)\s+de\b|\bhasta\b|[<>]/iu', $resto);
        if (!$esRango && preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|kilos?|g|gr|grs|gramos)\b/iu', $resto, $m)) {
            $num = (float) str_replace(',', '.', $m[1]);
            $kilos = in_array(mb_strtolower($m[2]), ['kg', 'kilo', 'kilos'], true);
            $gramosUno = $kilos ? $num * 1000 : $num;
            $cantidad = str_replace('.', ',', rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.')) . ($kilos ? ' kg' : ' g');
        }

        $envase = '';
        if (preg_match('/\b(' . self::ENVASES . ')\b/iu', $resto, $m)) {
            $envase = rtrim(mb_strtolower($m[1]), 's');
            $envase = $envase === 'bolsita' ? 'bolsa' : $envase;
        }

        // Las unidades solo cuentan si el envase se da entero (latas, sobres…), no un saco. En Kompy
        // «Bolsa» es también el sobre de húmedo: hasta 200 g es un sobre; más, una bolsa de pienso.
        $porUnidades = $envase !== '' && $envase !== 'saco'
            && ($envase !== 'bolsa' || ($gramosUno !== null && $gramosUno <= 200));

        // Pienso, solo el peso («12 kg», «2 x 12 kg»), como en la parte de arriba de la ficha.
        if ($n > 1) {
            $corto = ($porUnidades ? 'Pack ' : '') . $n . ' x ' . ($cantidad !== '' ? $cantidad : $resto);
        } elseif ($envase !== '' && $cantidad !== '') {
            $corto = $porUnidades ? mb_strtoupper(mb_substr($envase, 0, 1)) . mb_substr($envase, 1) . ' ' . $cantidad : $cantidad;
        } else {
            $corto = $limpio;
        }

        return [
            'nombre' => $limpio,
            'corto' => $corto,
            'gramos' => $gramosUno !== null ? $gramosUno * $n : null,
            'unidades' => $porUnidades ? $n : null,
            'envase' => $envase,
        ];
    }
}
