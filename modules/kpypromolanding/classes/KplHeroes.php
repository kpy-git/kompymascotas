<?php
/**
 * Qué foto de cada producto sale en las **tarjetas** de la landing (lo más rebajado, los listados por
 * tipo y por promoción, la campaña): la primera de su ficha que es **un solo saco de pie**. La
 * portada del catálogo si vale; si la portada es una foto de transición (saco viejo, flecha, saco
 * nuevo: «Nuevo packaging»), la siguiente que valga. Si ninguna vale, se deja la portada.
 *
 * Es la misma regla que la biblioteca aplica al héroe del banner (`KplBiblioteca::producto()`),
 * pero sin recortar nada: cada foto se **mide** una vez (`KplRecorte::medir()`, 0,3 s en una copia
 * de 420 px) y la medida queda apuntada en `kpy_promo_foto` con la firma del fichero. Si la
 * biblioteca ya recortó esa foto, se usa su decisión (es la misma regla con el motor bueno).
 *
 * La decisión por producto se escribe en el resumen (`kpy_promo_dato`, nombre `heroes`) al
 * sincronizar, y el hook `actionPresentProductListing` la aplica al pintar: cambia la portada de la
 * tarjeta por esa foto. No se escribe nada en el catálogo: la portada de la ficha es la de siempre.
 */
class KplHeroes
{
    public const TABLA = 'kpy_promo_foto';
    public const DATO = 'heroes';
    /** 1 = la foto héroe también en los listados de toda la tienda (categorías, buscador, portada); 0 = solo en la landing. */
    public const CONF_TIENDA = 'KPL_HEROE_TIENDA';
    /** Cuántas fotos de una ficha se miran como mucho. */
    private const MAXIMO = 8;

    /**
     * Decide la foto de cada producto. Devuelve id de producto => id de imagen (0 = la portada de
     * siempre, porque vale o porque ninguna vale).
     *
     * @param int[] $idsProductos
     *
     * @return array<int, int>
     */
    public static function deProductos(array $idsProductos): array
    {
        $salida = [];
        foreach (array_unique(array_map('intval', $idsProductos)) as $id) {
            if ($id > 0) {
                $salida[$id] = self::decidir($id);
            }
        }

        return $salida;
    }

    /** La foto que sale en la tarjeta de un producto (id de imagen), o 0 si se queda la portada. */
    public static function decidir(int $idProducto): int
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $fotos = $db->executeS("SELECT id_image, cover FROM {$p}image WHERE id_product = " . (int) $idProducto . ' ORDER BY cover DESC, position ASC') ?: [];
        $fotos = array_slice($fotos, 0, self::MAXIMO);
        foreach ($fotos as $i => $f) {
            $idImagen = (int) $f['id_image'];
            $medida = self::medida($idImagen, $idProducto);
            if ($medida === null) {
                continue; // no se sabe (sin fondo blanco, ilegible): se prueba la siguiente
            }
            if ($medida['apta']) {
                return $i === 0 ? 0 : $idImagen; // la portada vale: nada que cambiar
            }
        }

        return 0;
    }

    /**
     * La medida de una foto: de la biblioteca si ya la recortó, de la tabla si ya se midió (y el
     * fichero no ha cambiado), y si no se mide ahora y se apunta.
     *
     * @return ?array{objetos: int, apta: bool}
     */
    private static function medida(int $idImagen, int $idProducto): ?array
    {
        $ruta = rtrim(_PS_IMG_DIR_, '/\\') . '/p/' . Image::getImgFolderStatic($idImagen) . $idImagen . '.jpg';
        if (!is_file($ruta)) {
            return null;
        }
        $firma = md5_file($ruta) ?: '';
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $biblioteca = $db->getRow("SELECT objetos, apta, firma, bytes FROM {$p}kpy_promo_imagen WHERE origen = 'producto' AND id_ref = " . $idImagen);
        if ($biblioteca && (string) $biblioteca['firma'] === $firma && (int) $biblioteca['bytes'] > 0) {
            return ['objetos' => (int) $biblioteca['objetos'], 'apta' => (int) $biblioteca['apta'] === 1];
        }
        $fila = $db->getRow('SELECT objetos, apta, firma, nota FROM ' . $p . self::TABLA . ' WHERE id_image = ' . $idImagen);
        if ($fila && (string) $fila['firma'] === $firma) {
            return (string) $fila['nota'] === 'sin fondo blanco' ? null : ['objetos' => (int) $fila['objetos'], 'apta' => (int) $fila['apta'] === 1];
        }
        $m = KplRecorte::medir($ruta);
        $campos = [
            'id_image' => $idImagen,
            'id_product' => $idProducto,
            'firma' => pSQL($firma),
            'objetos' => (int) ($m['objetos'] ?? 0),
            'ancho' => (int) ($m['ancho'] ?? 0),
            'alto' => (int) ($m['alto'] ?? 0),
            'apta' => (int) ($m['apta'] ?? false),
            'nota' => $m === null ? 'sin fondo blanco' : ($m['apta'] ? 'un saco de pie' : ($m['objetos'] !== 1 ? $m['objetos'] . ' objetos (transición, flecha)' : 'más ancha que alta')),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        $db->execute(
            'INSERT INTO ' . $p . self::TABLA . ' (' . implode(', ', array_keys($campos)) . ") VALUES ('" . implode("', '", array_map('strval', $campos)) . "')
             ON DUPLICATE KEY UPDATE id_product = VALUES(id_product), firma = VALUES(firma), objetos = VALUES(objetos), ancho = VALUES(ancho), alto = VALUES(alto), apta = VALUES(apta), nota = VALUES(nota), date_upd = VALUES(date_upd)"
        );

        return $m === null ? null : ['objetos' => (int) $m['objetos'], 'apta' => (bool) $m['apta']];
    }

    /** Las medidas apuntadas (para la pantalla), más recientes primero. */
    public static function medidas(int $limite = 200): array
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM ' . _DB_PREFIX_ . self::TABLA . ' ORDER BY date_upd DESC LIMIT ' . (int) $limite) ?: [];
    }

    /** ¿La foto héroe se aplica también en los listados de toda la tienda? */
    public static function enTodaLaTienda(): bool
    {
        return (int) Configuration::getGlobalValue(self::CONF_TIENDA) === 1;
    }
}
