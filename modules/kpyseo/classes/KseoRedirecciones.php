<?php
/**
 * Redirecciones que la tienda necesita y PrestaShop no hace solo (P-SEO-2). Se ejecutan en
 * actionDispatcherBefore, antes de resolver el controlador, solo en peticiones GET del front:
 *
 *  1. Query string duplicada: la redirección del dominio viejo deja «?a=b?a=b&src=pym». Se limpia y se
 *     vuelve a evaluar el resto de reglas sobre la URL limpia (una sola redirección más).
 *  2. Búsqueda cuyo término es exactamente una marca («/buscar?controller=search&s=beaphar», «/busqueda?s=libra»):
 *     301 a «/marcas/<marca>». Son las URL del dominio viejo que aún traen clics.
 *  3. URL no amigable «index.php?controller=product&id_product=N» (y category, cms, manufacturer): 301 a la URL
 *     amigable si el objeto está activo. Si no lo está, se deja a PrestaShop (404 o la redirección propia del producto).
 *  4. Lista configurable (una por línea): «/ruta-vieja | /destino | 301», «/blog/* | / | 301», «/lo-que-sea | - | 410».
 *
 * Nunca toca peticiones POST, ajax ni controladores de módulos (pasarelas, sitemap, landings).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoRedirecciones
{
    /** Punto de entrada: decide y, si toca, redirige (y termina la petición). */
    public static function atender(Context $ctx): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }
        if (Tools::getValue('fc') === 'module' || Tools::getValue('ajax') || Tools::isSubmit('ajax')) {
            return;
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $ruta = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $rutaRel = self::rutaRelativa($ruta);
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $get = $_GET;
        $limpiada = true;

        // 1. query string duplicada
        /*if (strpos($query, '?') !== false && KseoAjustes::activo('KPYSEO_REDIR_LIMPIAR_QS')) {
            $get = self::parametrosLimpios($query);
            $limpiada = true;
        }*/

        // 2. búsqueda de marca
        if (KseoAjustes::activo('KPYSEO_REDIR_BUSQUEDAS')) {
            $destino = self::destinoBusqueda($rutaRel, $get, $ctx);
            if ($destino !== '') {
                self::ir($destino);
            }
        }

        // 3. index.php no amigable
        /*if (KseoAjustes::activo('KPYSEO_REDIR_INDEXPHP')) {
            $destino = self::destinoNoAmigable($rutaRel, $get, $ctx);
            if ($destino !== '' && self::sinQuery($destino) !== self::sinQuery($ctx->link->getBaseLink() . ltrim($rutaRel, '/'))) {
                self::ir($destino);
            }
        }*/

        // 4. lista configurable
        $regla = self::reglaConfigurada($rutaRel);
        if ($regla !== null) {
            if ($regla['codigo'] === 410) {
                self::desaparecida();
            }
            self::ir(self::absoluta($regla['destino'], $ctx));
        }

        // si solo había que limpiar la query, se redirige a la URL limpia
        if ($limpiada) {
            unset($get['src']);
            $limpia = http_build_query($get, '', '&', PHP_QUERY_RFC3986);
            self::ir($ctx->link->getBaseLink() . ltrim($rutaRel, '/') . ($limpia !== '' ? '?' . $limpia : ''));
        }
    }

    // ------------------------------------------------------------ reglas

    /** @param array<string, mixed> $get */
    private static function destinoBusqueda(string $rutaRel, array $get, Context $ctx): string
    {
        $esBusqueda = (($get['controller'] ?? '') === 'search')
            || in_array(trim($rutaRel, '/'), ['buscar', 'busqueda', 'search'], true);
        if (!$esBusqueda) {
            return '';
        }
        $termino = trim((string) ($get['s'] ?? $get['search_query'] ?? ''));
        if ($termino === '' || mb_strlen($termino) > 40) {
            return '';
        }
        $rewrite = self::marcaPorTermino($termino);
        if ($rewrite === '') {
            return '';
        }

        return $ctx->link->getBaseLink() . 'marcas/' . $rewrite;
    }

    /** El link_rewrite de la marca cuyo nombre o rewrite coincide exactamente con el término, o ''. */
    public static function marcaPorTermino(string $termino): string
    {
        if (!KseoMarcas::tablasDisponibles()) {
            return '';
        }
        $clave = Tools::str2url($termino);
        if ($clave === '' || strlen($clave) > 40) {
            return '';
        }
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT m.name, km.link_rewrite
             FROM `' . _DB_PREFIX_ . 'manufacturer` m
             INNER JOIN `' . _DB_PREFIX_ . 'kpy_manufacturer` km ON km.id_manufacturer = m.id_manufacturer
             WHERE m.active = 1 AND km.link_rewrite <> \'\''
        ) ?: [];
        foreach ($filas as $fila) {
            $rewrite = (string) $fila['link_rewrite'];
            if ($clave === $rewrite || $clave === Tools::str2url((string) $fila['name'])) {
                return $rewrite;
            }
        }

        return '';
    }

    /** @param array<string, mixed> $get */
    private static function destinoNoAmigable(string $rutaRel, array $get, Context $ctx): string
    {
        $limpia = trim($rutaRel, '/');
        if ($limpia !== '' && $limpia !== 'index.php') {
            return '';
        }
        $controlador = (string) ($get['controller'] ?? '');
        $idLang = (int) ($ctx->language->id ?? 0) ?: (int) Configuration::get('PS_LANG_DEFAULT');
        $idShop = (int) ($ctx->shop->id ?? 0) ?: (int) Configuration::get('PS_SHOP_DEFAULT');
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);

        if (($controlador === 'product' || $controlador === '') && (int) ($get['id_product'] ?? 0) > 0) {
            $id = (int) $get['id_product'];
            $activo = (int) $db->getValue('SELECT active FROM `' . _DB_PREFIX_ . 'product_shop` WHERE id_product = ' . $id . ' AND id_shop = ' . $idShop);
            if ($activo !== 1) {
                return '';
            }
            $ipa = (int) ($get['id_product_attribute'] ?? 0);

            return $ctx->link->getProductLink($id, null, null, null, $idLang, $idShop, $ipa > 0 ? $ipa : null);
        }
        if (($controlador === 'category' || $controlador === '') && (int) ($get['id_category'] ?? 0) > 0) {
            $id = (int) $get['id_category'];
            $activo = (int) $db->getValue('SELECT active FROM `' . _DB_PREFIX_ . 'category` WHERE id_category = ' . $id);
            if ($activo !== 1) {
                return '';
            }

            return $ctx->link->getCategoryLink($id, null, $idLang, null, $idShop);
        }
        if ($controlador === 'cms' && (int) ($get['id_cms'] ?? 0) > 0) {
            $id = (int) $get['id_cms'];
            $activo = (int) $db->getValue('SELECT active FROM `' . _DB_PREFIX_ . 'cms` WHERE id_cms = ' . $id);
            if ($activo !== 1) {
                return '';
            }

            return $ctx->link->getCMSLink($id, null, null, $idLang, $idShop);
        }
        if ($controlador === 'manufacturer' && (int) ($get['id_manufacturer'] ?? 0) > 0) {
            $rewrite = KseoMarcas::rewritePorId((int) $get['id_manufacturer']);

            return $rewrite !== '' ? $ctx->link->getBaseLink() . 'marcas/' . $rewrite : '';
        }

        return '';
    }

    /**
     * La regla de la lista configurable que casa con la ruta, o null.
     *
     * @return array{destino: string, codigo: int}|null
     */
    public static function reglaConfigurada(string $rutaRel): ?array
    {
        foreach (self::reglas() as $regla) {
            if ($regla['prefijo']) {
                if (strpos($rutaRel, $regla['origen']) !== 0) {
                    continue;
                }
            } elseif (rtrim($rutaRel, '/') !== rtrim($regla['origen'], '/')) {
                continue;
            }
            if ($regla['codigo'] !== 410 && rtrim($regla['destino'], '/') === rtrim($rutaRel, '/')) {
                continue; // evita bucles
            }

            return ['destino' => $regla['destino'], 'codigo' => $regla['codigo']];
        }

        return null;
    }

    /**
     * Las reglas del ajuste KPYSEO_REDIRECCIONES. Una por línea: «origen | destino | código». El código es 301
     * si falta; 410 para «ya no existe» (destino vacío o «-»). Un «*» al final del origen casa por prefijo.
     *
     * @return array<int, array{origen: string, prefijo: bool, destino: string, codigo: int}>
     */
    public static function reglas(): array
    {
        $texto = KseoAjustes::texto('KPYSEO_REDIRECCIONES');
        if ($texto === '') {
            return [];
        }
        $reglas = [];
        foreach (preg_split('~[\r\n]+~', $texto) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#') {
                continue;
            }
            $partes = array_map('trim', explode('|', $linea));
            $origen = (string) ($partes[0] ?? '');
            $destino = (string) ($partes[1] ?? '');
            $codigo = (int) ($partes[2] ?? 301);
            if ($origen === '' || $origen[0] !== '/') {
                continue;
            }
            $prefijo = substr($origen, -1) === '*';
            if ($prefijo) {
                $origen = rtrim(substr($origen, 0, -1), '/') . '/';
            }
            if ($destino === '' || $destino === '-') {
                $codigo = 410;
            }
            if (!in_array($codigo, [301, 302, 410], true)) {
                $codigo = 301;
            }
            $reglas[] = ['origen' => $origen, 'prefijo' => $prefijo, 'destino' => $destino, 'codigo' => $codigo];
        }

        return $reglas;
    }

    // ------------------------------------------------------------ utilidades

    /** «a=b&c=d?a=b&c=d&src=pym» → los parámetros de la primera parte más los que solo estén en la segunda. */
    public static function parametrosLimpios(string $query): array
    {
        $corte = strpos($query, '?');
        $primera = $corte === false ? $query : substr($query, 0, $corte);
        $segunda = $corte === false ? '' : substr($query, $corte + 1);
        parse_str($primera, $a);
        parse_str($segunda, $b);
        foreach ($b as $clave => $valor) {
            if (!array_key_exists($clave, $a)) {
                $a[$clave] = $valor;
            }
        }

        return $a;
    }

    /** La ruta pedida sin la base de la tienda (para instalaciones en subcarpeta). */
    private static function rutaRelativa(string $ruta): string
    {
        $base = rtrim(__PS_BASE_URI__, '/');
        if ($base !== '' && strpos($ruta, $base) === 0) {
            $ruta = substr($ruta, strlen($base));
        }

        return '/' . ltrim($ruta, '/');
    }

    private static function absoluta(string $destino, Context $ctx): string
    {
        if (preg_match('~^https?://~i', $destino)) {
            return $destino;
        }

        return $ctx->link->getBaseLink() . ltrim($destino, '/');
    }

    private static function sinQuery(string $url): string
    {
        return rtrim((string) (strtok($url, '?') ?: $url), '/');
    }

    private static function ir(string $url): void
    {
        Tools::redirect($url, __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
        exit;
    }

    /** Respuesta 410 mínima: la página ya no existe y no volverá. */
    private static function desaparecida(): void
    {
        header('HTTP/1.1 410 Gone');
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex');
        $inicio = htmlspecialchars(Context::getContext()->link->getBaseLink(), ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Esta página ya no existe</title>'
            . '<style>body{font-family:system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1rem;color:#222}a{color:#0b6}</style></head>'
            . '<body><h1>Esta página ya no existe</h1><p>La hemos retirado y no va a volver. Puedes seguir desde la <a href="' . $inicio . '">portada</a>.</p></body></html>';
        exit;
    }
}
