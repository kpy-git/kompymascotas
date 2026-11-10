<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyGoogleFeedModifier\Etiquetas;

use Configuration;
use Db;

/**
 * La lista de etiquetas de campaña (custom_label_0) que manda el Etiquetador de Google Ads cada mañana:
 * se comprueba entera, se cuenta y se sustituye la tabla de una vez.
 *
 * La tabla `kpy_google_feed_custom_labels` la lee el generador del feed de Merchant, que corre fuera de
 * PrestaShop, así que su estructura no se toca. La sustitución se hace en una tabla gemela que se intercambia
 * con RENAME TABLE: quien lea en ese momento ve la lista vieja o la nueva, nunca una a medias ni vacía.
 */
final class Importador
{
    public const TABLA = 'kpy_google_feed_custom_labels';
    public const MINIMO_FILAS = 5000;
    public const MAXIMO_ORIGEN = 100;

    public const CLAVE_AJUSTE = 'KGFM_CLAVE';
    public const ULTIMA_FECHA = 'KGFM_ULTIMA_FECHA';
    public const ULTIMA_ORIGEN = 'KGFM_ULTIMA_ORIGEN';
    public const ULTIMA_FILAS = 'KGFM_ULTIMA_FILAS';

    /**
     * Comprueba el cuerpo recibido. Devuelve origen, probar y las filas ya partidas en
     * [id_product, id_product_attribute, etiqueta]. Lanza EtiquetasException (400/422) si algo no vale.
     *
     * @param mixed $cuerpo lo que venga de json_decode
     *
     * @return array{origen: string, probar: bool, filas: array<int, array{0: int, 1: int, 2: string}>}
     */
    public static function validar($cuerpo): array
    {
        if (!is_array($cuerpo)) {
            throw new EtiquetasException('El cuerpo no es un objeto JSON.', 400);
        }
        $origen = isset($cuerpo['origen']) && is_string($cuerpo['origen']) ? trim($cuerpo['origen']) : '';
        $origen = mb_substr(preg_replace('~[\x00-\x1F\x7F]~u', '', $origen) ?? '', 0, self::MAXIMO_ORIGEN);

        $probar = false;
        if (array_key_exists('probar', $cuerpo)) {
            $valor = $cuerpo['probar'];
            if (!is_bool($valor) && !in_array($valor, [0, 1, '0', '1', 'true', 'false'], true)) {
                throw new EtiquetasException('«probar» debe ser true o false.', 422);
            }
            $probar = in_array($valor, [true, 1, '1', 'true'], true);
        }

        if (!isset($cuerpo['etiquetas']) || !is_array($cuerpo['etiquetas'])) {
            throw new EtiquetasException('Falta la lista «etiquetas».', 422);
        }
        $filas = [];
        $vistos = [];
        foreach ($cuerpo['etiquetas'] as $i => $par) {
            if (!is_array($par) || count($par) !== 2 || !isset($par[0], $par[1]) || !is_string($par[0]) || !is_string($par[1])) {
                throw new EtiquetasException(sprintf('Fila %d: cada elemento debe ser ["id-combinacion", "etiqueta"].', $i), 422);
            }
            $id = trim($par[0]);
            $etiqueta = trim($par[1]);
            if (!preg_match('~^(\d{1,10})-(\d{1,10})$~', $id, $m)) {
                throw new EtiquetasException(sprintf('Fila %d: id «%s» no es producto-combinación.', $i, self::recortar($id)), 422);
            }
            if (!preg_match('~^[a-z0-9_]{1,40}$~', $etiqueta)) {
                throw new EtiquetasException(sprintf('Fila %d: etiqueta «%s» no vale (minúsculas, números y _; de 1 a 40).', $i, self::recortar($etiqueta)), 422);
            }
            if (isset($vistos[$id])) {
                throw new EtiquetasException(sprintf('Fila %d: id «%s» repetido.', $i, $id), 422);
            }
            $vistos[$id] = true;
            $filas[] = [(int) $m[1], (int) $m[2], $etiqueta];
        }
        if (count($filas) < self::MINIMO_FILAS) {
            throw new EtiquetasException(sprintf('Solo %d filas; el mínimo es %d. La tabla no se toca.', count($filas), self::MINIMO_FILAS), 422);
        }

        return ['origen' => $origen, 'probar' => $probar, 'filas' => $filas];
    }

    /**
     * Recuento por etiqueta, de más a menos.
     *
     * @param array<int, array{0: int, 1: int, 2: string}> $filas
     *
     * @return array<string, int>
     */
    public static function porEtiqueta(array $filas): array
    {
        $cuenta = [];
        foreach ($filas as $fila) {
            $cuenta[$fila[2]] = ($cuenta[$fila[2]] ?? 0) + 1;
        }
        arsort($cuenta);

        return $cuenta;
    }

    /** Filas que hay ahora en la tabla. */
    public static function contar(): int
    {
        return (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . self::tabla() . '`');
    }

    /**
     * Sustituye la tabla entera por las filas dadas, de una vez: se cargan en una tabla gemela y se intercambian
     * con RENAME TABLE (atómico). Si algo falla, la tabla original queda como estaba.
     *
     * @param array<int, array{0: int, 1: int, 2: string}> $filas
     */
    public static function sustituir(array $filas): void
    {
        $db = Db::getInstance();
        $tabla = self::tabla();
        $nueva = $tabla . '__nueva';
        $vieja = $tabla . '__vieja';

        try {
            $db->execute('DROP TABLE IF EXISTS `' . $nueva . '`');
            $db->execute('DROP TABLE IF EXISTS `' . $vieja . '`');
            $db->execute('CREATE TABLE `' . $nueva . '` LIKE `' . $tabla . '`');
            foreach (array_chunk($filas, 1000) as $trozo) {
                $valores = [];
                foreach ($trozo as [$idProducto, $idCombinacion, $etiqueta]) {
                    $valores[] = '(' . (int) $idProducto . ', ' . (int) $idCombinacion . ", '" . pSQL($etiqueta) . "')";
                }
                if (!$db->execute('INSERT INTO `' . $nueva . '` (`id_product`, `id_product_attribute`, `custom_label_0`) VALUES ' . implode(', ', $valores))) {
                    throw new EtiquetasException('No se pudieron insertar las filas: ' . $db->getMsgError(), 500);
                }
            }
            $cargadas = (int) $db->getValue('SELECT COUNT(*) FROM `' . $nueva . '`');
            if ($cargadas !== count($filas)) {
                throw new EtiquetasException(sprintf('Se cargaron %d filas de %d; no se cambia nada.', $cargadas, count($filas)), 500);
            }
            if (!$db->execute('RENAME TABLE `' . $tabla . '` TO `' . $vieja . '`, `' . $nueva . '` TO `' . $tabla . '`')) {
                throw new EtiquetasException('No se pudo intercambiar la tabla: ' . $db->getMsgError(), 500);
            }
            $db->execute('DROP TABLE IF EXISTS `' . $vieja . '`');
        } catch (\Throwable $e) {
            // la tabla original no se ha tocado (o ya se ha intercambiado con éxito antes del DROP final)
            $db->execute('DROP TABLE IF EXISTS `' . $nueva . '`');
            if ($e instanceof EtiquetasException) {
                throw $e;
            }
            throw new EtiquetasException('Error al guardar: ' . $e->getMessage(), 500);
        }
    }

    /** Apunta en Configuration cuándo, de dónde y cuántas filas llegaron. */
    public static function anotarActualizacion(string $origen, int $filas): void
    {
        Configuration::updateValue(self::ULTIMA_FECHA, date('Y-m-d H:i:s'));
        Configuration::updateValue(self::ULTIMA_ORIGEN, $origen);
        Configuration::updateValue(self::ULTIMA_FILAS, (string) $filas);
    }

    /** La etiqueta de un id «producto-combinacion», o null si no está. */
    public static function buscar(string $id): ?string
    {
        if (!preg_match('~^(\d{1,10})-(\d{1,10})$~', trim($id), $m)) {
            return null;
        }
        $valor = Db::getInstance()->getValue(
            'SELECT custom_label_0 FROM `' . self::tabla() . '` WHERE id_product = ' . (int) $m[1] . ' AND id_product_attribute = ' . (int) $m[2]
        );

        return $valor === false || $valor === null ? null : (string) $valor;
    }

    /**
     * Lo que enseña la página del back office.
     *
     * @return array{total: int, por_etiqueta: array<string, int>, ultima_fecha: string, ultima_origen: string, ultima_filas: string}
     */
    public static function estado(): array
    {
        $cuenta = [];
        $filas = Db::getInstance()->executeS(
            'SELECT custom_label_0 AS etiqueta, COUNT(*) AS n FROM `' . self::tabla() . '` GROUP BY custom_label_0 ORDER BY n DESC'
        ) ?: [];
        foreach ($filas as $fila) {
            $cuenta[(string) $fila['etiqueta']] = (int) $fila['n'];
        }

        return [
            'total' => self::contar(),
            'por_etiqueta' => $cuenta,
            'ultima_fecha' => (string) Configuration::get(self::ULTIMA_FECHA),
            'ultima_origen' => (string) Configuration::get(self::ULTIMA_ORIGEN),
            'ultima_filas' => (string) Configuration::get(self::ULTIMA_FILAS),
        ];
    }

    // ------------------------------------------------------------------ clave

    /** Crea la clave y los ajustes si no existen. No toca nada que ya esté. */
    public static function prepararAjustes(): void
    {
        if ((string) Configuration::get(self::CLAVE_AJUSTE) === '') {
            Configuration::updateValue(self::CLAVE_AJUSTE, self::claveNueva());
        }
        foreach ([self::ULTIMA_FECHA, self::ULTIMA_ORIGEN, self::ULTIMA_FILAS] as $clave) {
            if (Configuration::get($clave) === false) {
                Configuration::updateValue($clave, '');
            }
        }
    }

    public static function quitarAjustes(): void
    {
        foreach ([self::CLAVE_AJUSTE, self::ULTIMA_FECHA, self::ULTIMA_ORIGEN, self::ULTIMA_FILAS] as $clave) {
            Configuration::deleteByName($clave);
        }
    }

    public static function claveNueva(): string
    {
        return bin2hex(random_bytes(20));
    }

    /** Verdadero si la clave recibida es la configurada (comparación en tiempo constante). */
    public static function claveValida(string $recibida): bool
    {
        $configurada = (string) Configuration::get(self::CLAVE_AJUSTE);

        return $configurada !== '' && $recibida !== '' && hash_equals($configurada, $recibida);
    }

    private static function tabla(): string
    {
        return _DB_PREFIX_ . self::TABLA;
    }

    private static function recortar(string $texto): string
    {
        return mb_strlen($texto) > 60 ? mb_substr($texto, 0, 57) . '…' : $texto;
    }
}
