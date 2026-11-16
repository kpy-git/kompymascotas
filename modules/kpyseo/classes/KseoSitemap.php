<?php
/**
 * Los sitemaps XML de la tienda, generados por el módulo y servidos en `/sitemap.xml` (índice),
 * `/sitemap-paginas.xml`, `/sitemap-categorias.xml`, `/sitemap-marcas.xml` y `/sitemap-productos-N.xml`.
 *
 * Entran: la portada y las páginas de contenido indexables; las categorías activas con producto
 * (propio o de sus hijas); las marcas con landing y producto; los productos activos y visibles, con su
 * imagen de portada. No entran: filtros, paginaciones, categorías vacías, productos apagados o sin
 * visibilidad. Se guardan en `var/cache/` y se regeneran cuando alguien los pide pasadas N horas.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoSitemap
{
    private Context $ctx;
    private int $idShop;
    private int $idLang;

    public function __construct(Context $ctx)
    {
        $this->ctx = $ctx;
        $this->idShop = (int) $ctx->shop->id;
        $this->idLang = (int) $ctx->language->id;
    }

    // ----------------------------------------------------------------- caché

    /**
     * La carpeta de la caché: `var/cache/` del módulo y, si el servidor no deja escribir ahí, la caché
     * de PrestaShop (`var/cache/<entorno>/kpyseo/`, que se vacía al limpiar la caché: se regenera sola).
     */
    public static function dirCache(): string
    {
        static $dir = null;
        if ($dir === null) {
            $propia = _PS_MODULE_DIR_ . 'kpyseo/var/cache/';
            if (!is_dir($propia)) {
                @mkdir($propia, 0755, true);
            }
            $dir = is_dir($propia) && is_writable($propia) ? $propia : _PS_CACHE_DIR_ . 'kpyseo/';
        }

        return $dir;
    }

    public static function prepararCache(): void
    {
        $dir = self::dirCache();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_file($dir . '.htaccess')) {
            @file_put_contents($dir . '.htaccess', "Require all denied\n");
        }
        if (!is_file($dir . 'index.php')) {
            @file_put_contents($dir . 'index.php', "<?php\nheader('HTTP/1.1 403 Forbidden');\nexit;\n");
        }
    }

    public static function borrarCache(): void
    {
        foreach (glob(self::dirCache() . '*.{xml,json}', GLOB_BRACE) ?: [] as $fichero) {
            @unlink($fichero);
        }
    }

    private function prefijo(): string
    {
        return $this->idShop . '-' . $this->idLang . '-';
    }

    private function ruta(string $nombre): string
    {
        return self::dirCache() . $this->prefijo() . $nombre . '.xml';
    }

    private function rutaMeta(): string
    {
        return self::dirCache() . $this->prefijo() . 'meta.json';
    }

    /** @return array{generado: int, paginas: int, categorias: int, marcas: int, productos: int, ficheros_productos: int} */
    public function estado(): array
    {
        $vacio = ['generado' => 0, 'paginas' => 0, 'categorias' => 0, 'marcas' => 0, 'productos' => 0, 'ficheros_productos' => 0];
        if (!is_file($this->rutaMeta())) {
            return $vacio;
        }
        $meta = json_decode((string) file_get_contents($this->rutaMeta()), true);

        return is_array($meta) ? array_merge($vacio, array_intersect_key($meta, $vacio)) : $vacio;
    }

    private function caducado(): bool
    {
        $estado = $this->estado();
        if ($estado['generado'] === 0 || !is_file($this->ruta('indice'))) {
            return true;
        }
        $horas = max(1, (int) KseoAjustes::numero('KPYSEO_SITEMAP_HORAS'));

        return (time() - $estado['generado']) > $horas * 3600;
    }

    private function asegurar(): void
    {
        if ($this->caducado()) {
            $this->regenerar();
        }
    }

    // --------------------------------------------------------------- servir

    public function indice(): ?string
    {
        $this->asegurar();

        return is_file($this->ruta('indice')) ? (string) file_get_contents($this->ruta('indice')) : null;
    }

    public function fichero(string $tipo, int $n): ?string
    {
        $this->asegurar();
        $nombre = $tipo === 'productos' ? 'productos-' . max(1, $n) : $tipo;
        if (!in_array($tipo, ['paginas', 'categorias', 'marcas', 'productos'], true)) {
            return null;
        }
        $ruta = $this->ruta($nombre);

        return is_file($ruta) ? (string) file_get_contents($ruta) : null;
    }

    // ------------------------------------------------------------- generar

    /** @return array{generado: int, paginas: int, categorias: int, marcas: int, productos: int, ficheros_productos: int} */
    public function regenerar(): array
    {
        self::prepararCache();
        $cerrojo = @fopen(self::dirCache() . $this->prefijo() . 'cerrojo', 'c');
        if ($cerrojo && !flock($cerrojo, LOCK_EX | LOCK_NB)) {
            // otro proceso lo está generando: si ya hay uno viejo, vale mientras tanto
            if (is_file($this->ruta('indice'))) {
                fclose($cerrojo);

                return $this->estado();
            }
            flock($cerrojo, LOCK_EX);
        }

        $paginas = $this->urlsPaginas();
        $categorias = $this->urlsCategorias();
        $marcas = $this->urlsMarcas();
        $productos = $this->urlsProductos();

        $porFichero = max(500, (int) KseoAjustes::numero('KPYSEO_SITEMAP_POR_FICHERO'));
        $trozos = array_chunk($productos, $porFichero) ?: [[]];

        $ficheros = ['paginas' => $paginas, 'categorias' => $categorias, 'marcas' => $marcas];
        foreach ($trozos as $i => $trozo) {
            $ficheros['productos-' . ($i + 1)] = $trozo;
        }
        foreach (glob(self::dirCache() . $this->prefijo() . 'productos-*.xml') ?: [] as $viejo) {
            @unlink($viejo);
        }
        $ahora = date('c');
        foreach ($ficheros as $nombre => $urls) {
            file_put_contents($this->ruta($nombre), $this->xmlUrlset($urls), LOCK_EX);
        }
        file_put_contents($this->ruta('indice'), $this->xmlIndice(array_keys($ficheros), $ahora), LOCK_EX);

        $meta = [
            'generado' => time(),
            'paginas' => count($paginas),
            'categorias' => count($categorias),
            'marcas' => count($marcas),
            'productos' => count($productos),
            'ficheros_productos' => count($trozos),
        ];
        file_put_contents($this->rutaMeta(), json_encode($meta), LOCK_EX);

        if ($cerrojo) {
            flock($cerrojo, LOCK_UN);
            fclose($cerrojo);
        }

        return $meta;
    }

    /** Todas las URL del sitemap, para mandarlas a IndexNow. @return string[] */
    public function todasLasUrls(): array
    {
        $todas = [];
        foreach ([$this->urlsPaginas(), $this->urlsCategorias(), $this->urlsMarcas(), $this->urlsProductos()] as $grupo) {
            foreach ($grupo as $url) {
                $todas[] = $url['loc'];
            }
        }

        return $todas;
    }

    private function base(): string
    {
        return $this->ctx->link->getPageLink('index', true, $this->idLang);
    }

    /** @return array<int, array{loc: string, lastmod?: string, imagen?: string}> */
    private function urlsPaginas(): array
    {
        $urls = [['loc' => $this->base()]];
        if (Module::isEnabled('kpymanufacturer')) {
            $urls[] = ['loc' => $this->ctx->link->getModuleLink('kpymanufacturer', 'brands')];
        }
        $sql = 'SELECT c.id_cms, cl.link_rewrite
            FROM `' . _DB_PREFIX_ . 'cms` c
            INNER JOIN `' . _DB_PREFIX_ . 'cms_shop` cs ON cs.id_cms = c.id_cms AND cs.id_shop = ' . $this->idShop . '
            INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON cl.id_cms = c.id_cms AND cl.id_lang = ' . $this->idLang . ' AND cl.id_shop = ' . $this->idShop . '
            WHERE c.active = 1 AND c.indexation = 1
            ORDER BY c.id_cms';
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ?: [] as $fila) {
            $urls[] = ['loc' => $this->ctx->link->getCMSLink((int) $fila['id_cms'], (string) $fila['link_rewrite'], null, $this->idLang, $this->idShop)];
        }

        return $urls;
    }

    /** @return array<int, array{loc: string, lastmod?: string}> */
    private function urlsCategorias(): array
    {
        $urls = [];
        foreach (KseoCategorias::conProducto($this->idLang, $this->idShop) as $id => $datos) {
            $url = ['loc' => $this->ctx->link->getCategoryLink($id, $datos['rewrite'], $this->idLang, null, $this->idShop)];
            $lastmod = $this->fecha($datos['date_upd']);
            if ($lastmod !== '') {
                $url['lastmod'] = $lastmod;
            }
            $urls[] = $url;
        }

        return $urls;
    }

    /** @return array<int, array{loc: string}> */
    private function urlsMarcas(): array
    {
        if (!Module::isEnabled('kpymanufacturer')) {
            return [];
        }
        $urls = [];
        foreach (KseoMarcas::conLanding($this->idShop) as $rewrite) {
            $urls[] = ['loc' => $this->ctx->link->getModuleLink('kpymanufacturer', 'landing', ['brand' => $rewrite])];
        }

        return $urls;
    }

    /** @return array<int, array{loc: string, lastmod?: string, imagen?: string}> */
    private function urlsProductos(): array
    {
        $sql = 'SELECT p.id_product, pl.link_rewrite, cl.link_rewrite AS categoria, p.date_upd, i.id_image
            FROM `' . _DB_PREFIX_ . 'product` p
            INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.id_product = p.id_product AND ps.id_shop = ' . $this->idShop . '
                AND ps.active = 1 AND ps.visibility <> \'none\'
            INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.id_product = p.id_product AND pl.id_lang = ' . $this->idLang . ' AND pl.id_shop = ' . $this->idShop . '
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = ps.id_category_default AND cl.id_lang = ' . $this->idLang . ' AND cl.id_shop = ' . $this->idShop . '
            LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` i ON i.id_product = p.id_product AND i.id_shop = ' . $this->idShop . ' AND i.cover = 1
            ORDER BY p.id_product';
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ?: [];

        $dispatcher = Dispatcher::getInstance();
        $base = $this->base();
        // Todas las palabras clave que admite la ruta de producto, vacías: una que falte y esté en la
        // ruta da error, y una que sobre acaba en la query string.
        $plantilla = array_fill_keys(array_keys($dispatcher->default_routes['product_rule']['keywords'] ?? []), '');
        $urls = [];
        foreach ($filas as $fila) {
            $ruta = $dispatcher->createUrl('product_rule', $this->idLang, [
                'id' => (int) $fila['id_product'],
                'rewrite' => (string) $fila['link_rewrite'],
                'category' => (string) ($fila['categoria'] ?? ''),
            ] + $plantilla, false, '', $this->idShop);
            $url = ['loc' => $base . ltrim($ruta, '/')];
            $lastmod = $this->fecha((string) $fila['date_upd']);
            if ($lastmod !== '') {
                $url['lastmod'] = $lastmod;
            }
            if (!empty($fila['id_image'])) {
                $url['imagen'] = $this->ctx->link->getImageLink((string) $fila['link_rewrite'], $fila['id_product'] . '-' . $fila['id_image'], 'large_default');
            }
            $urls[] = $url;
        }

        return $urls;
    }

    private function fecha(string $mysql): string
    {
        if ($mysql === '' || str_starts_with($mysql, '0000')) {
            return '';
        }
        $t = strtotime($mysql);

        return $t ? date('c', $t) : '';
    }

    // ----------------------------------------------------------------- xml

    private function xmlUrlset(array $urls): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        foreach ($urls as $url) {
            $xml .= "  <url>\n    <loc>" . $this->esc($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . $this->esc($url['lastmod']) . "</lastmod>\n";
            }
            if (!empty($url['imagen'])) {
                $xml .= "    <image:image>\n      <image:loc>" . $this->esc($url['imagen']) . "</image:loc>\n    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }

        return $xml . "</urlset>\n";
    }

    private function xmlIndice(array $nombres, string $lastmod): string
    {
        $base = $this->base();
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($nombres as $nombre) {
            $xml .= "  <sitemap>\n    <loc>" . $this->esc($base . 'sitemap-' . $nombre . '.xml') . "</loc>\n    <lastmod>" . $this->esc($lastmod) . "</lastmod>\n  </sitemap>\n";
        }

        return $xml . "</sitemapindex>\n";
    }

    private function esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
