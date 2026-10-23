<?php
/**
 * La biblioteca de imágenes de los banners: logos y sacos **sin fondo**, hechos solos.
 *
 * Se alimenta sola. La primera vez que una promoción necesita la foto de un producto o el logo de
 * una marca, se coge la imagen grande que ya tiene la tienda (`img/p/…/{id_image}.jpg`,
 * `img/m/{id}.jpg`), se le quita el fondo (un servidor rembg si lo hay, remove.bg si hay clave, y si
 * no el recorte propio `KplRecorte`) y se deja en
 * `img/kpypromo/`, apuntada en `kpy_promo_imagen` con la firma del original: si la foto del
 * catálogo cambia, se vuelve a recortar; si no, se reutiliza para siempre. De cada foto de producto
 * queda apuntado si sirve de héroe (`apta`: un solo objeto, de pie) o por qué no (`objetos`).
 *
 * También guarda lo que se suba a mano, con su **tipo** (logo, saco, lata u otra foto), su marca, su
 * mascota y su gama. Si ya viene sin fondo (PNG o WebP transparente) solo se le quita el aire y se
 * escala a la medida estándar (logos 600 px de ancho, sacos y latas 900); si viene con fondo (un
 * JPG), se recorta como las automáticas. Un logo subido sustituye al automático de su marca; un saco
 * o una lata, a la foto del catálogo en los banners de esa marca (y mascota; y gama, si la promoción
 * la nombra). «Otra foto» solo se usa si se elige en el paso 6 de la promoción.
 *
 * Nunca lanza excepciones hacia fuera: si una imagen no sale, devuelve `null`, lo apunta en el
 * registro y el banner tira del logo o de nada.
 */
class KplBiblioteca
{
    public const CONF_REMOVEBG = 'KPL_REMOVEBG_KEY';
    /** Ajustes del motor rembg (dirección del servidor y modelo), vacíos si no se usa. */
    public const CONF_REMBG = 'KPL_REMBG_URL';
    public const CONF_REMBG_MODELO = 'KPL_REMBG_MODELO';
    public const REMBG_MODELOS = ['u2net', 'isnet-general-use', 'u2netp'];
    public const CARPETA = 'kpypromo';
    /** Los tipos de imagen subida a mano, con su nombre en la pantalla. */
    public const TIPOS = ['logo' => 'Logo', 'saco' => 'Saco', 'lata' => 'Lata', 'otra' => 'Otra foto'];
    /** El ancho estándar al que se deja cada tipo (más grande no hace falta; más pequeña no se agranda). */
    public const ANCHO = ['logo' => 600, 'saco' => 900, 'lata' => 900, 'otra' => 900];

    /** La carpeta en disco, creada si no existe (con su index.php). */
    public static function carpeta(): string
    {
        $dir = rtrim(_PS_IMG_DIR_, '/\\') . '/' . self::CARPETA . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (is_dir($dir) && !is_file($dir . 'index.php')) {
            @file_put_contents($dir . 'index.php', "<?php\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\nheader('Location: ../');\nexit;\n");
        }

        return $dir;
    }

    /** La dirección pública de un fichero de la biblioteca (vacía si no existe). */
    public static function url(string $fichero): string
    {
        if ($fichero === '' || !is_file(self::carpeta() . $fichero)) {
            return '';
        }
        // Sin pasar por Link (en consola no hay): el dominio de la tienda y su ruta base.
        $base = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__;

        return rtrim($base, '/') . '/img/' . self::CARPETA . '/' . rawurlencode($fichero) . '?v=' . (int) @filemtime(self::carpeta() . $fichero);
    }

    /**
     * El logo de una marca sin fondo: el subido a mano si lo hay; si no, el del catálogo recortado.
     * Devuelve la fila de la biblioteca (con `url`) o null.
     */
    public static function logo(int $idManufacturer): ?array
    {
        if ($idManufacturer <= 0) {
            return null;
        }
        $subido = self::subida($idManufacturer, 'logo');
        if ($subido !== null) {
            return $subido;
        }
        $origen = rtrim(_PS_MANU_IMG_DIR_, '/\\') . '/' . $idManufacturer . '.jpg';
        if (!is_file($origen)) {
            return null;
        }

        // Los logos, siempre con el recorte propio: a la inteligencia artificial (rembg, remove.bg) se
        // le van las letras finas de un logotipo; con un fondo blanco liso el propio los deja perfectos.
        return self::asegurar('marca', $idManufacturer, $origen, 'm-' . $idManufacturer . '.webp', [
            'id_manufacturer' => $idManufacturer,
            'ancho' => 600, 'margen' => 4, 'suave' => 3, 'tolerancia' => 20, 'motor' => 'propio',
        ]);
    }

    /**
     * La foto «héroe» de un producto sin fondo: un solo saco, de pie, sin flechas ni sacos viejos al
     * lado. Devuelve la fila de la biblioteca (con `url`) o null si no hay ninguna foto que valga.
     *
     * Se prueban las fotos de la ficha por este orden y se elige la primera que sirve (un objeto,
     * más alta que ancha):
     *   1. las fotos propias de los formatos preferidos (`$combos`: el saco Jirafa de los kilos
     *      gratis, el pack con regalo), es decir, las que están en esos formatos y en ningún otro;
     *   2. la portada de la ficha;
     *   3. el resto, por su orden.
     * Una foto «de transición» (saco viejo, flecha, saco nuevo) tiene 2 o 3 objetos y se descarta; un
     * rótulo o una lata (más ancha que alta) solo se usa si no hay nada mejor. Cada foto se recorta
     * una sola vez: la decisión queda apuntada en la biblioteca (`objetos`, `apta`).
     *
     * @param int[] $combos formatos preferidos (id_product_attribute)
     */
    public static function producto(int $idProducto, int $idManufacturer = 0, string $mascota = '', array $combos = []): ?array
    {
        if ($idProducto <= 0) {
            return null;
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $candidatas = [];
        $combos = array_values(array_filter(array_map('intval', $combos)));
        if ($combos !== []) {
            $lista = implode(',', $combos);
            foreach ($db->executeS(
                "SELECT DISTINCT i.id_image, i.position FROM {$p}image i
                 INNER JOIN {$p}product_attribute_image pai ON pai.id_image = i.id_image AND pai.id_product_attribute IN ({$lista})
                 WHERE i.id_product = " . (int) $idProducto . "
                   AND NOT EXISTS (SELECT 1 FROM {$p}product_attribute_image o WHERE o.id_image = i.id_image AND o.id_product_attribute NOT IN ({$lista}))
                 ORDER BY i.position"
            ) ?: [] as $f) {
                $candidatas[] = (int) $f['id_image'];
            }
        }
        foreach ($db->executeS("SELECT id_image FROM {$p}image WHERE id_product = " . (int) $idProducto . ' ORDER BY cover DESC, position ASC') ?: [] as $f) {
            $candidatas[] = (int) $f['id_image'];
        }
        $candidatas = array_slice(array_values(array_unique($candidatas)), 0, 6);
        $mejor = null;
        foreach ($candidatas as $idImagen) {
            $origen = rtrim(_PS_IMG_DIR_, '/\\') . '/p/' . Image::getImgFolderStatic($idImagen) . $idImagen . '.jpg';
            if (!is_file($origen)) {
                continue;
            }
            $fila = self::asegurar('producto', $idImagen, $origen, 'p-' . $idImagen . '.webp', [
                'id_product' => $idProducto,
                'id_manufacturer' => $idManufacturer,
                'mascota' => $mascota,
                'ancho' => 900, 'margen' => 3, 'suave' => 0, 'tolerancia' => 6,
            ]);
            if ($fila === null) {
                continue;
            }
            if ((int) $fila['apta'] === 1) {
                return $fila;
            }
            // Si ninguna vale del todo, la primera de un solo objeto (una lata, un rótulo); si no, la primera que haya.
            if ($mejor === null || ((int) $fila['objetos'] === 1 && (int) $mejor['objetos'] !== 1)) {
                $mejor = $fila;
            }
        }

        return $mejor;
    }

    /** Las imágenes subidas a mano (de la biblioteca), más recientes primero. */
    public static function subidas(int $idManufacturer = 0): array
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . "kpy_promo_imagen WHERE origen = 'subida'"
            . ($idManufacturer > 0 ? ' AND id_manufacturer = ' . $idManufacturer : '')
            . ' ORDER BY id_kpy_promo_imagen DESC'
        ) ?: [];
    }

    /**
     * La imagen subida a mano que toca: de esa marca y ese tipo (`logo`, `saco`, `lata`), la más
     * reciente que encaje. Para un saco o una lata, la de la mascota pedida (`perro`, `gato`) gana a
     * la de «perros y gatos» (mascota vacía); y una con gama (Mature, Puppy…) solo vale si `$texto`
     * (el nombre de la promoción, su título y los nombres de sus productos) nombra esa gama, y
     * entonces gana a las que no la llevan. Null si no hay ninguna.
     */
    public static function subida(int $idManufacturer, string $tipo, string $mascota = '', string $texto = ''): ?array
    {
        if ($idManufacturer <= 0) {
            return null;
        }
        $filas = Db::getInstance()->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . "kpy_promo_imagen WHERE origen = 'subida' AND id_manufacturer = " . $idManufacturer
            . " AND tipo = '" . pSQL($tipo) . "' ORDER BY id_kpy_promo_imagen DESC"
        ) ?: [];
        $textoLimpio = self::simplificar($texto);
        $mejor = null;
        $mejorPuntos = -1;
        foreach ($filas as $f) {
            $puntos = 0;
            if ((string) $f['mascota'] !== '') {
                if ((string) $f['mascota'] !== $mascota) {
                    continue;
                }
                ++$puntos;
            }
            if ((string) $f['gama'] !== '') {
                if ($textoLimpio === '' || !str_contains($textoLimpio, self::simplificar((string) $f['gama']))) {
                    continue;
                }
                $puntos += 2;
            }
            if ($puntos > $mejorPuntos && is_file(self::carpeta() . basename((string) $f['fichero']))) {
                $mejor = $f;
                $mejorPuntos = $puntos;
            }
        }
        if ($mejor !== null) {
            $mejor['url'] = self::url((string) $mejor['fichero']);
        }

        return $mejor;
    }

    /** ¿Hay sacos o latas subidos con gama para esta marca? (Si no, no hace falta juntar los nombres para compararlos.) */
    public static function conGama(int $idManufacturer): bool
    {
        return $idManufacturer > 0 && (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . "kpy_promo_imagen WHERE origen = 'subida' AND gama <> '' AND id_manufacturer = " . $idManufacturer
        ) > 0;
    }

    /** Minúsculas, sin acentos y con un solo espacio: para comparar una gama con los nombres. */
    private static function simplificar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = (string) Tools::replaceAccentedChars($s);

        return trim((string) preg_replace('/\s+/', ' ', $s));
    }

    /**
     * Cuánto más alto o más bajo que el estándar se pinta un logo para que todos pesen lo mismo a la
     * vista: un logotipo muy apaisado (Eukanuba, 9:1) a la misma altura que uno casi cuadrado (Alpha
     * Spirit, 1,6:1) parece diminuto. Se iguala el área con una referencia de 2,5:1: el factor es
     * raíz(2,5 / proporción), entre 0,55 y 1,35. Las plantillas lo ponen en `--kpl-logo-k` y el CSS
     * multiplica por él la altura de cada sitio.
     */
    public static function factorLogo(int $ancho, int $alto): float
    {
        if ($ancho <= 0 || $alto <= 0) {
            return 1.0;
        }

        return round(max(.55, min(1.35, sqrt(2.5 / ($ancho / $alto)))), 2);
    }

    /** Una fila de la biblioteca por su id (con `url`), o null. */
    public static function fila(int $id): ?array
    {
        $f = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_promo_imagen WHERE id_kpy_promo_imagen = ' . (int) $id);
        if (!$f) {
            return null;
        }
        $f['url'] = self::url((string) $f['fichero']);

        return $f['url'] !== '' ? $f : null;
    }

    /**
     * Guarda una imagen subida a mano con su tipo (`logo`, `saco`, `lata`, `otra`), su marca, su
     * mascota (vacía = perros y gatos) y su gama. Si ya viene sin fondo (PNG o WebP transparente) solo
     * se le quita el aire y se escala a la medida estándar del tipo; si viene con fondo (un JPG, o un
     * PNG opaco) se recorta como las automáticas: los logos con el motor propio, lo demás con el que
     * haya (rembg, remove.bg, el propio). `$tmp` es el fichero subido ya comprobado por quien llama.
     *
     * @return array{ok: bool, nota: string, id?: int, automatica?: bool} `automatica`: si se usa sola
     *                                                                     (logo, saco o lata de una marca) o solo desde el paso 6
     */
    public static function subir(string $tmp, string $nombreOriginal, int $idManufacturer, string $tipo, string $mascota, string $gama, string $titulo): array
    {
        $tipo = isset(self::TIPOS[$tipo]) ? $tipo : 'otra';
        $mascota = in_array($mascota, ['perro', 'gato'], true) ? $mascota : '';
        $gama = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $gama)), 0, 40);
        if ($tipo === 'logo') {
            $mascota = '';
            $gama = '';
        }
        if ($tipo !== 'otra' && $idManufacturer <= 0) {
            return ['ok' => false, 'nota' => 'un ' . mb_strtolower(self::TIPOS[$tipo]) . ' tiene que ser de una marca: elígela'];
        }
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return ['ok' => false, 'nota' => 'solo valen JPG, PNG o WebP'];
        }
        $fichero = 's-' . date('Ymd-His') . '-' . substr(md5($nombreOriginal . microtime(true)), 0, 6) . '.webp';
        $ruta = self::carpeta() . $fichero;
        $ancho = self::ANCHO[$tipo];
        $transparente = $info[2] !== IMAGETYPE_JPEG && self::conTransparencia($tmp, $info[2]);
        if ($transparente) {
            // Ya viene sin fondo: solo se le quita el aire que traiga y se escala.
            $recorte = new KplRecorte();
            $recorte->maxAncho = $ancho;
            $recorte->margen = $tipo === 'logo' ? 4 : 3;
            $resultado = $recorte->recortar($tmp, $ruta);
            $motor = 'propio';
        } else {
            [$resultado, $motor] = self::conMotores($tmp, $ruta, $tipo === 'logo'
                ? ['ancho' => $ancho, 'margen' => 4, 'suave' => 3, 'tolerancia' => 20, 'motor' => 'propio']
                : ['ancho' => $ancho, 'margen' => 3]);
        }
        if (!$resultado['ok']) {
            return ['ok' => false, 'nota' => $resultado['nota']];
        }
        $nota = $transparente ? 'ya venía sin fondo' : 'recortada con ' . $motor;
        Db::getInstance()->insert('kpy_promo_imagen', [
            'origen' => 'subida',
            'id_ref' => 0,
            'id_product' => 0,
            'id_manufacturer' => (int) $idManufacturer,
            'mascota' => pSQL($mascota),
            'tipo' => pSQL($tipo),
            'gama' => pSQL($gama),
            'fichero' => pSQL($fichero),
            'firma' => pSQL(md5_file($tmp) ?: ''),
            'ancho' => (int) $resultado['ancho'],
            'alto' => (int) $resultado['alto'],
            'bytes' => (int) $resultado['bytes'],
            'objetos' => (int) $resultado['objetos'],
            'apta' => 1,
            'motor' => pSQL($motor),
            'nota' => pSQL(mb_substr($titulo !== '' ? $titulo . ' · ' . $nota : $nota, 0, 255)),
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok' => true,
            'nota' => $nota . ', ' . (int) $resultado['ancho'] . '×' . (int) $resultado['alto'] . ' px',
            'id' => (int) Db::getInstance()->Insert_ID(),
            'automatica' => $tipo !== 'otra',
        ];
    }

    /** ¿Trae píxeles transparentes de verdad? (Una rejilla de 80 × 80 muestras; como en KplRecorte.) */
    private static function conTransparencia(string $ruta, int $tipo): bool
    {
        $img = $tipo === IMAGETYPE_PNG ? @imagecreatefrompng($ruta) : @imagecreatefromwebp($ruta);
        if (!$img) {
            return false;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $muestras = 0;
        $conAlfa = 0;
        $px = max(1, intdiv($w, 80));
        $py = max(1, intdiv($h, 80));
        for ($y = 0; $y < $h; $y += $py) {
            for ($x = 0; $x < $w; $x += $px) {
                ++$muestras;
                if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 8) {
                    ++$conAlfa;
                }
            }
        }
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($img);
        }

        return $conAlfa > $muestras * 0.02;
    }

    /** Borra una imagen subida (solo las subidas a mano; las automáticas se rehacen solas). */
    public static function borrar(int $id): bool
    {
        $f = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_promo_imagen WHERE id_kpy_promo_imagen = ' . (int) $id);
        if (!$f) {
            return false;
        }
        $ruta = self::carpeta() . basename((string) $f['fichero']);
        if (is_file($ruta)) {
            @unlink($ruta);
        }

        return (bool) Db::getInstance()->delete('kpy_promo_imagen', 'id_kpy_promo_imagen = ' . (int) $id);
    }

    /** Borra todos los ficheros y filas automáticos (para rehacerlos) o todo (al desinstalar del todo). */
    public static function vaciar(bool $tambienSubidas = false): int
    {
        $filas = Db::getInstance()->executeS(
            'SELECT id_kpy_promo_imagen, fichero FROM ' . _DB_PREFIX_ . 'kpy_promo_imagen' . ($tambienSubidas ? '' : " WHERE origen <> 'subida'")
        ) ?: [];
        foreach ($filas as $f) {
            $ruta = self::carpeta() . basename((string) $f['fichero']);
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
        Db::getInstance()->delete('kpy_promo_imagen', $tambienSubidas ? '1' : "origen <> 'subida'");
        if ($tambienSubidas) {
            $dir = self::carpeta();
            @unlink($dir . 'index.php');
            @rmdir($dir);
        }

        return count($filas);
    }

    // ---------------------------------------------------------------------------------------

    /**
     * La imagen recortada de un origen, haciéndola si no está o si el original ha cambiado.
     * Devuelve la fila de la biblioteca (con `url`, `objetos` y `apta`) o null si no ha salido.
     *
     * @param array{id_product?: int, id_manufacturer?: int, mascota?: string, ancho?: int, margen?: float, suave?: int, tolerancia?: int} $o
     */
    private static function asegurar(string $origen, int $idRef, string $ficheroOrigen, string $fichero, array $o): ?array
    {
        $db = Db::getInstance();
        $firma = md5_file($ficheroOrigen) ?: '';
        $fila = $db->getRow('SELECT * FROM ' . _DB_PREFIX_ . "kpy_promo_imagen WHERE origen = '" . pSQL($origen) . "' AND id_ref = " . $idRef);
        $ruta = self::carpeta() . $fichero;
        if ($fila && (string) $fila['firma'] === $firma && is_file($ruta)) {
            if ($fila['nota'] === 'sin fondo blanco' || (int) $fila['bytes'] <= 0) {
                return null; // ya se intentó con este mismo original y no salió: no se insiste
            }
            $fila['url'] = self::url($fichero);

            return $fila;
        }

        [$resultado, $motor] = self::conMotores($ficheroOrigen, $ruta, $o);
        $nota = $resultado['ok'] ? $resultado['nota'] : (str_contains($resultado['nota'], 'fondo blanco') ? 'sin fondo blanco' : 'ERROR: ' . $resultado['nota']);
        // ¿Sirve de héroe? Para una foto de producto: un solo objeto y más alta que ancha. Logos y subidas, siempre.
        $apta = 1;
        if ($origen === 'producto' && $resultado['ok']) {
            $apta = (int) $resultado['objetos'] === 1 && (int) $resultado['alto'] >= 1.15 * (int) $resultado['ancho'] ? 1 : 0;
            if ($apta === 0) {
                $nota .= (int) $resultado['objetos'] !== 1 ? ' · descartada: varios objetos (transición, flecha, dos sacos)' : ' · descartada: más ancha que alta (rótulo, lata)';
            }
        }
        $campos = [
            'origen' => pSQL($origen),
            'id_ref' => $idRef,
            'id_product' => (int) ($o['id_product'] ?? 0),
            'id_manufacturer' => (int) ($o['id_manufacturer'] ?? 0),
            'mascota' => pSQL((string) ($o['mascota'] ?? '')),
            'fichero' => pSQL($fichero),
            'firma' => pSQL($firma),
            'ancho' => (int) $resultado['ancho'],
            'alto' => (int) $resultado['alto'],
            'bytes' => (int) $resultado['bytes'],
            'objetos' => (int) ($resultado['objetos'] ?? 0),
            'apta' => $apta,
            'motor' => pSQL($motor),
            'nota' => pSQL(mb_substr($nota, 0, 255)),
            'date_add' => date('Y-m-d H:i:s'),
        ];
        if ($fila) {
            $db->update('kpy_promo_imagen', $campos, 'id_kpy_promo_imagen = ' . (int) $fila['id_kpy_promo_imagen']);
            $campos['id_kpy_promo_imagen'] = (int) $fila['id_kpy_promo_imagen'];
        } else {
            $db->insert('kpy_promo_imagen', $campos);
            $campos['id_kpy_promo_imagen'] = (int) $db->Insert_ID();
        }
        if (!$resultado['ok']) {
            if (!str_contains($resultado['nota'], 'fondo blanco')) {
                PrestaShopLogger::addLog('kpypromolanding, recorte de ' . basename($ficheroOrigen) . ': ' . $resultado['nota'], 2, null, 'KplBiblioteca', $idRef, true);
            }

            return null;
        }
        $campos['url'] = self::url($fichero);

        return $campos;
    }

    /**
     * Recorta con el motor que haya, por este orden: rembg (si hay servidor), remove.bg (si hay
     * clave) y el propio; si uno falla, el siguiente. Con `motor` = `propio` en `$o`, solo el propio
     * (los logos: a la inteligencia artificial se le van las letras finas).
     *
     * @param array{ancho?: int, margen?: float, suave?: int, tolerancia?: int, motor?: string} $o
     *
     * @return array{0: array, 1: string} el resultado de `KplRecorte::recortar()` y el motor que ha sido
     */
    private static function conMotores(string $ficheroOrigen, string $ruta, array $o): array
    {
        $motor = 'propio';
        $resultado = null;
        $soloPropio = ($o['motor'] ?? '') === 'propio';
        $rembg = $soloPropio ? '' : self::rembgUrl();
        if ($rembg !== '') {
            $resultado = self::conRembg($rembg, self::rembgModelo(), $ficheroOrigen, $ruta, (int) ($o['ancho'] ?? 900), (float) ($o['margen'] ?? 3));
            if ($resultado !== null && $resultado['ok']) {
                $motor = 'rembg';
            } else {
                $resultado = null;
            }
        }
        $clave = $soloPropio ? '' : trim((string) Configuration::getGlobalValue(self::CONF_REMOVEBG));
        if ($resultado === null && $clave !== '') {
            $resultado = self::conRemoveBg($clave, $ficheroOrigen, $ruta, (int) ($o['ancho'] ?? 900), (float) ($o['margen'] ?? 3));
            if ($resultado !== null && $resultado['ok']) {
                $motor = 'remove.bg';
            } else {
                $resultado = null; // sin crédito, sin red o con error: el propio
            }
        }
        if ($resultado === null) {
            $recorte = new KplRecorte();
            $recorte->maxAncho = (int) ($o['ancho'] ?? 900);
            $recorte->margen = (float) ($o['margen'] ?? 3);
            $recorte->suave = (int) ($o['suave'] ?? 0);
            $recorte->tolerancia = (int) ($o['tolerancia'] ?? 6);
            $resultado = $recorte->recortar($ficheroOrigen, $ruta);
        }

        return [$resultado, $motor];
    }

    /** La dirección del servidor rembg configurada, sin barra final (vacía si no hay). */
    public static function rembgUrl(): string
    {
        $url = trim((string) Configuration::getGlobalValue(self::CONF_REMBG));

        return $url !== '' && preg_match('#^https?://#i', $url) ? rtrim($url, '/') : '';
    }

    /** El modelo de rembg configurado (u2net si no hay nada). */
    public static function rembgModelo(): string
    {
        $m = trim((string) Configuration::getGlobalValue(self::CONF_REMBG_MODELO));

        return in_array($m, self::REMBG_MODELOS, true) ? $m : 'u2net';
    }

    /**
     * Comprueba que un servidor rembg contesta: le manda una imagen pequeña y mira que devuelve un PNG.
     *
     * @return array{ok: bool, nota: string}
     */
    public static function probarRembg(string $url, string $modelo): array
    {
        $url = rtrim(trim($url), '/');
        if (!preg_match('#^https?://#i', $url)) {
            return ['ok' => false, 'nota' => 'la dirección tiene que empezar por http:// o https://'];
        }
        $tmp = tempnam(sys_get_temp_dir(), 'kpl');
        $img = imagecreatetruecolor(120, 160);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imagefilledrectangle($img, 30, 20, 90, 140, imagecolorallocate($img, 40, 60, 120));
        imagejpeg($img, $tmp, 90);
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($img);
        }
        $t = microtime(true);
        [$codigo, $cuerpo] = self::pedirRembg($url, $modelo, $tmp, 60);
        @unlink($tmp);
        if ($codigo !== 200 || !is_string($cuerpo) || strncmp($cuerpo, "\x89PNG", 4) !== 0) {
            return ['ok' => false, 'nota' => 'no contesta bien (código ' . $codigo . ($cuerpo !== null && strlen($cuerpo) < 300 ? ': ' . trim(strip_tags($cuerpo)) : '') . ')'];
        }

        return ['ok' => true, 'nota' => sprintf('contesta (modelo %s, %.1f s)', $modelo, microtime(true) - $t)];
    }

    /** @return array{0: int, 1: ?string} código HTTP y cuerpo */
    private static function pedirRembg(string $url, string $modelo, string $fichero, int $tiempo): array
    {
        if (!function_exists('curl_init')) {
            return [0, null];
        }
        // El modelo va como campo del formulario: en el POST el servidor no mira la query (y sin él
        // descarga su modelo por defecto, de 1 GB).
        $ch = curl_init($url . '/api/remove?model=' . rawurlencode($modelo));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $tiempo,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POSTFIELDS => ['file' => new CURLFile($fichero), 'model' => $modelo],
        ]);
        $cuerpo = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [$codigo, is_string($cuerpo) ? $cuerpo : null];
    }

    /**
     * El recorte con un servidor rembg propio (https://github.com/danielgatis/rembg: inteligencia
     * artificial, gratis, en un contenedor junto a la tienda). Devuelve un PNG sin fondo, que se
     * recorta y se escala igual que los nuestros. Null si no contesta: entonces el siguiente motor.
     */
    private static function conRembg(string $url, string $modelo, string $ficheroOrigen, string $ruta, int $ancho, float $margen): ?array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'kpl');
        if ($tmp === false) {
            return null;
        }
        try {
            [$codigo, $cuerpo] = self::pedirRembg($url, $modelo, $ficheroOrigen, 180);
            if ($codigo !== 200 || $cuerpo === null || strncmp($cuerpo, "\x89PNG", 4) !== 0) {
                PrestaShopLogger::addLog('kpypromolanding, rembg ha contestado ' . $codigo . ' para ' . basename($ficheroOrigen) . ': se usa otro motor', 2, null, 'KplBiblioteca', 0, true);

                return null;
            }
            file_put_contents($tmp, $cuerpo);
            $recorte = new KplRecorte();
            $recorte->maxAncho = $ancho;
            $recorte->margen = $margen;
            $resultado = $recorte->recortar($tmp, $ruta);

            return $resultado['ok'] ? $resultado : null;
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, rembg: ' . $e->getMessage(), 2, null, 'KplBiblioteca', 0, true);

            return null;
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * El recorte con remove.bg (https://www.remove.bg/api): se manda la foto y devuelve un PNG sin
     * fondo, que se recorta y se escala igual que los nuestros. Devuelve null si la API no contesta
     * bien (sin crédito, clave mala, sin red): entonces se usa el motor propio.
     */
    private static function conRemoveBg(string $clave, string $ficheroOrigen, string $ruta, int $ancho, float $margen): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'kpl');
        if ($tmp === false) {
            return null;
        }
        try {
            $ch = curl_init('https://api.remove.bg/v1.0/removebg');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_HTTPHEADER => ['X-Api-Key: ' . $clave],
                CURLOPT_POSTFIELDS => [
                    'image_file' => new CURLFile($ficheroOrigen),
                    'size' => 'auto',
                    'format' => 'png',
                    'type' => 'product',
                ],
            ]);
            $cuerpo = curl_exec($ch);
            $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if (!is_string($cuerpo) || $codigo !== 200 || strlen($cuerpo) < 100) {
                PrestaShopLogger::addLog('kpypromolanding, remove.bg ha contestado ' . $codigo . ' para ' . basename($ficheroOrigen) . ': se usa el recorte propio', 2, null, 'KplBiblioteca', 0, true);

                return null;
            }
            file_put_contents($tmp, $cuerpo);
            // El PNG de remove.bg ya viene transparente: KplRecorte solo le quita el aire y lo escala.
            $recorte = new KplRecorte();
            $recorte->maxAncho = $ancho;
            $recorte->margen = $margen;
            $resultado = $recorte->recortar($tmp, $ruta);

            return $resultado['ok'] ? $resultado : null;
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, remove.bg: ' . $e->getMessage(), 2, null, 'KplBiblioteca', 0, true);

            return null;
        } finally {
            @unlink($tmp);
        }
    }
}
