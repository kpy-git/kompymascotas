<?php
/**
 * Los descuentos por llevarlos juntos, **por marca**.
 *
 * No es una regla general de la tienda: se decide marca a marca y se enciende y se apaga cuando
 * interesa (muchas veces lo paga la propia marca, y solo durante una campaña). Por eso cada regla
 * lleva su marca, su porcentaje, sus fechas y su interruptor.
 *
 * Reglas:
 *   - manda la de la marca del producto que se está viendo;
 *   - si no hay ninguna suya, vale la regla «todas las marcas» (id_manufacturer = 0), que existe
 *     solo si alguien la ha creado a propósito;
 *   - «solo con productos de la misma marca» viene encendido: un descuento que paga Royal Canin no
 *     se regala al comprar un snack de otro.
 */
class KcsPromo
{
    /**
     * La regla que le toca a una marca, o `null` si esa marca no tiene descuento ahora mismo.
     *
     * @return array{id_promo: int, id_manufacturer: int, porcentaje: float, misma_marca: bool, nombre: string}|null
     */
    public static function deLaMarca(int $idMarca): ?array
    {
        foreach (self::enVigor() as $regla) {
            if ((int) $regla['id_manufacturer'] === $idMarca) {
                return $regla;
            }
        }
        foreach (self::enVigor() as $regla) {
            if ((int) $regla['id_manufacturer'] === 0) {
                return $regla;
            }
        }

        return null;
    }

    /** Todas las reglas encendidas y en fecha, la de cada marca antes que la general. */
    public static function enVigor(): array
    {
        static $reglas = null;
        if ($reglas !== null) {
            return $reglas;
        }
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_cross_sell_promo
             WHERE activo = 1 AND porcentaje > 0
               AND (fecha_desde IS NULL OR fecha_desde = "0000-00-00" OR fecha_desde <= CURDATE())
               AND (fecha_hasta IS NULL OR fecha_hasta = "0000-00-00" OR fecha_hasta >= CURDATE())
             ORDER BY (id_manufacturer = 0), id_manufacturer'
        ) ?: [];

        $reglas = [];
        foreach ($filas as $f) {
            $hasta = (string) ($f['fecha_hasta'] ?? '');
            $reglas[] = [
                'id_promo' => (int) $f['id_promo'],
                'id_manufacturer' => (int) $f['id_manufacturer'],
                'porcentaje' => (float) $f['porcentaje'],
                'misma_marca' => (bool) $f['misma_marca'],
                'nombre' => (string) $f['nombre'],
                // Hasta el último segundo de su último día; sin fecha de fin, null.
                'hasta' => $hasta !== '' && strpos($hasta, '0000') !== 0 ? substr($hasta, 0, 10) . ' 23:59:59' : null,
            ];
        }

        return $reglas;
    }

    /** Todas, encendidas o no, para la pantalla del módulo. */
    public static function todas(): array
    {
        return Db::getInstance()->executeS(
            'SELECT p.*, m.name AS marca
             FROM ' . _DB_PREFIX_ . 'kpy_cross_sell_promo p
             LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON m.id_manufacturer = p.id_manufacturer
             ORDER BY p.activo DESC, (p.id_manufacturer = 0), m.name'
        ) ?: [];
    }

    /**
     * ¿Le vale esta regla a esta pareja? Con «solo misma marca», las dos tienen que ser de ella.
     */
    public static function valeLaPareja(array $regla, int $marcaPrincipal, int $marcaCompanero): bool
    {
        if (!$regla['misma_marca']) {
            return true;
        }

        return $marcaPrincipal > 0 && $marcaPrincipal === $marcaCompanero;
    }

    public static function guardar(array $datos): bool
    {
        $id = (int) ($datos['id_promo'] ?? 0);
        $campos = [
            'id_manufacturer' => (int) ($datos['id_manufacturer'] ?? 0),
            'porcentaje' => (float) ($datos['porcentaje'] ?? 0),
            'misma_marca' => (int) (bool) ($datos['misma_marca'] ?? 1),
            'activo' => (int) (bool) ($datos['activo'] ?? 1),
            'fecha_desde' => ($datos['fecha_desde'] ?? '') ?: null,
            'fecha_hasta' => ($datos['fecha_hasta'] ?? '') ?: null,
            'nombre' => pSQL((string) ($datos['nombre'] ?? '')),
        ];

        return $id > 0
            ? Db::getInstance()->update('kpy_cross_sell_promo', $campos, 'id_promo = ' . $id)
            : Db::getInstance()->insert('kpy_cross_sell_promo', $campos);
    }

    public static function borrar(int $id): bool
    {
        return Db::getInstance()->delete('kpy_cross_sell_promo', 'id_promo = ' . (int) $id);
    }

    public static function cambiar(int $id): bool
    {
        return Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . 'kpy_cross_sell_promo SET activo = 1 - activo WHERE id_promo = ' . (int) $id
        );
    }
}
