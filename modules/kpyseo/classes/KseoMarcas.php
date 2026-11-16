<?php
/**
 * Las páginas de marca (`/marcas` y `/marcas/<marca>`, del módulo kpymanufacturer): canónica, título,
 * descripción y qué marcas tienen landing. Solo se leen sus tablas (`kpy_manufacturer` para el
 * link_rewrite y `kpy_manufacturer_landing` para saber si hay landing); si no existen, no se hace nada.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoMarcas
{
    private static ?bool $tablas = null;

    public static function esLanding(): bool
    {
        return Tools::getValue('module') === 'kpymanufacturer'
            && Tools::getValue('controller') === 'landing'
            && Tools::getValue('brand', '') !== '';
    }

    public static function esIndice(): bool
    {
        return Tools::getValue('module') === 'kpymanufacturer' && Tools::getValue('controller') === 'brands';
    }

    public static function rewriteActual(): string
    {
        return (string) preg_replace('~[^a-z0-9_-]~i', '', (string) Tools::getValue('brand', ''));
    }

    public static function tablasDisponibles(): bool
    {
        if (self::$tablas === null) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $a = $db->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'kpy_manufacturer\'');
            $b = $db->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'kpy_manufacturer_landing\'');
            self::$tablas = !empty($a) && !empty($b);
        }

        return self::$tablas;
    }

    public static function idPorRewrite(string $rewrite): int
    {
        if ($rewrite === '' || !self::tablasDisponibles()) {
            return 0;
        }

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT id_manufacturer FROM `' . _DB_PREFIX_ . 'kpy_manufacturer` WHERE link_rewrite = \'' . pSQL($rewrite) . '\''
        );
    }

    public static function rewritePorId(int $id): string
    {
        if ($id <= 0 || !self::tablasDisponibles()) {
            return '';
        }

        return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT link_rewrite FROM `' . _DB_PREFIX_ . 'kpy_manufacturer` WHERE id_manufacturer = ' . $id
        );
    }

    /**
     * Marcas activas, con landing y con algún producto activo: las que merecen estar en el sitemap.
     *
     * @return array<int, string> id_manufacturer => link_rewrite
     */
    public static function conLanding(int $idShop): array
    {
        if (!self::tablasDisponibles()) {
            return [];
        }
        $sql = 'SELECT m.id_manufacturer, km.link_rewrite
            FROM `' . _DB_PREFIX_ . 'manufacturer` m
            INNER JOIN `' . _DB_PREFIX_ . 'kpy_manufacturer` km ON km.id_manufacturer = m.id_manufacturer
            INNER JOIN `' . _DB_PREFIX_ . 'kpy_manufacturer_landing` ml ON ml.id_manufacturer = m.id_manufacturer
            WHERE m.active = 1 AND km.link_rewrite <> \'\'
              AND EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'product` p
                  INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.id_product = p.id_product AND ps.id_shop = ' . $idShop . '
                  WHERE p.id_manufacturer = m.id_manufacturer AND ps.active = 1 AND ps.visibility <> \'none\')
            ORDER BY m.name';
        $resultado = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ?: [] as $fila) {
            $resultado[(int) $fila['id_manufacturer']] = (string) $fila['link_rewrite'];
        }

        return $resultado;
    }

    /** Verdadero cuando la marca no tiene ningún producto activo y visible en la tienda. */
    public static function sinProductos(int $id, int $idShop): bool
    {
        if ($id <= 0) {
            return false;
        }
        $n = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product` p
             INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.id_product = p.id_product AND ps.id_shop = ' . $idShop . '
             WHERE p.id_manufacturer = ' . $id . ' AND ps.active = 1 AND ps.visibility <> \'none\''
        );

        return $n === 0;
    }

    /** La imagen de la marca para Open Graph, si el fichero existe. */
    public static function ogImagen(int $id, Context $ctx): string
    {
        if ($id <= 0 || !is_file(_PS_MANU_IMG_DIR_ . $id . '.jpg')) {
            return '';
        }

        return $ctx->link->getManufacturerImageLink($id);
    }

    /** Cambia título, meta descripción y Open Graph de la landing por los de la marca. */
    public static function reescribirCabecera(string $html, Context $ctx): string
    {
        if (!KseoAjustes::activo('KPYSEO_MARCAS_META')) {
            return $html;
        }
        $id = self::idPorRewrite(self::rewriteActual());
        if ($id <= 0) {
            return $html;
        }
        $idLang = (int) $ctx->language->id;
        $marca = new Manufacturer($id, $idLang);
        if (!Validate::isLoadedObject($marca)) {
            return $html;
        }
        [$titulo, $descripcion] = self::tituloYDescripcion($marca, $idLang);

        return self::reemplazarCabecera($html, $titulo, $descripcion);
    }

    /** Sustituye título, meta descripción y Open Graph en el HTML final (lo usan marcas y soporte). */
    public static function reemplazarCabecera(string $html, string $titulo, string $descripcion): string
    {
        $t = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
        $d = htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8');

        $html = (string) preg_replace('~<title>.*?</title>~s', '<title>' . $t . '</title>', $html, 1);
        $html = (string) preg_replace('~<meta name="description" content="[^"]*"\s*/?>~', '<meta name="description" content="' . $d . '">', $html, 1);
        $html = (string) preg_replace('~<meta property="og:title" content="[^"]*"\s*/?>~', '<meta property="og:title" content="' . $t . '" />', $html, 1);
        $html = (string) preg_replace('~<meta property="og:description" content="[^"]*"\s*/?>~', '<meta property="og:description" content="' . $d . '" />', $html, 1);

        return $html;
    }

    /** @return array{0: string, 1: string} */
    private static function tituloYDescripcion(Manufacturer $marca, int $idLang): array
    {
        $tienda = KseoAjustes::texto('KPYSEO_MARCA_TEXTO') ?: (string) Configuration::get('PS_SHOP_NAME');
        $nombre = trim((string) $marca->name);

        // un meta título o una meta descripción iguales al nombre de la marca cuentan como vacíos
        $titulo = trim((string) $marca->meta_title);
        if ($titulo === '' || mb_strtolower($titulo) === mb_strtolower($nombre)) {
            $titulo = $nombre . ': toda la gama en ' . $tienda;
        }

        $descripcion = trim((string) $marca->meta_description);
        if ($descripcion === '' || mb_strtolower($descripcion) === mb_strtolower($nombre)) {
            $descripcion = self::textoPlano((string) $marca->short_description);
        }
        if ($descripcion === '') {
            $descripcion = self::textoPlano((string) $marca->description);
        }
        if ($descripcion === '') {
            $descripcion = self::subtituloLanding((int) $marca->id, $idLang);
        }
        if ($descripcion === '') {
            $descripcion = $nombre . ' en ' . $tienda . ': toda la gama, con envío en 1-2 días laborables a la península.';
        }

        return [self::recortar($titulo, 70), self::recortar($descripcion, 170)];
    }

    private static function subtituloLanding(int $id, int $idLang): string
    {
        if (!self::tablasDisponibles()) {
            return '';
        }
        $valor = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT mll.subtitle FROM `' . _DB_PREFIX_ . 'kpy_manufacturer_landing` ml
             INNER JOIN `' . _DB_PREFIX_ . 'kpy_manufacturer_landing_lang` mll ON mll.id_landing = ml.id_landing AND mll.id_lang = ' . $idLang . '
             WHERE ml.id_manufacturer = ' . $id
        );

        return self::textoPlano((string) $valor);
    }

    public static function textoPlano(string $html): string
    {
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('~\s+~u', ' ', $texto));
    }

    public static function recortar(string $texto, int $max): string
    {
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        $corte = mb_substr($texto, 0, $max - 1);
        $espacio = mb_strrpos($corte, ' ');
        if ($espacio !== false && $espacio > $max * 0.6) {
            $corte = mb_substr($corte, 0, $espacio);
        }

        return rtrim($corte, ' ,;:.') . '…';
    }
}
