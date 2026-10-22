<?php
/**
 * Todo lo que la ficha lee de la base de datos, en un sitio.
 *
 * Solo lee tablas que ya existen y que se rellenan al dar de alta un producto (los paquetes de
 * `kompy-tarifas`): no crea ni cambia nada. Si una tabla no está (otra tienda, o el módulo que la
 * crea desinstalado), esa parte sale vacía y la ficha sigue.
 *
 * Tablas (sin prefijo):
 *   feature_product…              características (vía Product::getFrontFeaturesStatic, que el
 *                                 tema ya consulta y queda en caché: no cuesta otra consulta)
 *   kpy_benefit_product            qué beneficios lleva el producto
 *   kpy_benefits, kpy_benefit_lang catálogo de beneficios (57), con su icono
 *   kpy_product_ingredients        texto de ingredientes, por idioma
 *   kpy_product_component          composición analítica (valor en texto: «27 %»)
 *   kpy_component_lang             nombre de cada componente
 *   kpy_product_rations_table      ración diaria, en HTML
 *   kpy_product_attribute          combinaciones apagadas (active = 0) que no se enseñan
 *   kpy_manufacturer               landing de cada marca (/marcas/lilys-kitchen) y su categoría
 */
class KppDatos
{
    /**
     * Carpeta de los iconos de beneficios, dentro de `modules/`. Por defecto, la de siempre (los
     * SVG que ya se ven en la ficha). Se cambia en la configuración del módulo (KPP_ICONOS), p. ej.
     * para servirlos desde este mismo módulo y no depender de ningún otro.
     */
    public static string $iconos = 'kpyproductdescription/views/img/benefits/';

    /** @var array<string, bool>|null */
    private static ?array $tablas = null;

    public static function hayTabla(string $tabla): bool
    {
        if (self::$tablas === null) {
            self::$tablas = [];
            $nombres = ['kpy_benefit_product', 'kpy_benefits', 'kpy_benefit_lang', 'kpy_product_ingredients',
                'kpy_product_component', 'kpy_component_lang', 'kpy_product_rations_table', 'kpy_product_attribute',
                'kpy_manufacturer'];
            $en = implode(',', array_map(fn ($t) => "'" . pSQL(_DB_PREFIX_ . $t) . "'", $nombres));
            $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (' . $en . ')'
            ) ?: [];
            foreach ($filas as $f) {
                self::$tablas[substr($f['t'], strlen(_DB_PREFIX_))] = true;
            }
        }

        return isset(self::$tablas[$tabla]);
    }

    /**
     * Enlace de la marca, el mismo que el nombre de marca de arriba de la ficha: su landing
     * (`/marcas/lilys-kitchen`, de `kpymanufacturer`), si no su categoría y si no la página de
     * marca de PrestaShop. La landing solo si `kpymanufacturer` está activo, que es quien tiene la
     * ruta; no se usa ninguna de sus clases.
     */
    public static function urlMarca(int $idMarca, Link $link): string
    {
        if ($idMarca <= 0) {
            return '';
        }
        if (self::hayTabla('kpy_manufacturer')) {
            $fila = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                'SELECT link_rewrite, id_category_related FROM ' . _DB_PREFIX_ . 'kpy_manufacturer WHERE id_manufacturer = ' . (int) $idMarca
            ) ?: [];
            $rewrite = trim((string) ($fila['link_rewrite'] ?? ''));
            if ($rewrite !== '' && Module::isEnabled('kpymanufacturer')) {
                return $link->getBaseLink() . 'marcas/' . rawurlencode($rewrite);
            }
            $idCategoria = (int) ($fila['id_category_related'] ?? 0);
            if ($idCategoria > 0) {
                return $link->getCategoryLink($idCategoria);
            }
        }

        return $link->getManufacturerLink($idMarca);
    }

    /** @return array<int, array{nombre: string, valor: string}> */
    public static function caracteristicas(int $idProducto, int $idIdioma): array
    {
        $salida = [];
        foreach (Product::getFrontFeaturesStatic($idIdioma, $idProducto) ?: [] as $f) {
            $salida[] = ['nombre' => (string) $f['name'], 'valor' => (string) $f['value']];
        }

        return $salida;
    }

    /**
     * `protege` = 1 en los de antiparasitarios (pulgas, garrapatas…): la sección se titula
     * entonces «Protege contra» en vez de «Beneficios clave», como hasta ahora.
     *
     * @return array<int, array{nombre: string, icono: string, protege: bool}>
     */
    public static function beneficios(int $idProducto, int $idIdioma): array
    {
        if (!self::hayTabla('kpy_benefit_product') || !self::hayTabla('kpy_benefits') || !self::hayTabla('kpy_benefit_lang')) {
            return [];
        }
        $columnas = self::columnasBeneficios();
        $orden = isset($columnas['position']) ? 'b.position, bp.id_benefit' : 'bp.id_benefit';
        $activo = isset($columnas['active']) ? ' AND b.active = 1' : '';
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT b.*, bl.name AS kpp_nombre
             FROM ' . _DB_PREFIX_ . 'kpy_benefit_product bp
             INNER JOIN ' . _DB_PREFIX_ . 'kpy_benefits b ON b.id_benefit = bp.id_benefit
             INNER JOIN ' . _DB_PREFIX_ . 'kpy_benefit_lang bl ON bl.id_benefit = bp.id_benefit AND bl.id_lang = ' . (int) $idIdioma . '
             WHERE bp.id_product = ' . (int) $idProducto . $activo . '
             ORDER BY ' . $orden
        ) ?: [];

        $campoIcono = null;
        foreach (['icon', 'image', 'img', 'file', 'filename', 'picture', 'svg'] as $c) {
            if (isset($columnas[$c])) {
                $campoIcono = $c;
                break;
            }
        }

        $salida = [];
        foreach ($filas as $f) {
            $nombre = trim((string) $f['kpp_nombre']);
            if ($nombre === '') {
                continue;
            }
            $salida[] = [
                'nombre' => $nombre,
                'icono' => $campoIcono ? self::urlIcono((string) $f[$campoIcono]) : '',
                'protege' => !empty($f['protege']),
            ];
        }

        return $salida;
    }

    public static function ingredientes(int $idProducto, int $idIdioma): string
    {
        if (!self::hayTabla('kpy_product_ingredients')) {
            return '';
        }

        return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT text FROM ' . _DB_PREFIX_ . 'kpy_product_ingredients
             WHERE id_product = ' . (int) $idProducto . ' AND id_lang = ' . (int) $idIdioma
        );
    }

    /** @return array<int, array{nombre: string, valor: string}> */
    public static function composicion(int $idProducto, int $idIdioma): array
    {
        if (!self::hayTabla('kpy_product_component') || !self::hayTabla('kpy_component_lang')) {
            return [];
        }
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT cl.name AS nombre, pc.percentage AS valor
             FROM ' . _DB_PREFIX_ . 'kpy_product_component pc
             INNER JOIN ' . _DB_PREFIX_ . 'kpy_component_lang cl ON cl.id_component = pc.id_component AND cl.id_lang = ' . (int) $idIdioma . '
             WHERE pc.id_product = ' . (int) $idProducto . '
             ORDER BY pc.id_component'
        ) ?: [];

        return array_map(fn ($f) => ['nombre' => (string) $f['nombre'], 'valor' => (string) $f['valor']], $filas);
    }

    public static function racion(int $idProducto): string
    {
        if (!self::hayTabla('kpy_product_rations_table')) {
            return '';
        }

        return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT `table` FROM ' . _DB_PREFIX_ . 'kpy_product_rations_table WHERE id_product = ' . (int) $idProducto
        );
    }

    /**
     * Combinaciones que se venden, con su nombre («Saco de 2 Kg»), en el orden de la tienda.
     *
     * @return array<int, array{id: int, nombre: string}>
     */
    public static function combinaciones(int $idProducto, int $idIdioma, int $idTienda): array
    {
        // Una sola consulta. (Product::getAttributesResume haría además una de stock por
        // combinación, que aquí no hace falta.)
        $apagada = self::hayTabla('kpy_product_attribute')
            ? 'LEFT JOIN ' . _DB_PREFIX_ . 'kpy_product_attribute kpa ON kpa.id_product_attribute = pa.id_product_attribute'
            : '';
        $filtro = $apagada !== '' ? ' AND (kpa.active IS NULL OR kpa.active = 1)' : '';
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT pa.id_product_attribute AS id,
                    GROUP_CONCAT(al.name ORDER BY a.id_attribute_group SEPARATOR \', \') AS nombre,
                    MIN(a.position) AS posicion
             FROM ' . _DB_PREFIX_ . 'product_attribute pa
             INNER JOIN ' . _DB_PREFIX_ . 'product_attribute_shop pas
                     ON pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = ' . (int) $idTienda . '
             INNER JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute
             INNER JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON al.id_attribute = a.id_attribute AND al.id_lang = ' . (int) $idIdioma . '
             ' . $apagada . '
             WHERE pa.id_product = ' . (int) $idProducto . $filtro . '
             GROUP BY pa.id_product_attribute
             ORDER BY posicion, pa.id_product_attribute'
        ) ?: [];

        return array_map(fn ($f) => ['id' => (int) $f['id'], 'nombre' => (string) $f['nombre']], $filas);
    }

    /** @return array<string, bool> */
    private static function columnasBeneficios(): array
    {
        static $columnas = null;
        if ($columnas === null) {
            $columnas = [];
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'kpy_benefits') ?: [] as $c) {
                $columnas[strtolower((string) $c['Field'])] = true;
            }
        }

        return $columnas;
    }

    private static function urlIcono(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//|^/#', $valor)) {
            return $valor;
        }
        if (!preg_match('/\.(svg|png|webp|jpe?g|gif)$/i', $valor)) {
            $valor .= '.svg';
        }

        return _MODULE_DIR_ . self::$iconos . $valor;
    }
}
