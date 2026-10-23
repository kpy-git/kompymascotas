<?php
/**
 * La pestaña Catálogo → «Kompy · Landing de promociones». Ordenada como se usa:
 *
 *   0. Cómo funciona: las promociones se crean en Kompy · Promociones; este módulo hace solo lo
 *      suyo al guardar (categoría, banner, foto, landing); aquí se mira y se afina.
 *   1. Las promociones en la landing: cada una con su estado, su banner y lo que se afinó en su
 *      paso 6 (con el enlace para cambiarlo allí).
 *   2. Las campañas (Black Friday…), con su formulario por pasos.
 *   3. Las imágenes: el motor de recorte y la biblioteca (logos, sacos, subidas a mano).
 *   4. Mantenimiento: sincronizar, rehacer imágenes, la foto de las tarjetas en toda la tienda,
 *      borrar todo.
 */
class AdminKpyPromoLandingController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = 'Kompy · Landing de promociones';
    }

    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);
        $this->addCSS(_MODULE_DIR_ . 'kpypromolanding/views/css/admin.css?v=' . $this->module->version);
    }

    public function initContent(): void
    {
        $url = $this->context->link->getAdminLink('AdminKpyPromoLanding');
        $idTienda = (int) $this->context->shop->id;
        $this->procesar($url, $idTienda);
        $this->context->smarty->assign(['kpl' => $this->datosPantalla($url, $idTienda)]);
        $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'kpypromolanding/views/templates/admin/pantalla.tpl');
        parent::initContent();
    }

    /** Todo lo que pinta la pantalla (también lo usa la vista previa en consola, sin sesión del back office). */
    public function datosPantalla(string $url, int $idTienda): array
    {
        $resumen = KplPromos::resumen();
        $categorias = KplCategorias::todas();
        $marcas = Db::getInstance()->executeS('SELECT id_manufacturer, name FROM ' . _DB_PREFIX_ . 'manufacturer WHERE active = 1 ORDER BY name') ?: [];
        $nombresMarca = array_column($marcas, 'name', 'id_manufacturer');
        $imagenes = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_promo_imagen ORDER BY origen, id_kpy_promo_imagen DESC') ?: [];
        foreach ($imagenes as $i => $im) {
            $imagenes[$i]['url'] = KplBiblioteca::url((string) $im['fichero']);
            $imagenes[$i]['marca_nombre'] = (string) ($nombresMarca[(int) $im['id_manufacturer']] ?? '');
            $imagenes[$i]['tipo_nombre'] = KplBiblioteca::TIPOS[(string) ($im['tipo'] ?? '')] ?? 'Otra foto';
        }
        $nImagenesOk = count(array_filter($imagenes, static fn (array $im): bool => $im['url'] !== ''));
        // Las subidas a mano (las que gestiona Rafa) por un lado y las hechas solas por otro.
        $subidas = array_values(array_filter($imagenes, static fn (array $im): bool => $im['origen'] === 'subida'));
        $automaticas = array_values(array_filter($imagenes, static fn (array $im): bool => $im['origen'] !== 'subida'));
        $campanas = [];
        foreach (KplCampanas::todas() as $id => $c) {
            $campanas[$id] = $c + KplCampanas::textos($c) + ['url_front' => KplCampanas::url($c)];
        }
        $idCampana = (int) Tools::getValue('campana', 0);
        $campana = $campanas[$idCampana] ?? ($idCampana === -1 ? null : (reset($campanas) ?: null));
        if ($campana === null) {
            // Una nueva, con los valores por defecto.
            $campana = ['id_kpy_promo_campana' => 0, 'clave' => '', 'nombre' => '', 'activa' => 1, 'desde' => '', 'hasta' => '', 'piel' => 'negra', 'titulo' => '', 'texto' => '', 'lema' => 'Stock limitado · hasta agotar existencias', 'texto_antes' => '', 'texto_despues' => '', 'cinta' => 1, 'cinta_texto' => '', 'redirigir' => 1, 'sin_carrusel' => 1, 'etiquetas' => KplCampanas::etiquetasPorDefecto(), 'colores' => KplCampanas::paletaLimpia([], 'negra'), 'orden_marcas' => '', 'estado' => 'nueva', 'fechas' => '', 'quedan_texto' => '', 'faltan_texto' => ''];
        }
        $campana['colores'] = KplCampanas::coloresDe($campana);
        $campana['desde_fecha'] = $campana['desde'] ? substr((string) $campana['desde'], 0, 10) : '';
        $campana['desde_hora'] = $campana['desde'] ? substr((string) $campana['desde'], 11, 5) : '';
        $campana['hasta_fecha'] = $campana['hasta'] ? substr((string) $campana['hasta'], 0, 10) : '';
        $campana['hasta_hora'] = $campana['hasta'] ? substr((string) $campana['hasta'], 11, 5) : '';

        $contestadas = KplPromociones::todas($idTienda);
        $promos = $this->promociones($contestadas ?? [], $resumen, $categorias);
        $cuentas = ['viva' => 0, 'programada' => 0, 'terminada' => 0, 'apagada' => 0];
        foreach ($promos as $pr) {
            ++$cuentas[$pr['estado']];
        }
        $vivasEnLanding = count(array_filter($resumen['claves'], static fn (array $c): bool => $c['estado'] === 'viva' && (int) $c['listar'] === 1 && empty($c['permanente'])));
        $campanaViva = KplCampanas::viva();

        return [
            'url' => $url,
            'url_promociones' => $this->context->link->getAdminLink('AdminKpyPromociones'),
            'url_landing' => KplPromos::urlLanding(),
            'version' => $this->module->version,
            'aviso' => (string) Tools::getValue('aviso'),
            'error' => (string) Tools::getValue('error'),
            'fecha' => (string) ($resumen['fecha'] ?? ''),
            'hace' => $this->hace((string) ($resumen['fecha'] ?? '')),
            'pendiente' => (int) Configuration::getGlobalValue(Kpypromolanding::CONF_PENDIENTE) === 1,
            // Nadie contesta al hook de consulta de las promociones: kpycoupons apagado o anterior a la 3.0.0.
            'sin_kpycoupons' => $contestadas === null,
            'hook_promociones' => KplPromociones::HOOK,
            'n_landing' => $vivasEnLanding,
            'n_categorias' => count($categorias),
            'n_imagenes' => $nImagenesOk,
            'cuentas' => $cuentas,
            'promos' => $promos,
            'permanentes' => $this->permanentes($resumen, $categorias),
            'campanas' => $campanas,
            'campana' => $campana,
            'campana_viva' => $campanaViva ? $campanaViva['nombre'] : '',
            'pieles' => KplCampanas::PIELES,
            // Las paletas preparadas (las de las campañas predefinidas), para ponerlas de un clic.
            'paletas' => array_map(static fn (array $p): array => ['nombre' => $p['nombre'], 'piel' => $p['piel'], 'colores' => $p['colores']], KplCampanas::predefinidas()),
            'faltan_predefinidas' => count(array_filter(array_keys(KplCampanas::predefinidas()), static fn (string $k): bool => KplCampanas::porClave($k) === null)),
            'marcas_prioridad' => (string) Configuration::getGlobalValue(Kpypromolanding::CONF_MARCAS),
            'portada_n' => (int) Configuration::getGlobalValue(Kpypromolanding::CONF_PORTADA_N),
            'tipos_etiqueta' => KplCampanas::TIPOS_ETIQUETA,
            'nombres_tipo' => KplClaves::NOMBRE_TIPO,
            'imagenes' => $imagenes,
            'subidas' => $subidas,
            'automaticas' => $automaticas,
            'tipos_imagen' => KplBiblioteca::TIPOS,
            'n_fotos' => (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . KplHeroes::TABLA),
            'n_fotos_descartadas' => (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . KplHeroes::TABLA . " WHERE apta = 0 AND nota <> 'sin fondo blanco'"),
            'marcas' => $marcas,
            'removebg' => (string) Configuration::getGlobalValue(KplBiblioteca::CONF_REMOVEBG),
            'rembg' => (string) Configuration::getGlobalValue(KplBiblioteca::CONF_REMBG),
            'rembg_modelo' => KplBiblioteca::rembgModelo(),
            'rembg_modelos' => KplBiblioteca::REMBG_MODELOS,
            'heroe_tienda' => KplHeroes::enTodaLaTienda(),
            'raiz' => (int) Configuration::getGlobalValue(KplCategorias::CONF_RAIZ),
        ];
    }

    /** Lo que llega por POST: cada acción hace lo suyo y vuelve a la pantalla con su aviso. */
    private function procesar(string $url, int $idTienda): void
    {
        $volver = static function (string $texto, bool $error = false, string $extra = '') use ($url): void {
            Tools::redirectAdmin($url . $extra . '&' . ($error ? 'error' : 'aviso') . '=' . urlencode($texto));
        };

        if (Tools::isSubmit('kplSincronizar')) {
            $r = $this->module->sincronizar($idTienda, true);
            $volver($r === null
                ? 'No se ha podido sincronizar (otra sincronización en marcha, o un error: mira el registro).'
                : sprintf('Hecho: %d categorías, %d productos colocados, %d imágenes listas.', $r['claves'], $r['productos'], $r['imagenes']) . ($r['avisos'] ? ' Avisos: ' . implode(' · ', $r['avisos']) : ''), $r === null);
        }
        if (Tools::isSubmit('kplRehacerImagenes')) {
            $n = KplBiblioteca::vaciar(false);
            Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . KplHeroes::TABLA);
            $r = $this->module->sincronizar($idTienda, true);
            $volver(sprintf('%d imágenes automáticas borradas y %d rehechas; las fotos de las tarjetas, medidas de nuevo.', $n, $r['imagenes'] ?? 0));
        }
        if (Tools::isSubmit('kplGuardarClave') || Tools::isSubmit('kplProbarRembg')) {
            $clave = trim((string) Tools::getValue('removebg'));
            $rembg = rtrim(trim((string) Tools::getValue('rembg')), '/');
            $modelo = (string) Tools::getValue('rembg_modelo');
            if ($clave !== '' && !preg_match('/^[A-Za-z0-9]{10,64}$/', $clave)) {
                $volver('La clave de remove.bg no tiene buena pinta: letras y números, sin espacios.', true);
            }
            if ($rembg !== '' && !preg_match('#^https?://[A-Za-z0-9.\-]+(:\d+)?(/[A-Za-z0-9./\-_]*)?$#', $rembg)) {
                $volver('La dirección de rembg tiene que ser como http://rembg:7000.', true);
            }
            if (!in_array($modelo, KplBiblioteca::REMBG_MODELOS, true)) {
                $modelo = 'u2net';
            }
            Configuration::updateGlobalValue(KplBiblioteca::CONF_REMOVEBG, $clave);
            Configuration::updateGlobalValue(KplBiblioteca::CONF_REMBG, $rembg);
            Configuration::updateGlobalValue(KplBiblioteca::CONF_REMBG_MODELO, $modelo);
            if (Tools::isSubmit('kplProbarRembg')) {
                $r = $rembg === '' ? ['ok' => false, 'nota' => 'no hay dirección de rembg'] : KplBiblioteca::probarRembg($rembg, $modelo);
                $volver($r['ok'] ? 'rembg ' . $r['nota'] . '. Guardado.' : 'rembg: ' . $r['nota'] . '. Guardado igualmente; mientras no conteste se usa el siguiente motor.', !$r['ok']);
            }
            $motor = $rembg !== '' ? 'rembg (y si falla, ' . ($clave !== '' ? 'remove.bg y luego ' : '') . 'el propio)' : ($clave !== '' ? 'remove.bg (y si falla, el propio)' : 'el recorte propio');
            $volver('Guardado. Las imágenes nuevas se recortan con ' . $motor . '. Para rehacer las que ya hay: «Rehacer las imágenes».');
        }
        if (Tools::isSubmit('kplGuardarAjustes')) {
            Configuration::updateGlobalValue(KplHeroes::CONF_TIENDA, Tools::getValue('heroe_tienda') ? 1 : 0);
            $volver(Tools::getValue('heroe_tienda') ? 'Guardado: la foto de las tarjetas (un solo saco de pie) se aplica también en los listados de toda la tienda.' : 'Guardado: la foto de las tarjetas solo se aplica en las páginas de la landing.');
        }
        if (Tools::isSubmit('kplBorrarTodo') && Tools::getValue('confirmar') === 'BORRAR') {
            $n = KplCategorias::borrarTodas();
            KplBiblioteca::vaciar(true);
            Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'kpy_promo_promo');
            Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'kpy_promo_dato');
            Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . KplHeroes::TABLA);
            $volver(sprintf('Borrado todo: %d categorías, las imágenes y los datos. Las campañas y las promociones de Kompy · Promociones no se tocan. Al sincronizar se vuelve a crear lo que haga falta.', $n));
        }
        if (Tools::isSubmit('kplSubir') && isset($_FILES['imagen']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
            $f = $_FILES['imagen'];
            if ((int) $f['size'] > 4 * 1024 * 1024) {
                $volver('La imagen pesa más de 4 MB.', true);
            }
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $volver('Solo valen JPG, PNG o WebP.', true);
            }
            $tipo = (string) Tools::getValue('tipo', 'otra');
            $gama = trim((string) Tools::getValue('gama'));
            $r = KplBiblioteca::subir($f['tmp_name'], (string) $f['name'], (int) Tools::getValue('id_manufacturer'), $tipo, (string) Tools::getValue('mascota'), $gama, trim((string) Tools::getValue('titulo')));
            if (!$r['ok']) {
                $volver('No se ha podido: ' . $r['nota'], true);
            }
            $aviso = 'Guardado: ' . mb_strtolower(KplBiblioteca::TIPOS[$tipo] ?? 'imagen') . ' (' . $r['nota'] . ').';
            if (!empty($r['automatica'])) {
                // Un logo o un saco de marca se usan solos: se vuelve a sincronizar para que los banners lo cojan ya.
                $s = $this->module->sincronizar($idTienda, true);
                if ($s === null) {
                    $aviso .= ' No se ha podido sincronizar ahora: pulsa «Sincronizar ahora» para que salga.';
                } elseif ($tipo === 'logo') {
                    $aviso .= ' Ya es el logo de la marca en toda la landing.';
                } elseif ($gama !== '') {
                    $aviso .= ' Sale solo en las promociones de la marca que nombren «' . $gama . '»; en las demás, elígelo en el paso 6.';
                } else {
                    $aviso .= ' Ya sale en los banners de la marca.';
                }
            } else {
                $aviso .= ' Para usarla en un banner: paso 6 de la promoción, «una imagen subida a mano».';
            }
            $volver($aviso);
        }
        if (Tools::isSubmit('kplBorrarImagen')) {
            KplBiblioteca::borrar((int) Tools::getValue('id'));
            $volver('Imagen borrada.');
        }
        if (Tools::isSubmit('kplGuardarPortada')) {
            Configuration::updateGlobalValue(Kpypromolanding::CONF_MARCAS, mb_substr(trim((string) Tools::getValue('marcas_prioridad')), 0, 255));
            Configuration::updateGlobalValue(Kpypromolanding::CONF_PORTADA_N, max(0, min(16, (int) Tools::getValue('portada_n'))));
            $volver('Guardado: la portada de la tienda durante una campaña.');
        }
        if (Tools::isSubmit('kplCrearPredefinidas')) {
            $n = KplCampanas::crearPredefinidas();
            KplPromos::olvidar();
            $volver($n > 0 ? sprintf('Creadas %d campañas predefinidas, con sus fechas y su paleta. Cámbiales lo que quieras.', $n) : 'Las campañas predefinidas ya estaban todas.');
        }
        if (Tools::isSubmit('kplGuardarCampana')) {
            $etiquetas = [];
            foreach (KplCampanas::TIPOS_ETIQUETA as $tipo) {
                $etiquetas[$tipo] = [
                    'nombre' => (string) Tools::getValue('et_nombre_' . $tipo),
                    'fondo' => (string) Tools::getValue('et_fondo_' . $tipo),
                    'texto' => (string) Tools::getValue('et_texto_' . $tipo),
                ];
            }
            $id = KplCampanas::guardar([
                'id_kpy_promo_campana' => (int) Tools::getValue('id_kpy_promo_campana'),
                'clave' => (string) Tools::getValue('clave'),
                'nombre' => (string) Tools::getValue('nombre'),
                'activa' => (int) Tools::getValue('activa'),
                'desde' => $this->fechaHora((string) Tools::getValue('desde_fecha'), (string) Tools::getValue('desde_hora'), '00:00:00'),
                'hasta' => $this->fechaHora((string) Tools::getValue('hasta_fecha'), (string) Tools::getValue('hasta_hora'), '23:59:59'),
                'piel' => (string) Tools::getValue('piel'),
                'titulo' => (string) Tools::getValue('titulo'),
                'texto' => (string) Tools::getValue('texto'),
                'lema' => (string) Tools::getValue('lema'),
                'texto_antes' => (string) Tools::getValue('texto_antes'),
                'texto_despues' => (string) Tools::getValue('texto_despues'),
                'cinta' => (int) Tools::getValue('cinta'),
                'cinta_texto' => (string) Tools::getValue('cinta_texto'),
                'redirigir' => (int) Tools::getValue('redirigir'),
                'sin_carrusel' => (int) Tools::getValue('sin_carrusel'),
                'etiquetas' => $etiquetas,
                'colores' => ['fondo' => (string) Tools::getValue('color_fondo'), 'acento' => (string) Tools::getValue('color_acento'), 'acento2' => (string) Tools::getValue('color_acento2')],
                'orden_marcas' => (string) Tools::getValue('orden_marcas'),
            ]);
            if ($id <= 0) {
                $volver('La dirección de la campaña no vale: letras y números, sin espacios, y que no sea una dirección de la landing (todas, cupones, perros…).', true, '&campana=-1');
            }
            KplPromos::olvidar();
            $volver('Campaña guardada. Se enciende y se apaga sola entre sus fechas.', false, '&campana=' . $id);
        }
        if (Tools::isSubmit('kplBorrarCampana')) {
            KplCampanas::borrar((int) Tools::getValue('id_kpy_promo_campana'));
            $volver('Campaña borrada.');
        }
    }

    /**
     * Las promociones de Kompy · Promociones (las que contesta el hook de consulta de kpycoupons,
     * `KplPromociones`) con lo que la landing hace con cada una: su estado, su clave y dirección, su
     * banner (logo y sacos), lo afinado en el paso 6 (`kpy_promo_promo`) y el enlace para cambiarlo.
     * El estado lo calcula kpycoupons; una agotada se enseña como terminada.
     *
     * @param array<int, array> $contestadas las filas públicas, por id
     */
    private function promociones(array $contestadas, array $resumen, array $categorias): array
    {
        $nuestras = [];
        foreach (Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_promo_promo') ?: [] as $pp) {
            $nuestras[(int) $pp['id_kpy_coupon']] = $pp;
        }
        $urlPromociones = $this->context->link->getAdminLink('AdminKpyPromociones');
        $salida = [];
        foreach ($contestadas as $promo) {
            $f = ($nuestras[(int) $promo['id_kpy_coupon']] ?? []) + $promo;
            $desde = (string) ($promo['fecha_desde'] ?? '');
            $hasta = (string) ($promo['fecha_hasta'] ?? '');
            $sinHasta = $hasta === '';
            $estado = in_array($promo['estado'], ['viva', 'programada', 'apagada'], true) ? $promo['estado'] : 'terminada';
            $clave = (string) ($f['clave'] ?? '');
            $c = $clave !== '' ? ($resumen['claves'][$clave] ?? null) : null;
            $afinado = [];
            if (!empty($f['clave_manual'])) {
                $afinado[] = 'dirección';
            }
            if ((string) ($f['titulo'] ?? '') !== '') {
                $afinado[] = 'título';
            }
            if ((string) ($f['texto'] ?? '') !== '') {
                $afinado[] = 'texto';
            }
            if ((string) ($f['imagen'] ?? '') !== '') {
                $afinado[] = 'foto';
            }
            if ((int) ($f['orden'] ?? 0) > 0) {
                $afinado[] = 'orden';
            }
            if ((string) ($f['campanas'] ?? '') !== '') {
                $afinado[] = 'campaña: ' . $f['campanas'];
            }
            if (isset($f['listar']) && (int) $f['listar'] === 0) {
                $afinado[] = 'no sale en la landing';
            }
            $salida[] = [
                'id' => (int) $promo['id_kpy_coupon'],
                'codigo' => (string) $promo['codigo'],
                'nombre' => (string) $promo['nombre'],
                'tipo' => (string) $promo['tipo'],
                'nombre_tipo' => KplClaves::NOMBRE_TIPO[(string) $promo['tipo']] ?? (string) $promo['tipo'],
                'pct' => KplTextos::pct((float) $promo['porcentaje']),
                'estado' => $estado,
                'desde' => $this->fechaCorta($desde),
                'hasta' => $sinHasta ? '' : $this->fechaCorta($hasta),
                // Los artículos del reparto (un formato, o un producto sin combinaciones, cada uno).
                'formatos' => count((array) ($promo['items'] ?? [])),
                'clave' => $clave,
                'url' => $clave !== '' ? KplPromos::urlClave($clave) : '',
                'id_category' => (int) ($categorias[$clave]['id_category'] ?? 0),
                'en_landing' => $c !== null && $c['estado'] === 'viva' && (int) $c['listar'] === 1,
                'n' => (int) ($c['n']['todos'] ?? 0),
                'titulo' => (string) ($c['titulo'] ?? ''),
                'logo' => (string) ($c['logo'] ?? ''),
                'imagenes' => (array) ($c['imagenes'] ?? []),
                'afinado' => $afinado,
                'url_editar' => $urlPromociones . '&editar=' . (int) $promo['id_kpy_coupon'],
                'url_ver' => $urlPromociones . '&ver=' . (int) $promo['id_kpy_coupon'] . '#kpc-promo-' . (int) $promo['id_kpy_coupon'],
            ];
        }
        $orden = ['viva' => 0, 'programada' => 1, 'apagada' => 2, 'terminada' => 3];
        usort($salida, static fn (array $a, array $b): int => [$orden[$a['estado']], -$a['id']] <=> [$orden[$b['estado']], -$b['id']]);

        return $salida;
    }

    /** Las categorías permanentes (kilos gratis, regalos, outlet): cuántos productos y su dirección. */
    private function permanentes(array $resumen, array $categorias): array
    {
        $salida = [];
        foreach (KplClaves::PERMANENTES as $tipo => $clave) {
            $c = $resumen['claves'][$clave] ?? null;
            $salida[] = [
                'tipo' => $tipo,
                'nombre' => KplClaves::NOMBRE_TIPO[$tipo],
                'clave' => $clave,
                'n' => (int) ($c['n']['todos'] ?? 0),
                'url' => KplPromos::urlClave($clave),
                'id_category' => (int) ($categorias[$clave]['id_category'] ?? 0),
                'imagen' => (string) ($c['imagenes'][0] ?? ''),
            ];
        }

        return $salida;
    }

    /** «2026-11-01» + «00:00» → «2026-11-01 00:00:00»; vacío si no hay fecha. */
    private function fechaHora(string $fecha, string $hora, string $siNoHora): string
    {
        $fecha = trim($fecha);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return '';
        }
        $hora = trim($hora);
        if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $fecha . ' ' . $siNoHora;
        }

        return $fecha . ' ' . $hora . ($siNoHora === '23:59:59' && $hora === '23:59' ? ':59' : ':00');
    }

    private function fechaCorta(string $fecha): string
    {
        if ($fecha === '' || strpos($fecha, '0000') === 0) {
            return '';
        }
        $t = strtotime($fecha);

        return $t ? date('d/m/Y', $t) : '';
    }

    /** «hace 3 minutos», «hace 2 horas», «ayer»… o vacío. */
    private function hace(string $fecha): string
    {
        $t = $fecha !== '' ? strtotime($fecha) : false;
        if (!$t) {
            return '';
        }
        $s = time() - $t;
        if ($s < 90) {
            return 'hace un momento';
        }
        if ($s < 3600) {
            return 'hace ' . (int) round($s / 60) . ' minutos';
        }
        if ($s < 86400) {
            $h = (int) round($s / 3600);

            return 'hace ' . $h . ($h === 1 ? ' hora' : ' horas');
        }
        $d = (int) round($s / 86400);

        return $d === 1 ? 'ayer' : 'hace ' . $d . ' días';
    }
}
