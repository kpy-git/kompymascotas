<?php
/**
 * Lo que pasa con las alternativas de la cesta: cuántas veces se enseña el bloque, cuántas se cambia un
 * producto, cuántas se deja como está, cuántas se abre «ver más» o la (i). Sin datos del cliente: solo
 * la cesta, la línea, el almacén de referencia y el producto sugerido, si lo hay.
 *
 * Es también la memoria de «Dejarlo como está»: si el cliente lo pulsó para una línea, el bloque no
 * vuelve a salirle en esa cesta (la etiqueta de aviso sí).
 *
 * Contar no puede impedir una venta: si la tabla falla, el bloque sigue.
 */
class KcsCestaLog
{
    public const TABLA = 'kpy_cross_sell_cesta';

    public const EVENTOS = ['vista', 'cambio', 'dejar', 'vermas', 'info', 'ventana', 'error'];

    /** @var array<string, bool> lo ya apuntado en esta petición («evento|cesta|línea»), para no contar dos veces */
    private static array $apuntado = [];

    public static function crearTabla(): bool
    {
        $p = _DB_PREFIX_;
        $motor = _MYSQL_ENGINE_;

        return (bool) Db::getInstance()->execute(
            "CREATE TABLE IF NOT EXISTS {$p}" . self::TABLA . " (
                id_log INT UNSIGNED NOT NULL AUTO_INCREMENT,
                id_shop INT UNSIGNED NOT NULL DEFAULT 1,
                id_cart INT UNSIGNED NOT NULL,
                id_product INT UNSIGNED NOT NULL DEFAULT 0,
                id_product_attribute INT UNSIGNED NOT NULL DEFAULT 0,
                almacen_ref VARCHAR(20) NOT NULL DEFAULT '',
                evento VARCHAR(12) NOT NULL,
                id_sugerido INT UNSIGNED NOT NULL DEFAULT 0,
                fecha DATETIME NOT NULL,
                PRIMARY KEY (id_log),
                KEY cesta (id_cart, id_product, id_product_attribute, evento),
                KEY fecha (fecha, evento)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        );
    }

    /** Apunta un evento. Las vistas se apuntan una vez por línea y petición. */
    public static function apuntar(int $idCesta, string $evento, int $idProducto = 0, int $ipa = 0, string $almacenRef = '', int $idSugerido = 0): void
    {
        if ($idCesta <= 0 || !in_array($evento, self::EVENTOS, true)) {
            return;
        }
        $clave = $evento . '|' . $idCesta . '|' . $idProducto . '-' . $ipa . '|' . $idSugerido;
        if (isset(self::$apuntado[$clave])) {
            return;
        }
        self::$apuntado[$clave] = true;
        try {
            // De vez en cuando (una de cada quinientas veces), fuera lo de hace más de un año.
            if (mt_rand(1, 500) === 1) {
                self::limpiarViejos();
            }
            Db::getInstance()->insert(self::TABLA, [
                'id_shop' => (int) Context::getContext()->shop->id,
                'id_cart' => $idCesta,
                'id_product' => $idProducto,
                'id_product_attribute' => $ipa,
                'almacen_ref' => pSQL(mb_substr($almacenRef, 0, 20)),
                'evento' => pSQL($evento),
                'id_sugerido' => $idSugerido,
                'fecha' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Contar no es tan importante como vender.
        }
    }

    /** ¿Pulsó el cliente «Dejarlo como está» para esta línea de esta cesta? */
    public static function dejada(int $idCesta, int $idProducto, int $ipa): bool
    {
        if ($idCesta <= 0) {
            return false;
        }
        try {
            return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT 1 FROM ' . _DB_PREFIX_ . self::TABLA . "
                 WHERE id_cart = {$idCesta} AND id_product = {$idProducto} AND id_product_attribute = {$ipa} AND evento = 'dejar'"
            );
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * El resumen para la pantalla del módulo: por día y evento, los últimos N días.
     *
     * @return array<int, array{dia: string, evento: string, n: int, cestas: int}>
     */
    public static function resumen(int $dias = 30): array
    {
        try {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT DATE(fecha) AS dia, evento, COUNT(*) AS n, COUNT(DISTINCT id_cart) AS cestas
                 FROM ' . _DB_PREFIX_ . self::TABLA . '
                 WHERE fecha >= DATE_SUB(NOW(), INTERVAL ' . max(1, $dias) . ' DAY)
                 GROUP BY DATE(fecha), evento
                 ORDER BY dia DESC, evento'
            ) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Los cambios hechos, con el producto que salió y el que entró: para ver qué funciona.
     *
     * @return array<int, array{fecha: string, id_cart: int, id_product: int, id_sugerido: int, almacen_ref: string, salio: string, entro: string}>
     */
    public static function cambios(int $dias = 30, int $cuantos = 50): array
    {
        $p = _DB_PREFIX_;
        $idLang = (int) Context::getContext()->language->id;
        try {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                "SELECT l.fecha, l.id_cart, l.id_product, l.id_sugerido, l.almacen_ref, s.name AS salio, e.name AS entro
                 FROM {$p}" . self::TABLA . " l
                 LEFT JOIN {$p}product_lang s ON s.id_product = l.id_product AND s.id_lang = {$idLang}
                 LEFT JOIN {$p}product_lang e ON e.id_product = l.id_sugerido AND e.id_lang = {$idLang}
                 WHERE l.evento = 'cambio' AND l.fecha >= DATE_SUB(NOW(), INTERVAL " . max(1, $dias) . ' DAY)
                 ORDER BY l.fecha DESC LIMIT ' . max(1, $cuantos)
            ) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Lo viejo se barre: un año es más que suficiente para comparar antes y después. */
    public static function limpiarViejos(int $dias = 365): int
    {
        try {
            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . self::TABLA . ' WHERE fecha < DATE_SUB(NOW(), INTERVAL ' . max(30, $dias) . ' DAY)');

            return (int) Db::getInstance()->Affected_Rows();
        } catch (Throwable $e) {
            return 0;
        }
    }
}
