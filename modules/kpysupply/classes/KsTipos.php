<?php
/**
 * Los tres tipos de acuerdo con el proveedor, con sus nombres en la tabla `kpy_supplier_promo_type`
 * tal como los pidió Julián. En el código se usan claves cortas (`redencion`, `factura`, `coste`);
 * los ids de la tabla se leen una vez por petición.
 */
class KsTipos
{
    public const REDENCION = 'redencion';
    public const FACTURA = 'factura';
    public const COSTE = 'coste';

    /** Clave => nombre en la tabla (los valores que pidió Julián, letra por letra). */
    public const NOMBRES = [
        self::REDENCION => 'redención',
        self::FACTURA => 'Dto en Factura',
        self::COSTE => 'Precio de coste actualizado',
    ];

    /** Lo que se le explica a Rafa en las pantallas. */
    public const TEXTOS = [
        self::REDENCION => [
            'titulo' => 'Redención',
            'valor' => '% sobre las ventas sin IVA',
            'corto' => 'Al acabar, el proveedor paga un % de lo vendido sin IVA',
            'explica' => 'Al acabar la promoción se le pasa al proveedor la lista de lo vendido (unidades y valor sin IVA, artículo por artículo); paga ese porcentaje y se le factura con IVA. Las ventas salen de aquí mismo («Ventas del periodo», con su Excel).',
        ],
        self::FACTURA => [
            'titulo' => 'Descuento en factura',
            'valor' => '% sobre el precio de coste neto',
            'corto' => 'Mientras dura, el proveedor factura con ese % de descuento adicional',
            'explica' => 'Un descuento adicional sobre el precio de coste neto de la unidad de venta durante el periodo (puede ser distinto por gama dentro de la misma marca). Administración contrasta las facturas con esta lista («Lista a Excel»).',
        ],
        self::COSTE => [
            'titulo' => 'Precio de coste actualizado',
            'valor' => '€ por unidad de venta, sin IVA',
            'corto' => 'El proveedor manda precios de coste nuevos por EAN, para un periodo o hasta nueva orden',
            'explica' => 'En vez de un %, el proveedor manda la lista con el precio de coste nuevo de cada artículo (sin IVA, por unidad de venta), para un periodo o «hasta nueva orden». Se sube pegando la lista con el precio al lado de cada EAN.',
        ],
    ];

    /** @var array<string, int>|null clave => id en la tabla (caché por petición) */
    private static ?array $ids = null;

    /** @return string[] las claves, en el orden de las pantallas */
    public static function claves(): array
    {
        return array_keys(self::NOMBRES);
    }

    /** Cómo se lee el valor de ese tipo: un porcentaje o un precio. */
    public static function como(string $clave): string
    {
        return $clave === self::COSTE ? 'precio' : 'pct';
    }

    public static function titulo(string $clave): string
    {
        return self::TEXTOS[$clave]['titulo'] ?? $clave;
    }

    /** «10 %» o «12,34 €», según el tipo. */
    public static function valorTexto(string $clave, float $valor): string
    {
        return self::como($clave) === 'precio' ? self::numero($valor) . ' €' : self::numero($valor) . ' %';
    }

    /** Un número como se escribe aquí: «12,5», «10», «0,075». */
    public static function numero(float $x): string
    {
        $t = rtrim(rtrim(number_format($x, 6, ',', '.'), '0'), ',');

        return $t === '' || $t === '-0' ? '0' : $t;
    }

    /** Los ids de la tabla por clave (0 si falta: `crear()` lo arregla). */
    public static function ids(): array
    {
        if (self::$ids !== null) {
            return self::$ids;
        }
        $porNombre = [];
        foreach (Db::getInstance()->executeS('SELECT id, nombre FROM ' . _DB_PREFIX_ . 'kpy_supplier_promo_type') ?: [] as $f) {
            $porNombre[mb_strtolower(trim((string) $f['nombre']))] = (int) $f['id'];
        }
        $salida = [];
        foreach (self::NOMBRES as $clave => $nombre) {
            $salida[$clave] = $porNombre[mb_strtolower($nombre)] ?? 0;
        }

        return self::$ids = $salida;
    }

    public static function id(string $clave): int
    {
        return (int) (self::ids()[$clave] ?? 0);
    }

    /** La clave de un id de la tabla ('' si no es ninguno de los tres). */
    public static function clave(int $id): string
    {
        foreach (self::ids() as $clave => $suyo) {
            if ($suyo === $id && $id > 0) {
                return (string) $clave;
            }
        }

        return '';
    }

    /** Deja los tres tipos en la tabla (los que falten; los que estén, se respetan). */
    public static function crear(): void
    {
        $db = Db::getInstance();
        foreach (self::NOMBRES as $nombre) {
            $db->execute('INSERT IGNORE INTO ' . _DB_PREFIX_ . "kpy_supplier_promo_type (nombre) VALUES ('" . pSQL($nombre) . "')");
        }
        self::$ids = null;
    }

    public static function olvidar(): void
    {
        self::$ids = null;
    }
}
