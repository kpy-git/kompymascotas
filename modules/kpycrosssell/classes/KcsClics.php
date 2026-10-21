<?php
/**
 * Qué se enseña y qué se lleva la gente.
 *
 * Es lo mismo que contaba el panel de siempre con sus columnas de clics, pero automático: cada vez
 * que el bloque sale se apunta una vista, y cada vez que alguien añade una sugerencia se apunta un
 * clic. Con eso se ve de un vistazo qué parejas funcionan y cuáles hay que quitar.
 *
 * No guarda nada del cliente: solo el producto que se miraba, el que se ofrecía y dos contadores.
 */
class KcsClics
{
    /** Se ha enseñado el bloque con estas sugerencias. @param int[] $sugeridos */
    public static function vistas(int $idRef, array $sugeridos): void
    {
        $ids = array_values(array_filter(array_map('intval', $sugeridos)));
        if ($idRef <= 0 || $ids === []) {
            return;
        }
        $idTienda = (int) Context::getContext()->shop->id;
        $valores = [];
        foreach ($ids as $id) {
            $valores[] = '(' . $idRef . ', ' . $id . ', ' . $idTienda . ', 1, 0, NOW())';
        }
        // Si falla (tabla que todavía no existe en una tienda a medio actualizar), el bloque sigue.
        try {
            Db::getInstance()->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'kpy_cross_sell_clic (id_ref, id_sugerido, id_shop, vistas, clics, ultimo)
                 VALUES ' . implode(', ', $valores) . '
                 ON DUPLICATE KEY UPDATE vistas = vistas + 1, ultimo = NOW()'
            );
        } catch (Throwable $e) {
            // Contar no es tan importante como vender.
        }
    }

    /** Alguien se ha llevado una de las sugerencias. */
    public static function clic(int $idRef, int $idSugerido): void
    {
        if ($idRef <= 0 || $idSugerido <= 0) {
            return;
        }
        $idTienda = (int) Context::getContext()->shop->id;
        try {
            Db::getInstance()->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'kpy_cross_sell_clic (id_ref, id_sugerido, id_shop, vistas, clics, ultimo)
                 VALUES (' . $idRef . ', ' . $idSugerido . ', ' . $idTienda . ', 0, 1, NOW())
                 ON DUPLICATE KEY UPDATE clics = clics + 1, ultimo = NOW()'
            );
        } catch (Throwable $e) {
            // Igual: contar no puede impedir una venta.
        }
    }
}
