<?php
/**
 * La pantalla de las promociones: Catálogo → Kompy · Promociones.
 *
 * Arriba, el formulario, en el orden en que se piensa una promoción:
 *
 *   1. Qué promoción: cupón con código, 2ª unidad, Special Price o regalo con la compra, y el %.
 *      El Special Price puede ser el mismo % para todos, un % por artículo o un precio final con IVA
 *      por artículo. El regalo dice qué se regala (el pequeño de la misma ficha, o un artículo para
 *      todos), cuántos (por unidad o por pedido), el tope y si los packs también regalan.
 *   2. A qué productos: marcas, categorías, características (se cruzan), una lista pegada o en
 *      Excel (EAN, referencias, ids, enlaces…, con su % o su precio si hace falta) y lo que se deja
 *      fuera.
 *   3. Cuándo: desde y hasta, con atajos.
 *   4. Cómo llega al cliente: en la ficha, ya puesto (para todos o según de dónde venga la
 *      visita), con enlace para compartir.
 *   5. Google: si se anuncia y con qué título.
 *
 * Debajo, **los productos de la promoción** (al comprobar o al abrir una guardada): a cuáles llega,
 * con sus precios, los que se quedan fuera y por qué, con un buscador y un botón para quitar a
 * mano cualquiera (y volver a meterlo). «Comprobar» no guarda nada; «Guardar» lo deja todo puesto:
 * reparto, regla de carrito, Special Price y etiquetas.
 *
 * Y la lista de promociones con su estado, los Special Price cargados fuera de aquí y los ficheros
 * de Google Merchant.
 */
class KpcPantalla
{
    /** Las características que se ofrecen como filtro: id => cómo se llama en la pantalla. */
    public const CARACTERISTICAS = [14 => 'Para', 15 => 'Tipo de producto', 16 => 'Edad'];

    /** Los modos del Special Price. */
    public const MODOS = ['pct', 'pct_articulo', 'precio_articulo'];

    /** Los pasos del formulario que pinta este módulo (1 a 5); los de otros módulos van detrás. */
    public const PASOS_PROPIOS = 5;

    /** Cuántas filas enseña la lista de productos (las cuentas son siempre de todas). */
    private const FILAS_PREVIA = 2000;

    private Kpycoupons $modulo;
    private Context $contexto;
    private string $url;
    private int $idTienda;
    /** La lista de promociones se abre sola después de tocar una desde ella (borrar, apagar…). */
    private bool $listaAbierta = false;

    public function __construct(Kpycoupons $modulo, string $url)
    {
        $this->modulo = $modulo;
        $this->contexto = Context::getContext();
        $this->url = $url;
        $this->idTienda = (int) $this->contexto->shop->id;
    }

    /**
     * La pantalla entera. Al entrar: la lista de promociones arriba, plegada (con su resumen), y el
     * formulario vacío para hacer una nueva. `&ver=ID` despliega los productos de una en la lista;
     * `&editar=ID` la abre en el formulario; `&guardado=ID` es la vuelta después de guardarla.
     */
    public function pintar(): string
    {
        $avisos = [];
        $valores = null;
        $previa = null;

        try {
            [$avisos, $valores, $previa] = $this->procesar();
        } catch (Throwable $e) {
            $avisos[] = ['error', $this->t('No se ha podido guardar: %s', [$e->getMessage()])];
            PrestaShopLogger::addLog('kpycoupons pantalla: ' . $e->getMessage(), 3, null, 'KpcPantalla', 0, true);
        }

        // Después de guardar: la lista abierta, con la recién guardada y sus productos a la vista, y
        // el formulario vacío para la siguiente.
        $ver = (int) Tools::getValue('ver');
        $guardado = (int) Tools::getValue('guardado');
        if ($guardado > 0 && $valores === null) {
            $avisos[] = ['ok', $this->resumenGuardado($guardado)];
            $ver = $guardado;
        }

        if ($valores === null) {
            $valores = $this->valoresDe((int) Tools::getValue('editar'), (int) Tools::getValue('duplicar'));
            // Al abrir una que ya existe en el formulario, sus productos: a cuáles llega hoy y por qué.
            if ($valores['id'] > 0) {
                $previa = $this->previa($valores);
            }
        }

        // Los productos de una promoción, desplegados bajo su fila de la lista.
        $verValores = null;
        $verPrevia = null;
        if ($ver > 0) {
            $verValores = $this->valoresDe($ver, 0);
            $verPrevia = $verValores['id'] > 0 ? $this->previa($verValores) : null;
        }
        $lista = $this->lista();
        $deFuera = $this->especialesDeFuera();
        $cuentas = array_count_values(array_column($lista, 'estado')) + ['en_marcha' => 0, 'programada' => 0, 'agotada' => 0, 'apagada' => 0, 'terminada' => 0];
        $cuentas['fuera'] = count($deFuera);

        // Lo que la plantilla necesita ya hecho (Smarty 4.5 no quiere funciones de PHP dentro).
        $valores['marcas_csv'] = implode(',', $valores['marcas']);
        $valores['categorias_csv'] = implode(',', $valores['categorias']);
        $valores['tamanos_json'] = json_encode(array_values($valores['tamanos']), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        // «0» no es ningún nivel: si se mandó sin nada marcado, vuelve sin nada marcado (y no «todos»).
        $valores['packs_csv'] = implode(',', $valores['packs']);
        // Las categorías que no son gamas de una marca elegida se ven en «Categorías de la tienda».
        [, $valores['generales']] = KpcAlcance::gamas($valores['marcas'], $valores['categorias'], false);
        $valores['antiguos'] = $valores['elegir'] === 'marca' ? $this->deAntes($valores) : '';
        $valores['carac_marcadas'] = array_fill_keys($valores['caracteristicas'], true);
        $valores['canal_marcado'] = array_fill_keys($valores['canales'], true);
        $valores['ver_marcado'] = array_fill_keys($valores['ver_canales'], true);
        // El regalo elegido (el mismo para todos, el de reserva), dicho para la pantalla.
        $valores['regalo_articulos'] = $this->articulosDichos([$valores['regalo_mismo'], $valores['regalo_reserva']]);

        $this->contexto->smarty->assign(['kpc' => [
            'url' => $this->url,
            'plantillas' => self::plantillas(),
            'avisos' => $avisos,
            'comprobado' => Tools::getValue('accion') === 'comprobar',
            'v' => $valores,
            // Lo que otro módulo añade al formulario (la landing de promociones: el paso 6, «En la landing»).
            'hook_formulario' => $this->hookFormulario($valores),
            'previa' => $previa,
            'lista' => $lista,
            'lista_abierta' => $this->listaAbierta || $ver > 0,
            'cuentas' => $cuentas,
            'ver' => $verPrevia !== null ? $ver : 0,
            'ver_v' => $verValores,
            'ver_previa' => $verPrevia,
            'de_fuera' => $deFuera,
            'merchant' => $this->merchant(),
            'caracteristicas' => $this->caracteristicas(),
            'datos' => json_encode([
                'marcas' => $this->marcas(),
                'detalle' => $this->detalleMarcas(),
                'hoy' => date('Y-m-d'),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
            'tienda' => rtrim($this->contexto->link->getBaseLink($this->idTienda), '/') . '/',
            'parametro' => Kpycoupons::PARAMETRO,
        ]]);

        return $this->contexto->smarty->fetch(self::plantillas() . 'pantalla.tpl');
    }

    /**
     * Solo los productos de una promoción, para desplegarlos en la lista sin recargar la página
     * (lo pide el ojo de «Productos»: `AdminKpyPromocionesController::displayAjaxProductos`).
     */
    public function productosDe(int $id): string
    {
        $valores = $this->valoresDe($id, 0);
        if ($valores['id'] <= 0) {
            return '<div class="kpc-productos"><p class="kpc-vacio">' . htmlspecialchars($this->t('Esa promoción ya no existe: recarga la página.'), ENT_QUOTES, 'UTF-8') . '</p></div>';
        }
        $plantilla = $this->contexto->smarty->createTemplate(self::plantillas() . 'productos.tpl');
        $plantilla->assign(['pr' => $this->previa($valores), 'pv' => $valores, 'url' => $this->url, 'solo_ver' => true]);

        return $plantilla->fetch();
    }

    private static function plantillas(): string
    {
        return _PS_MODULE_DIR_ . 'kpycoupons/views/templates/admin/';
    }

    /**
     * Buscar artículos para el regalo (lo pide la pantalla sin recargar, al escribir):
     * `AdminKpyPromocionesController::displayAjaxBuscar`.
     */
    public function buscarArticulos(string $q): array
    {
        return KpcRegalo::buscar($q, $this->idTienda, (int) $this->contexto->language->id);
    }

    /**
     * Unos artículos («8299-92468»), dichos para la pantalla: nombre corto y el detalle (marca,
     * precio, stock), y si están a la venta.
     *
     * @param string[] $valores
     *
     * @return array<string, array{texto: string, detalle: string, vendible: bool}>
     */
    private function articulosDichos(array $valores): array
    {
        $articulos = [];
        foreach ($valores as $v) {
            $a = KpcRegalo::leerArticulo((string) $v);
            if ($a !== null) {
                $articulos[] = $a;
            }
        }
        if ($articulos === []) {
            return [];
        }
        $salida = [];
        foreach (KpcRegalo::datos($articulos, $this->idTienda, (int) $this->contexto->language->id) as $k => $d) {
            $salida[$k] = [
                'texto' => KpcRegalo::nombreCorto($d['nombre'], $d['formato']),
                'detalle' => implode(' · ', array_filter([$d['marca'], $this->euros((float) $d['precio']), sprintf($this->t('stock %d'), (int) $d['stock']),
                    !$d['vendible'] || $d['apagado'] ? $this->t('NO SE VENDE') : ''])),
                'vendible' => $d['vendible'] && !$d['apagado'],
            ];
        }

        return $salida;
    }

    /** «12,99 €», como lo escribe la tienda. */
    private function euros(float $x): string
    {
        $locale = $this->contexto->getCurrentLocale();
        $moneda = $this->contexto->currency ? $this->contexto->currency->iso_code : 'EUR';

        return $locale ? $locale->formatPrice($x, $moneda) : number_format($x, 2, ',', '.') . ' €';
    }

    /**
     * Lo que otros módulos añaden al final del formulario, por el hook `displayKpyCouponsFormulario`
     * (`id` de la promoción, 0 si es nueva; `valores`, los del formulario). Hoy lo usa la landing de
     * promociones para su paso 6. Lo que devuelvan se guarda con `actionKpyCouponsGuardar`.
     */
    private function hookFormulario(array $valores): string
    {
        // Módulo a módulo, en el orden de Diseño → Posiciones, y cada uno con el número de su paso
        // (`paso`: 6, 7…, detrás de los cinco del formulario), para que ningún módulo tenga que
        // mirar quién más está enganchado para numerarse (2.5.0).
        $id = (int) $valores['id'];
        $enlace = $this->contexto->link->getAdminLink(Kpycoupons::PESTANA);
        $parametros = [
            'version' => KpcContrato::VERSION,
            'id' => $id,
            'valores' => $valores,
            'duplicar' => (int) Tools::getValue('duplicar'),
            // Los enlaces a esta pantalla, para que nadie los construya a mano.
            'url_editar' => $id > 0 ? $enlace . '&editar=' . $id : '',
            'url_ver' => $id > 0 ? $enlace . '&ver=' . $id . '#kpc-promo-' . $id : '',
        ];
        $salida = '';
        $paso = self::PASOS_PROPIOS;
        foreach ((array) (Hook::getHookModuleExecList('displayKpyCouponsFormulario') ?: []) as $modulo) {
            try {
                // El número solo se gasta si el módulo pinta algo (un tipo de promoción sin paso
                // ajeno no deja un hueco: «7» sin «6»).
                $html = trim((string) Hook::exec('displayKpyCouponsFormulario', $parametros + ['paso' => $paso + 1], (int) $modulo['id_module']));
                if ($html !== '') {
                    ++$paso;
                    $salida .= $html;
                }
            } catch (Throwable $e) {
                PrestaShopLogger::addLog('kpycoupons, al pintar el formulario de otro módulo (displayKpyCouponsFormulario, ' . ($modulo['module'] ?? '?') . '): ' . $e->getMessage(), 2, null, 'KpcPantalla', 0, true);
            }
        }

        return $salida;
    }

    /**
     * Lo que llega del formulario o de los enlaces de la lista.
     *
     * @return array{0: array, 1: ?array, 2: ?array} avisos, valores del formulario (si hay que
     *         volver a pintarlo con lo enviado) y lista de productos
     */
    private function procesar(): array
    {
        // Lo que se hace desde la lista (actualizar, borrar, apagar o encender) vuelve a una
        // dirección limpia: recargar la página no lo repite (antes, recargar tras «Apagar» la volvía
        // a encender).
        if (Tools::getValue('actualizar')) {
            $antes = microtime(true);
            KpcReparto::rehacer($this->idTienda);
            $this->volver(['hecho' => 'actualizado', 's' => number_format(microtime(true) - $antes, 1, ',', '')]);
        }

        if (Tools::getValue('borrar')) {
            $cupon = new KpcCupon((int) Tools::getValue('borrar'));
            if (Validate::isLoadedObject($cupon)) {
                $codigo = (string) $cupon->codigo;
                $tipo = (string) $cupon->tipo;
                $cupon->delete();
                KpcReparto::rehacer($this->idTienda);
                $this->volver(['hecho' => $tipo === 'special_price' ? 'borrada_sp' : 'borrada', 'codigo' => $codigo]);
            }
        }

        if (Tools::getValue('encender')) {
            $cupon = new KpcCupon((int) Tools::getValue('encender'));
            if (Validate::isLoadedObject($cupon)) {
                $cupon->activo = $cupon->activo ? 0 : 1;
                $cupon->update();
                KpcReparto::rehacer($this->idTienda);
                $this->volver(['hecho' => $cupon->activo ? 'encendida' : 'apagada', 'codigo' => (string) $cupon->codigo], (int) $cupon->id);
            }
        }

        // Una agotada vuelve a la tienda (con el cupo que tenga; para dar más unidades, se sube el cupo).
        if (Tools::getValue('reabrir')) {
            $cupon = new KpcCupon((int) Tools::getValue('reabrir'));
            if (Validate::isLoadedObject($cupon)) {
                KpcCupo::reabrir((int) $cupon->id);
                KpcReparto::rehacer($this->idTienda);
                $this->volver(['hecho' => 'reabierta', 'codigo' => (string) $cupon->codigo], (int) $cupon->id);
            }
        }

        // La vuelta: el aviso de lo hecho, con la lista abierta.
        $hecho = (string) Tools::getValue('hecho');
        if ($hecho !== '') {
            $this->listaAbierta = true;
            $codigo = (string) Tools::getValue('codigo');
            $textos = [
                'actualizado' => $this->t('Actualizado en %s s: a qué llega cada promoción, reglas de carrito, Special Price y etiquetas.', [(string) Tools::getValue('s')]),
                'borrada_sp' => $this->t('Promoción %s borrada: sus precios vuelven a los de siempre y sus etiquetas se quitan.', [$codigo]),
                'borrada' => $this->t('Promoción %s borrada, con su regla de carrito y sus etiquetas.', [$codigo]),
                'encendida' => $this->t('%s encendida.', [$codigo]),
                'apagada' => $this->t('%s apagada: ya no descuenta ni se ve en la tienda.', [$codigo]),
                'reabierta' => $this->t('%s reabierta: vuelve a la tienda con su cupo (si sigue cubierto, se agota con el siguiente pedido).', [$codigo]),
            ];
            if (isset($textos[$hecho])) {
                return [[['ok', $textos[$hecho]]], null, null];
            }
        }

        $accion = (string) Tools::getValue('accion');
        if (!Tools::isSubmit('kpc_formulario') || !in_array($accion, ['guardar', 'comprobar'], true)) {
            return [[], null, null];
        }

        $valores = $this->valoresDelFormulario();
        $avisos = [];
        foreach ($this->errores($valores, $accion === 'guardar') as $error) {
            $avisos[] = ['error', $error];
        }
        if ($accion === 'comprobar' || $avisos !== []) {
            // Sin tipo, o sin ningún suelto ni pack marcado, no hay nada que enseñar: primero hay que decirlo.
            $conPrevia = $valores['tipo'] !== '' && $valores['elegir'] !== '' && $this->hayAmbito($valores);

            return [$avisos, $valores, $conPrevia ? $this->previa($valores) : null];
        }

        // Guardada: a la lista, con ella a la vista (en marcha o programada) y el formulario vacío.
        $id = $this->guardar($valores);
        $this->volver(['guardado' => $id], $id);

        return [[], null, null];
    }

    /**
     * A la pantalla, con lo que diga `$parametros` y, si se da, a la altura de esa promoción.
     *
     * @param array<string, string|int> $parametros
     */
    private function volver(array $parametros, int $promocion = 0): void
    {
        Tools::redirectAdmin($this->url . '&' . http_build_query($parametros) . ($promocion > 0 ? '#kpc-promo-' . $promocion : '#kpc-promos'));
    }

    /** Guarda la promoción y lo rehace todo. @return int su id */
    private function guardar(array $v): int
    {
        $esPrecio = $v['tipo'] === 'special_price';
        $esRegalo = $v['tipo'] === 'regalo';
        $cupon = new KpcCupon((int) $v['id']);
        // El código lo escribe quien hace un cupón; la 2ª unidad y el Special Price llevan uno interno,
        // que se queda como estaba al cambiarlas (si no cambian de tipo).
        $cupon->codigo = $v['codigo'] !== '' ? $v['codigo']
            : ($cupon->id && (string) $cupon->codigo !== '' && (string) $cupon->tipo === $v['tipo'] ? (string) $cupon->codigo : $this->codigoPorDefecto($v));
        $cupon->nombre = $v['nombre'] !== '' ? $v['nombre'] : $this->nombrePorDefecto($v);
        $cupon->tipo = $v['tipo'];
        $cupon->modo_precio = $esPrecio ? $v['modo_precio'] : 'pct';
        $cupon->porcentaje = $esRegalo ? 0 : $v['porcentaje'];
        $cupon->activo = $v['activo'];
        // Sin fecha de inicio: la que ya tenía o, si es nueva, desde ya, en el minuto en punto
        // (PrestaShop mira los precios por minutos: con segundos tardaría en entrar). Siempre una
        // fecha de verdad: la «0000-00-00» que deja un vacío no la acepta la regla de carrito.
        $cupon->fecha_desde = $this->mismoMinuto(
            $this->fechaHora($v['fecha_desde'], $v['hora_desde'], '00:00') ?? (KpcCupon::fechaValida($cupon->fecha_desde) ?? date('Y-m-d H:i:00')),
            (string) $cupon->fecha_desde
        );
        $cupon->fecha_hasta = $this->mismoMinuto($this->fechaHora($v['fecha_hasta'], $v['hora_hasta'], '23:59', true), (string) $cupon->fecha_hasta);
        $cupon->auto_web = $v['tipo'] === 'cupon' && $v['entra'] === 'todos' ? 1 : 0;
        $cupon->auto_canal = $v['tipo'] === 'cupon' && $v['entra'] === 'canal' ? implode(',', $v['canales']) : '';
        // Dónde se ve (3.1.0): en toda la tienda, solo quien llega de un canal, o en ningún sitio (solo por
        // enlace o código). Lo que solo ven unos no puede llegar ya puesto a todos: pasa a «por canal».
        // Solo en el cupón: la 2ª unidad entra sola en la cesta de cualquiera, así que «solo quien llega de»
        // sería mentira (revisión del 26/10/2026). `valoresDelFormulario()` ya lo normaliza; esto es la red.
        $cupon->ver_canal = $v['tipo'] === 'cupon' && $v['ver'] === 'canal' ? implode(',', $v['ver_canales']) : '';
        if ($cupon->ver_canal !== '' && $cupon->auto_web) {
            $cupon->auto_web = 0;
            $cupon->auto_canal = $cupon->ver_canal;
        }
        // Y «ya puesto si llega de» solo desde canales que también lo ven: quien no lo ve no lo lleva.
        if ($cupon->ver_canal !== '' && $cupon->auto_canal !== '') {
            $cupon->auto_canal = implode(',', array_values(array_intersect(explode(',', $cupon->auto_canal), explode(',', $cupon->ver_canal))));
        }
        // El Special Price y el regalo se ven siempre en la ficha (el regalo se pide con su tarjeta). Y el
        // cupón «para todos» también: lo ve cualquiera en la ficha de sus productos, venga de donde
        // venga la visita (Julián, 23/10/2026).
        $cupon->en_ficha = $esPrecio || $esRegalo || $cupon->auto_web ? 1 : ($v['ver'] === 'nadie' ? 0 : 1);
        // Oculto del todo: no entra solo desde ningún canal (quien no lo ve no lo lleva puesto).
        if (!$cupon->en_ficha) {
            $cupon->auto_canal = '';
        }
        // Un regalo no respeta el Special Price: el artículo rebajado es justo el disparador (la
        // tarjeta de siempre va con su rebaja; la de «+ 3 kg gratis», al precio de siempre).
        $cupon->excluir_especial = $esPrecio ? 1 : ($esRegalo ? 0 : $v['excluir_especial']);
        // Los packs: «Incluir los packs» y, por marca, «¿solo algunos packs?» (ver `packsDe()`). En
        // un regalo, «los packs también regalan».
        $cupon->incluir_packs = $v['tipo'] === 'segunda_unidad' ? 1 : ($esRegalo ? (int) $v['regalo_packs'] : (int) $v['incluir_packs']);
        $cupon->tamanos = implode("\n", $v['tamanos']);
        $cupon->packs = $this->packsDe($v);
        $cupon->regalo_modo = $esRegalo ? $v['regalo_modo'] : 'pequeno';
        $cupon->regalo_por = $esRegalo ? $v['regalo_por'] : 'unidad';
        // El cupo (por cantidad), para todos los tipos; en un regalo es el tope de antes. Con un cupo
        // mayor que lo vendido (o sin cupo), una agotada se vuelve a abrir.
        $cupon->cupo = (int) $v['cupo'];
        $cupon->cupo_ficha = (int) $v['cupo_ficha'];
        $cupon->cupo_lista = $esRegalo ? 0 : (int) $v['cupo_lista'];
        $cupon->regalo_tope = $esRegalo ? (int) $v['cupo'] : 0;
        $cupon->etiqueta = $esPrecio ? $v['etiqueta'] : '';
        if ($cupon->id && KpcCupon::fechaValida($cupon->cupo_agotado) !== null) {
            $vendidas = (int) (KpcCupo::estado([(int) $cupon->id])[(int) $cupon->id]['vendidas'] ?? 0);
            // Con unidades por artículo se mira abajo, con la lista ya guardada (si a todos se les
            // acabaron y la lista no les da más, sigue agotada).
            if ($cupon->cupo <= 0 || $cupon->cupo > $vendidas || $cupon->cupo_lista) {
                $cupon->cupo_agotado = null;
            }
        }
        $cupon->regalo_packs = $esRegalo ? (int) $v['regalo_packs'] : 0;
        $cupon->regalo_mismo = $esRegalo ? $v['regalo_mismo'] : '';
        $cupon->regalo_reserva = $esRegalo ? $v['regalo_reserva'] : '';
        $cupon->regalo_parejas = $esRegalo ? $v['regalo_parejas'] : '';
        $cupon->regalo_corto = $esRegalo ? $v['regalo_corto'] : '';
        $cupon->sin_outlet = $v['sin_outlet'];
        $cupon->bloquear_priceshape = $v['bloquear_priceshape'];
        $cupon->en_merchant = $v['en_merchant'];
        $cupon->merchant_titulo = $esPrecio ? '' : ($v['merchant_titulo'] !== '' ? $v['merchant_titulo'] : $this->tituloPorDefecto($v));
        $cupon->merchant_id = $v['merchant_id'];
        $cupon->lista = $v['lista'];
        $cupon->lista_tipo = $v['lista_tipo'];
        $cupon->excluir = $v['excluir'];
        $cupon->id_shop = $this->idTienda;
        if (!($cupon->id ? $cupon->update() : $cupon->add())) {
            throw new RuntimeException($this->t('la base de datos no ha aceptado la promoción'));
        }
        $cupon->guardarAmbitos($this->ambitos($v));
        if ((int) $cupon->cupo_lista === 1) {
            KpcCupo::comprobarAgotada((int) $cupon->id);
        }
        // Lo que otros módulos guardan de esta promoción (el paso 6 de la landing), antes del
        // reparto: el reparto avisa a la landing y esta ya encuentra lo suyo escrito.
        try {
            // Con la fila pública (`KpcContrato::promocion`), no con el objeto: ningún oyente depende
            // de las clases de este módulo.
            $fila = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . (int) $cupon->id);
            Hook::exec('actionKpyCouponsGuardar', [
                'version' => KpcContrato::VERSION,
                'id' => (int) $cupon->id,
                'valores' => $v,
                'promocion' => is_array($fila) ? KpcContrato::promocion($fila) : null,
            ]);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al avisar de la promoción guardada (actionKpyCouponsGuardar): ' . $e->getMessage(), 2, null, 'KpcPantalla', (int) $cupon->id, true);
        }
        // El reparto avisa por `actionKpyCouponsReparto` con `guardada` = esta promoción: quien
        // guarde algo por promoción (los acuerdos con proveedores) hace ahí su espejo.
        KpcReparto::rehacer($this->idTienda, (int) $cupon->id);

        return (int) $cupon->id;
    }

    /** El aviso de después de guardar: a cuánto llega y qué se ha quedado fuera. */
    private function resumenGuardado(int $id): string
    {
        $cupon = new KpcCupon($id);
        if (!Validate::isLoadedObject($cupon)) {
            return $this->t('Guardada.');
        }
        $desde = (string) $cupon->fecha_desde;
        $cuando = !$cupon->activo ? $this->t('Está apagada: no hará nada hasta que la enciendas.')
            : ($desde > date('Y-m-d H:i:s')
                ? sprintf($this->t('Programada: empieza el %s.'), date('d/m/Y \a \l\a\s H:i', (int) strtotime($desde)))
                : $this->t('Ya está en marcha.'));
        $texto = sprintf($this->t('Guardada. %s llega a %d artículos.'), $cupon->codigo, (int) $cupon->formatos) . ' ' . $cuando;
        if ((string) $cupon->aviso !== '') {
            $texto .= ' ' . $cupon->aviso . '.';
        }
        if ((int) $cupon->cupo_lista === 1) {
            $a = $this->porArticulo((int) $cupon->id);
            $texto .= ' ' . sprintf($this->t('Unidades por artículo: %d en %d artículos (%s); %d vendidas, quedan %d%s.'), $a['unidades'], $a['articulos'], $this->t(KpcCupo::unidadDe((string) $cupon->tipo)), $a['vendidas'], $a['quedan'], $a['agotados'] > 0 ? sprintf($this->t(', %d artículos ya agotados'), $a['agotados']) : '');
        }
        if ((int) $cupon->cupo > 0) {
            $e = KpcCupo::estado([(int) $cupon->id])[(int) $cupon->id] ?? null;
            $texto .= ' ' . sprintf($this->t('Cupo de %d (%s): %d vendidas, quedan %d.'), (int) $cupon->cupo, $this->t(KpcCupo::unidadDe((string) $cupon->tipo)), (int) ($e['vendidas'] ?? 0), (int) ($e['quedan'] ?? $cupon->cupo));
        }

        if ($cupon->tipo === 'regalo') {
            return $texto . ' ' . $this->t('Qué regala cada artículo ya está decidido (míralo en sus productos) y las etiquetas al día: en la ficha sale la tarjeta «12 kg + 3 kg gratis» al lado de la de siempre.');
        }

        return $texto . ' ' . ($cupon->tipo === 'special_price'
            ? $this->t('Los precios ya están puestos, con sus fechas, y las etiquetas al día.')
            : $this->t('La regla de carrito y las etiquetas ya están al día.'));
    }

    /**
     * Lo que se ha enviado, limpio. El fichero, si lo hay, se lee aquí y su columna buena (y la del
     * % o el precio, si hace falta) se añade a la lista: así se ve, se puede corregir y queda guardada.
     */
    private function valoresDelFormulario(): array
    {
        $ids = static fn ($valor): array => array_values(array_unique(array_filter(array_map('intval', (array) $valor))));
        // Sin tipo elegido no se sigue: cada tipo tiene sus campos y no se adivina.
        $tipo = in_array(Tools::getValue('tipo'), KpcElegibles::TIPOS, true) ? (string) Tools::getValue('tipo') : '';
        $modo = in_array(Tools::getValue('modo_precio'), self::MODOS, true) ? (string) Tools::getValue('modo_precio') : 'pct';
        // Cómo se eligen los productos: por marca o con una lista (con un % o un precio por
        // artículo, siempre con la lista). Lo de la otra manera se queda escondido en el formulario,
        // pero no cuenta.
        $elegir = in_array(Tools::getValue('elegir'), ['marca', 'lista'], true) ? (string) Tools::getValue('elegir') : '';
        if ($elegir !== '' && $tipo === 'special_price' && $modo !== 'pct') {
            $elegir = 'lista';
        }
        $porMarca = $elegir === 'marca';
        $porLista = $elegir === 'lista';
        // Por marca, la lista que ya llevaba una promoción de antes (marca y lista a la vez) se mantiene.
        $lista = trim((string) Tools::getValue($porLista ? 'lista' : 'lista_de_antes'));
        $listaTipo = in_array(Tools::getValue('lista_tipo'), KpcLista::TIPOS, true) ? (string) Tools::getValue('lista_tipo') : 'auto';
        $conValor = $this->conValor($tipo, $modo);

        $fichero = $_FILES['lista_fichero'] ?? null;
        $leidoDelFichero = null;
        if ($porLista && is_array($fichero) && (int) ($fichero['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file((string) $fichero['tmp_name'])) {
            $filas = KpcLista::deFichero((string) $fichero['tmp_name'], (string) $fichero['name']);
            $leidoDelFichero = KpcLista::buscar($filas, $listaTipo, KpcLista::indice($this->idTienda), $conValor);
            $leidoDelFichero['fichero'] = (string) $fichero['name'];
            $lista = trim($lista . "\n" . implode("\n", $leidoDelFichero['valores']));
        }

        $codigo = mb_strtoupper(preg_replace('/\s+/', '', (string) Tools::getValue('codigo')) ?? '');
        $canales = array_values(array_intersect(KpcElegibles::CANALES, (array) Tools::getValue('canales')));
        $entra = in_array(Tools::getValue('entra'), ['no', 'todos', 'canal'], true) ? (string) Tools::getValue('entra') : 'no';
        $verCanales = array_values(array_intersect(KpcElegibles::CANALES, (array) Tools::getValue('ver_canales')));
        // Sin «ver» (un formulario abierto antes de la 3.1.0, con la casilla «se enseña», o un envío a mano):
        // lo que diga la casilla; sin nada, oculto, como hasta la 3.0.0. Nunca «visible» por defecto.
        $ver = in_array(Tools::getValue('ver'), ['todos', 'canal', 'nadie'], true)
            ? (string) Tools::getValue('ver')
            : (Tools::getValue('en_ficha') ? 'todos' : 'nadie');
        // «Solo quien llega de» solo existe en el cupón (la 2ª unidad entra sola en la cesta, para cualquiera).
        if ($tipo !== 'cupon' && $ver === 'canal') {
            $ver = 'todos';
        }
        // Lo que solo ven unos no puede llegar ya puesto a todos, ni puesto desde un canal que no lo ve: el
        // formulario enseña lo que se va a guardar (`guardar()` aplica la misma regla).
        if ($tipo === 'cupon' && $ver === 'canal' && $verCanales !== []) {
            if ($entra === 'todos') {
                $entra = 'canal';
                $canales = $verCanales;
            } elseif ($entra === 'canal') {
                $canales = array_values(array_intersect($canales, $verCanales));
            }
        }
        // «En ningún sitio» + «ya puesto a todo el mundo»: el «para todos» manda (`guardar()`: `auto_web` →
        // `en_ficha` = 1, lo ve cualquiera en la ficha de sus productos): el formulario enseña «en toda la tienda».
        if ($tipo === 'cupon' && $ver === 'nadie' && $entra === 'todos') {
            $ver = 'todos';
        }
        // «En ningún sitio» no lo ve nadie: tampoco llega puesto desde un canal (la misma regla, con el conjunto
        // vacío); solo por enlace o código.
        if ($tipo === 'cupon' && $ver === 'nadie' && $entra === 'canal') {
            $entra = 'no';
            $canales = [];
        }
        $esRegalo = $tipo === 'regalo';
        $regaloPacks = (int) ($esRegalo && (bool) Tools::getValue('regalo_packs'));

        return [
            'id' => (int) Tools::getValue('id_kpy_coupon'),
            'tipo' => $tipo,
            'modo_precio' => $modo,
            // Un regalo no descuenta en el precio: sin %.
            'porcentaje' => $esRegalo ? 0.0 : round((float) str_replace(',', '.', (string) Tools::getValue('porcentaje')), 3),
            'regalo_modo' => in_array(Tools::getValue('regalo_modo'), KpcRegalo::MODOS, true) ? (string) Tools::getValue('regalo_modo') : 'pequeno',
            'regalo_por' => in_array(Tools::getValue('regalo_por'), KpcRegalo::POR, true) ? (string) Tools::getValue('regalo_por') : 'unidad',
            // El cupo vale para todos los tipos (en un regalo era el «tope» hasta la 2.3.0).
            'cupo' => max(0, (int) Tools::getValue('cupo', Tools::getValue('regalo_tope'))),
            'cupo_ficha' => (int) (bool) Tools::getValue('cupo_ficha'),
            'cupo_lista' => $tipo !== 'regalo' && Tools::getValue('cupo_lista') ? 1 : 0,
            'etiqueta' => $tipo === 'special_price' && array_key_exists((string) Tools::getValue('etiqueta'), KpcCupo::ETIQUETAS) ? (string) Tools::getValue('etiqueta') : '',
            'cupo_estado' => null,
            'regalo_packs' => $regaloPacks,
            'regalo_mismo' => KpcRegalo::escribirArticulo(KpcRegalo::leerArticulo(Tools::getValue('regalo_mismo'))),
            'regalo_reserva' => KpcRegalo::escribirArticulo(KpcRegalo::leerArticulo(Tools::getValue('regalo_reserva'))),
            'regalo_parejas' => $this->parejasLimpias((string) Tools::getValue('regalo_parejas')),
            'regalo_corto' => mb_substr(trim((string) Tools::getValue('regalo_corto')), 0, 24),
            // Tal cual se escribió, para volver a pintarlo (vacío se queda vacío, no «0»).
            'pct_texto' => trim(str_replace(',', '.', (string) Tools::getValue('porcentaje'))),
            // Solo el cupón lleva código escrito; en los otros tipos el campo está escondido y no cuenta.
            'codigo' => $tipo === 'cupon' ? $codigo : '',
            'nombre' => trim((string) Tools::getValue('nombre')),
            'elegir' => $elegir,
            'marcas' => $porMarca ? $ids(Tools::getValue('marcas')) : [],
            'categorias' => $porMarca ? $ids(Tools::getValue('categorias')) : [],
            'caracteristicas' => $porMarca ? $ids(Tools::getValue('caracteristicas')) : [],
            'lista' => $lista,
            'lista_tipo' => $listaTipo,
            'excluir' => trim((string) Tools::getValue('excluir')),
            'tamanos' => $porMarca ? $this->elegidos(Tools::getValue('tamanos'), Tools::getValue('tamanos_ofrecidos'), [KpcFormatos::class, 'normalizar']) : [],
            'incluir_packs' => $esRegalo ? $regaloPacks : (int) ($tipo !== 'segunda_unidad' && (bool) Tools::getValue('incluir_packs')),
            // «¿Solo algunos packs?» (por marca, con los packs incluidos): esos niveles, sin los sueltos.
            'packs' => $porMarca && $tipo !== 'segunda_unidad' && !$esRegalo && Tools::getValue('incluir_packs')
                ? array_values(array_unique(array_filter(array_map([self::class, 'nivel'], (array) Tools::getValue('packs')), static fn ($n) => $n !== '' && $n !== '1' && substr($n, -1) !== '+')))
                : [],
            'excluir_especial' => (int) (bool) Tools::getValue('excluir_especial'),
            'sin_outlet' => (int) (bool) Tools::getValue('sin_outlet'),
            'fecha_desde' => $this->fechaLimpia(Tools::getValue('fecha_desde')),
            'hora_desde' => $this->horaLimpia(Tools::getValue('hora_desde')),
            'fecha_hasta' => $this->fechaLimpia(Tools::getValue('fecha_hasta')),
            'hora_hasta' => $this->horaLimpia(Tools::getValue('hora_hasta')),
            'activo' => (int) (bool) Tools::getValue('activo'),
            'en_ficha' => $ver !== 'nadie' ? 1 : 0,
            // Con «solo quien llega de» sin ningún canal marcado no se guarda: lo dice `errores()`.
            'ver' => $ver,
            'ver_canales' => $verCanales,
            'entra' => $entra === 'canal' && $canales === [] ? 'no' : $entra,
            'canales' => $canales,
            'en_merchant' => (int) (bool) Tools::getValue('en_merchant'),
            'merchant_titulo' => trim((string) Tools::getValue('merchant_titulo')),
            'merchant_id' => preg_replace('/[^A-Za-z0-9_]/', '_', trim((string) Tools::getValue('merchant_id'))) ?? '',
            'bloquear_priceshape' => (int) (bool) Tools::getValue('bloquear_priceshape'),
            'id_cart_rule' => 0,
            'leido_fichero' => $leidoDelFichero,
        ];
    }

    /** Los valores de una promoción guardada (o los de una nueva, o los de una copia). */
    private function valoresDe(int $id, int $duplicar): array
    {
        $cupon = new KpcCupon($duplicar ?: $id);
        $existe = Validate::isLoadedObject($cupon);
        $ambitos = $existe ? $cupon->ambitos() : [];
        $porTipo = KpcElegibles::porTipo($ambitos);
        $canales = array_values(array_filter(explode(',', (string) $cupon->auto_canal)));
        $verCanales = array_values(array_filter(explode(',', (string) $cupon->ver_canal)));

        // Una promoción cargada con SQL (desde el registro de catalog-ops) trae sus artículos sin
        // la lista escrita: se escribe como «producto-formato» (con su valor, si lo llevan), para
        // que guardarla no los pierda.
        $lista = (string) $cupon->lista;
        $excluir = (string) $cupon->excluir;
        if ($existe && trim($lista) === '' && ($porTipo['producto'] || $porTipo['combinacion'])) {
            $lista = $this->comoTexto($ambitos, '');
        }
        if ($existe && trim($excluir) === '' && ($porTipo['excluir_producto'] || $porTipo['excluir_combinacion'])) {
            $excluir = $this->comoTexto($ambitos, 'excluir_');
        }

        $v = [
            'id' => $existe && !$duplicar ? (int) $cupon->id : 0,
            // Una nueva, sin tipo: primero se elige qué promoción es y luego aparece lo suyo.
            'tipo' => $existe ? (string) $cupon->tipo : '',
            'modo_precio' => $existe ? ((string) $cupon->modo_precio ?: 'pct') : 'pct',
            'regalo_modo' => $existe && in_array((string) $cupon->regalo_modo, KpcRegalo::MODOS, true) ? (string) $cupon->regalo_modo : 'pequeno',
            'regalo_por' => $existe && in_array((string) $cupon->regalo_por, KpcRegalo::POR, true) ? (string) $cupon->regalo_por : 'unidad',
            'cupo' => $existe ? max((int) $cupon->cupo, (int) $cupon->regalo_tope) : 0,
            'cupo_ficha' => $existe ? (int) $cupon->cupo_ficha : 1,
            'cupo_lista' => $existe ? (int) $cupon->cupo_lista : 0,
            'etiqueta' => $existe && array_key_exists((string) $cupon->etiqueta, KpcCupo::ETIQUETAS) ? (string) $cupon->etiqueta : '',
            // Cómo va el cupo de una que ya existe (para decirlo en el formulario).
            'cupo_estado' => $existe && !$duplicar && ((int) $cupon->cupo > 0 || (int) $cupon->cupo_lista === 1) ? $this->cupoDicho((int) $cupon->id) : null,
            'regalo_packs' => $existe ? (int) $cupon->regalo_packs : 0,
            'regalo_mismo' => $existe ? KpcRegalo::escribirArticulo(KpcRegalo::leerArticulo((string) $cupon->regalo_mismo)) : '',
            'regalo_reserva' => $existe ? KpcRegalo::escribirArticulo(KpcRegalo::leerArticulo((string) $cupon->regalo_reserva)) : '',
            'regalo_parejas' => $existe ? $this->parejasLimpias((string) $cupon->regalo_parejas) : '',
            'regalo_corto' => $existe ? (string) $cupon->regalo_corto : '',
            // Una nueva, sin descuento puesto: que no parezca una promoción ya hecha.
            'porcentaje' => $existe ? (float) $cupon->porcentaje : 0.0,
            'pct_texto' => $existe ? rtrim(rtrim(number_format((float) $cupon->porcentaje, 3, '.', ''), '0'), '.') : '',
            'codigo' => $existe && !$duplicar ? (string) $cupon->codigo : '',
            'nombre' => $existe && !$duplicar ? (string) $cupon->nombre : '',
            // Por marca si lleva marca (o filtros de antes); si no, con la lista.
            'elegir' => !$existe ? '' : ($porTipo['marca'] || $porTipo['categoria'] || $porTipo['caracteristica'] ? 'marca' : (trim($lista) !== '' ? 'lista' : '')),
            'marcas' => $porTipo['marca'],
            'categorias' => $porTipo['categoria'],
            'caracteristicas' => $porTipo['caracteristica'],
            'lista' => $lista,
            'lista_tipo' => $existe ? ((string) $cupon->lista_tipo ?: 'auto') : 'auto',
            'excluir' => $excluir,
            'tamanos' => $existe ? KpcElegibles::tamanos(['tamanos' => (string) $cupon->tamanos]) : [],
            // «1» = sin packs; «2,4» = solo esos packs; vacío = sueltos y todos los packs.
            'incluir_packs' => (int) (!$existe || trim((string) $cupon->packs) !== '1'),
            'packs' => $existe ? array_values(array_filter(array_map([self::class, 'nivel'], explode(',', (string) $cupon->packs)),
                static fn ($n) => $n !== '' && $n !== '1' && substr($n, -1) !== '+')) : [],
            'excluir_especial' => $existe ? (int) $cupon->excluir_especial : 1,
            'sin_outlet' => $existe ? (int) $cupon->sin_outlet : 1,
            'fecha_desde' => $existe && !$duplicar ? substr((string) $cupon->fecha_desde, 0, 10) : '',
            'hora_desde' => $existe && !$duplicar ? $this->horaDe((string) $cupon->fecha_desde, '00:00') : '',
            'fecha_hasta' => $existe && !$duplicar ? substr((string) $cupon->fecha_hasta, 0, 10) : '',
            'hora_hasta' => $existe && !$duplicar ? $this->horaDe((string) $cupon->fecha_hasta, '23:59') : '',
            'activo' => $existe ? (int) $cupon->activo : 1,
            'en_ficha' => $existe ? (int) $cupon->en_ficha : 1,
            'ver' => !$existe || (int) $cupon->en_ficha ? ($verCanales ? 'canal' : 'todos') : 'nadie',
            'ver_canales' => $verCanales,
            'entra' => $cupon->auto_web ? 'todos' : ($canales ? 'canal' : 'no'),
            'canales' => $canales,
            'en_merchant' => $existe ? (int) $cupon->en_merchant : 1,
            'merchant_titulo' => $existe && !$duplicar ? (string) $cupon->merchant_titulo : '',
            'merchant_id' => $existe && !$duplicar ? (string) $cupon->merchant_id : '',
            'bloquear_priceshape' => $existe ? (int) $cupon->bloquear_priceshape : 0,
            'id_cart_rule' => $existe && !$duplicar ? (int) $cupon->id_cart_rule : 0,
            'leido_fichero' => null,
        ];
        foreach (['fecha_desde', 'fecha_hasta'] as $campo) {
            if (strpos($v[$campo], '0000') === 0) {
                $v[$campo] = '';
            }
        }

        return $v;
    }

    /** Lo que impide guardar (o comprobar). */
    private function errores(array $v, bool $alGuardar): array
    {
        if ($v['tipo'] === '') {
            return [$this->t('Elige primero qué promoción es: cupón con código, 2ª unidad, Special Price o regalo con la compra.')];
        }
        if ($v['elegir'] === '') {
            return [$this->t('Elige cómo se eligen los productos: por marca o con una lista.')];
        }
        $errores = [];
        $esRegalo = $v['tipo'] === 'regalo';
        $porArticulo = $v['tipo'] === 'special_price' && $v['modo_precio'] !== 'pct';
        if ($esRegalo) {
            // El regalo no lleva %: lo que hace falta es saber qué se regala y que esté a la venta.
            $articulos = $this->articulosDichos([$v['regalo_mismo'], $v['regalo_reserva']]);
            if ($v['regalo_modo'] === 'mismo' && $v['regalo_mismo'] === '') {
                $errores[] = $this->t('Falta el regalo: busca el artículo que entra en la cesta («el mismo para todos»).');
            } elseif ($v['regalo_mismo'] !== '' && empty($articulos[$v['regalo_mismo']]['vendible'])) {
                $errores[] = $this->t('El regalo %s no está a la venta (apagado, oculto o no se puede pedir): elige otro.', [$v['regalo_mismo']]);
            }
            if ($v['regalo_reserva'] !== '' && empty($articulos[$v['regalo_reserva']]['vendible'])) {
                $errores[] = $this->t('El regalo de reserva %s no está a la venta: elige otro o déjalo vacío.', [$v['regalo_reserva']]);
            }
            if ($v['regalo_reserva'] !== '' && $v['regalo_reserva'] === $v['regalo_mismo']) {
                $errores[] = $this->t('El regalo de reserva es el mismo artículo que el regalo: no sirve de reserva.');
            }
        } elseif ($porArticulo) {
            if ($v['porcentaje'] < 0 || $v['porcentaje'] > 90) {
                $errores[] = $this->t('El % general (para los que no traen el suyo) tiene que estar entre 0 y 90. Con 0, solo entran los de la lista con su valor.');
            }
        } elseif ($v['porcentaje'] <= 0 || $v['porcentaje'] > 90) {
            $errores[] = $v['porcentaje'] <= 0 ? $this->t('Falta el descuento: entre 1 y 90 %.') : $this->t('El descuento no puede pasar del 90 %.');
        }
        if (!$this->hayAmbito($v)) {
            $errores[] = $v['elegir'] === 'marca'
                ? $this->t('Falta la marca: escríbela y elígela.')
                : $this->t('Falta la lista: pega los EAN, referencias o ids, o sube el Excel del proveedor.');
        }
        if ($porArticulo && trim($v['lista']) === '' && $v['porcentaje'] <= 0) {
            $errores[] = $this->t('Para dar a cada artículo su % o su precio hace falta la lista con esos valores.');
        }
        if ($v['fecha_desde'] !== '' && $v['fecha_hasta'] !== ''
            && $this->fechaHora($v['fecha_hasta'], $v['hora_hasta'], '23:59', true) < $this->fechaHora($v['fecha_desde'], $v['hora_desde'], '00:00')) {
            $errores[] = $this->t('La fecha de fin es anterior a la de inicio.');
        }
        if ($v['tipo'] === 'cupon' && $v['ver'] === 'canal' && $v['ver_canales'] === []) {
            $errores[] = $this->t('Marca desde dónde se ve el cupón: Google, email o redes.');
        }
        if (!$alGuardar) {
            return $errores;
        }

        if ($v['tipo'] === 'special_price' && $v['fecha_hasta'] === '') {
            $errores[] = $this->t('Un Special Price necesita fecha de fin: es la que se ve en la ficha («hasta el…») y la que manda a Google.');
        }
        if ($v['tipo'] === 'special_price' && !KpcCupon::hayTablaEspeciales()) {
            $errores[] = $this->t('Para crear un Special Price hace falta el módulo kpyspecialprices (su tabla no está en esta tienda).');
        }
        if ($v['tipo'] === 'special_price' && $v['fecha_hasta'] !== ''
            && $this->fechaHora($v['fecha_hasta'], $v['hora_hasta'], '23:59', true) < date('Y-m-d H:i:s')) {
            $errores[] = $this->t('La fecha de fin ya ha pasado: ese Special Price no se vería nunca.');
        }
        if ($v['codigo'] === '' && $v['tipo'] === 'cupon') {
            $errores[] = $this->t('Un cupón necesita su código (lo que escribe el cliente): BOSKE10.');
        } elseif ($v['codigo'] !== '' && !preg_match('/^[A-Z0-9_-]{3,32}$/', $v['codigo'])) {
            $errores[] = $this->t('El código solo puede llevar letras sin acentos, números, «-» y «_», de 3 a 32.');
        } elseif ($v['tipo'] === 'cupon' && mb_strlen($v['codigo']) > 20) {
            // Google Merchant no admite códigos de más de 20 (y el cliente lo tiene que escribir).
            $errores[] = $this->t('El código de un cupón no puede pasar de 20 caracteres (Google no lo admite): %s tiene %d.', [$v['codigo'], mb_strlen($v['codigo'])]);
        } elseif ($v['codigo'] !== '' && strpos($v['codigo'], KpcSegunda::PREFIJO) === 0) {
            $errores[] = $this->t('Los códigos que empiezan por %s son de la 2ª unidad, elige otro.', [KpcSegunda::PREFIJO]);
        } elseif ($v['codigo'] !== '' && ($otro = $this->codigoOcupado($v['codigo'], (int) $v['id'])) !== '') {
            $errores[] = $otro;
        }

        return $errores;
    }

    /** Si el código ya lo usa otra promoción u otra regla de carrito, por qué; si no, vacío. */
    private function codigoOcupado(string $codigo, int $id): string
    {
        $db = Db::getInstance();
        if ((int) $db->getValue(
            'SELECT id_kpy_coupon FROM ' . _DB_PREFIX_ . "kpy_coupon WHERE codigo = '" . pSQL($codigo) . "' AND id_kpy_coupon <> " . $id
        )) {
            return $this->t('Ya hay otra promoción con el código %s.', [$codigo]);
        }
        $suRegla = $id ? (int) $db->getValue('SELECT id_cart_rule FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . $id) : 0;
        $deOtraRegla = (int) $db->getValue(
            'SELECT id_cart_rule FROM ' . _DB_PREFIX_ . "cart_rule WHERE code = '" . pSQL($codigo) . "' AND id_cart_rule <> " . $suRegla
        );

        return $deOtraRegla ? $this->t('El código %s ya lo usa otra regla de carrito de PrestaShop (id %d).', [$codigo, $deOtraRegla]) : '';
    }

    private function hayAmbito(array $v): bool
    {
        return $v['marcas'] || $v['categorias'] || $v['caracteristicas'] || trim($v['lista']) !== '';
    }

    /**
     * Lo marcado en unas fichas (tamaños o niveles de pack). Si está marcado todo lo que se
     * enseñaba, es «todos» y se guarda vacío: así el tamaño o el pack que entre mañana en la marca
     * también entra.
     *
     * @param mixed $marcados lo que llega (`tamanos[]`)
     * @param mixed $ofrecidos lo que se enseñaba, separado por «|»
     *
     * @return string[]
     */
    private function elegidos($marcados, $ofrecidos, callable $limpiar): array
    {
        $limpios = static fn ($lista): array => array_values(array_unique(array_filter(
            array_map(static fn ($x) => (string) $limpiar((string) $x), (array) $lista),
            static fn ($x) => $x !== ''
        )));
        $marcados = $limpios($marcados);
        $ofrecidos = $limpios(explode('|', (string) $ofrecidos));

        return $marcados !== [] && $ofrecidos !== [] && array_diff($ofrecidos, $marcados) === [] ? [] : $marcados;
    }

    /**
     * Qué formatos entran, como se guardan en `packs`: vacío = los sueltos y todos sus packs; «1» =
     * sin packs («Incluir los packs» sin marcar); «2,4» = solo esos packs, sin los sueltos. La 2ª
     * unidad nunca lleva packs (lo dice la regla, no hace falta guardarlo).
     */
    private function packsDe(array $v): string
    {
        if ($v['tipo'] === 'segunda_unidad') {
            return '';
        }
        if ($v['tipo'] === 'regalo') {
            // Los sueltos siempre; los packs solo si «los packs también regalan».
            return !empty($v['regalo_packs']) ? '' : '1';
        }

        return !$v['incluir_packs'] ? '1' : implode(',', $v['packs']);
    }

    /** Las parejas a mano (JSON clave → {r, s}) tal como se guardan: solo las que se entienden; vacío si ninguna. */
    private function parejasLimpias(string $json): string
    {
        $limpias = [];
        foreach (KpcRegalo::leerParejas($json) as $clave => $p) {
            $limpias[(string) $clave] = ['r' => KpcRegalo::escribirArticulo($p['r']), 's' => KpcRegalo::escribirArticulo($p['s'])];
        }

        return $limpias === [] ? '' : (string) json_encode($limpias, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lo que lleva una promoción de antes de la 1.6 y la pantalla ya no enseña (características,
     * categorías de la tienda, una lista junto a la marca): se dice y se mantiene al guardar.
     */
    private function deAntes(array $v): string
    {
        $partes = [];
        if ($v['caracteristicas']) {
            $nombres = [];
            foreach ($this->caracteristicas() as $grupo) {
                foreach ($grupo['valores'] as $valor) {
                    $nombres[$valor['id']] = $valor['nombre'];
                }
            }
            $partes[] = implode(', ', array_map(static fn ($id) => $nombres[$id] ?? '#' . $id, $v['caracteristicas']));
        }
        if ($v['generales']) {
            $nombres = array_column($this->categorias(), 'n', 'id');
            $partes[] = implode(', ', array_map(static fn ($id) => $nombres[$id] ?? '#' . $id, $v['generales']));
        }
        if (trim($v['lista']) !== '') {
            $partes[] = sprintf($this->t('una lista de %d líneas'), count(array_filter(preg_split('/\R/u', trim($v['lista'])) ?: [])));
        }

        return implode(' · ', $partes);
    }

    /** Un nivel de pack como se guarda: «1» (sueltos), «2» (packs de 2)… o «2+» (cualquier pack). */
    public static function nivel(string $valor): string
    {
        return preg_match('/^([1-9]\d?)(\+?)$/', trim($valor), $m) ? $m[1] . $m[2] : '';
    }

    /** Qué valor trae cada artículo de la lista: '' (ninguno), 'pct' o 'precio'. */
    private function conValor(string $tipo, string $modo): string
    {
        if ($tipo !== 'special_price') {
            return '';
        }

        return ['pct_articulo' => 'pct', 'precio_articulo' => 'precio'][$modo] ?? '';
    }

    /** Los ámbitos a guardar: los filtros tal cual, y la lista y lo excluido ya buscados. */
    private function ambitos(array $v): array
    {
        $ambitos = [];
        foreach (['marca' => 'marcas', 'categoria' => 'categorias', 'caracteristica' => 'caracteristicas'] as $tipo => $campo) {
            foreach ($v[$campo] as $id) {
                $ambitos[] = ['tipo' => $tipo, 'id' => $id];
            }
        }
        foreach ($this->leerListas($v) as $prefijo => $leido) {
            foreach ($leido['items'] as $item) {
                $unidades = $prefijo === '' && !empty($v['cupo_lista']) && isset($item['unidades']) ? (int) $item['unidades'] : null;
                // Con 0 unidades en la lista, ese artículo no entra (es como dejarlo fuera).
                $con = $unidades === 0 ? 'excluir_' : $prefijo;
                $ambitos[] = ($item['ipa'] > 0
                    ? ['tipo' => $con . 'combinacion', 'id' => $item['ipa']]
                    : ['tipo' => $con . 'producto', 'id' => $item['id_product']])
                    + ['valor' => $con === '' ? ($item['valor'] ?? null) : null, 'unidades' => $con === '' ? $unidades : null];
            }
        }

        return $ambitos;
    }

    /** @return array<string, array> '' (la lista) y 'excluir_' (lo que se deja fuera), ya buscados */
    private function leerListas(array $v): array
    {
        $salida = [];
        if (trim($v['lista']) !== '') {
            $salida[''] = KpcLista::buscar(KpcLista::celdas($v['lista']), $v['lista_tipo'], KpcLista::indice($this->idTienda), $this->conValor($v['tipo'], $v['modo_precio']), !empty($v['cupo_lista']));
        }
        if (trim($v['excluir']) !== '') {
            $salida['excluir_'] = KpcLista::buscar(KpcLista::celdas($v['excluir']), 'auto', KpcLista::indice($this->idTienda));
        }

        return $salida;
    }

    /**
     * Los productos de la promoción: a cuáles llega de verdad, con sus precios, y los que se quedan
     * fuera y por qué. Es lo que se ve al pulsar «Comprobar» y al abrir una promoción guardada.
     */
    private function previa(array $v): array
    {
        $ahora = date('Y-m-d H:i:s');
        $desde = $this->fechaHora($v['fecha_desde'], $v['hora_desde'], '00:00');
        $momento = $desde !== null && $desde > $ahora ? $desde : $ahora;
        $hasta = $this->fechaHora($v['fecha_hasta'], $v['hora_hasta'], '23:59', true);
        $esPrecio = $v['tipo'] === 'special_price';
        $esRegalo = $v['tipo'] === 'regalo';
        $ambitos = $this->ambitos($v);
        $esta = [
            'id_kpy_coupon' => $v['id'] ?: PHP_INT_MAX,
            'tipo' => $v['tipo'],
            'modo_precio' => $v['modo_precio'],
            'porcentaje' => $v['porcentaje'],
            'fecha_desde' => $desde ?? ($esPrecio ? $ahora : null),
            'fecha_hasta' => $hasta,
            'ambitos' => $ambitos,
            'tamanos' => $v['tamanos'],
            'packs' => $this->packsDe($v),
            'incluir_packs' => 1,
            'excluir_especial' => $esPrecio ? 1 : ($esRegalo ? 0 : $v['excluir_especial']),
            'sin_outlet' => $v['sin_outlet'],
            'regalo_modo' => $v['regalo_modo'],
            'regalo_por' => $v['regalo_por'],
            'cupo' => $v['cupo'],
            'cupo_lista' => (int) ($v['cupo_lista'] ?? 0),
            'regalo_tope' => $v['cupo'],
            'etiqueta' => $v['etiqueta'],
            'regalo_packs' => $v['regalo_packs'],
            'regalo_mismo' => $v['regalo_mismo'],
            'regalo_reserva' => $v['regalo_reserva'],
            'regalo_parejas' => $v['regalo_parejas'],
            'regalo_corto' => $v['regalo_corto'],
        ];
        $articulos = KpcAlcance::articulos($esta, $this->idTienda, $momento);

        // Los que se han quitado a mano (o con la lista de «dejar fuera»): se calcula sin esa lista
        // y se ve qué sobra. Salen al final, con «Volver a meter».
        $fuera = [];
        if (trim($v['excluir']) !== '') {
            $sinFuera = KpcAlcance::articulos(['ambitos' => array_values(array_filter(
                $ambitos,
                static fn ($a) => strpos((string) $a['tipo'], 'excluir_') !== 0
            ))] + $esta, $this->idTienda, $momento);
            $fuera = array_diff_key($sinFuera['entran'] + $sinFuera['especial'], $articulos['entran'], $articulos['especial']);
        }

        // Cupón, 2ª unidad y regalo: las demás promociones de cesta que estarán en marcha ese día. Si
        // mandan más, se quedan sus artículos; si mandan menos, esta se los quita. Un artículo lleva
        // una sola promoción: el regalo, que no descuenta en el precio, cede ante un cupón o una 2ª
        // unidad, y entre regalos manda el más antiguo.
        $otra = [];
        $quita = [];
        if (!$esPrecio) {
            $guardados = array_values(array_filter(
                KpcReparto::cupones($this->idTienda),
                static fn ($c) => !KpcElegibles::esPrecio($c)
            ));
            $itemsOtras = $this->itemsDe(array_values(array_diff(array_map('intval', array_column($guardados, 'id_kpy_coupon')), [(int) $v['id']])));
            foreach ($guardados as $c) {
                $idOtra = (int) $c['id_kpy_coupon'];
                if ($idOtra === (int) $v['id'] || !KpcReparto::enMarcha($c, $momento)) {
                    continue;
                }
                $comunes = array_intersect_key($articulos['entran'], $itemsOtras[$idOtra] ?? []);
                if ($comunes === []) {
                    continue;
                }
                if (KpcElegibles::manda($c, $esta) < 0) {
                    foreach (array_keys($comunes) as $clave) {
                        $otra[$clave] = (string) $c['codigo'];
                    }
                } else {
                    $quita[(string) $c['codigo']] = count($comunes);
                }
            }
        }
        $entran = array_diff_key($articulos['entran'], $otra);
        // Con unidades por artículo, los que ya vendieron las suyas no entran (hasta reabrir).
        $agotados = [];
        if ((int) $v['id'] > 0 && !empty($v['cupo_lista'])) {
            $agotados = array_intersect_key($entran, array_fill_keys(KpcCupo::agotadasDe((int) $v['id']), true));
            $entran = array_diff_key($entran, $agotados);
        }

        // Special Price: el precio de cada uno, y los que no pueden llevarlo (sin su valor, o con un
        // precio que no baja del de hoy).
        $objetivos = [];
        $sinPrecio = [];
        if ($esPrecio) {
            foreach ($entran as $clave => $idProducto) {
                $meta = KpcEspecial::meta($esta, $articulos['valores'][$clave] ?? null);
                $objetivo = $meta === null
                    ? ['ok' => false, 'motivo' => 'sin_valor']
                    : KpcEspecial::objetivo($meta, $idProducto, max(0, $clave), $this->idTienda);
                if ($objetivo['ok']) {
                    $objetivos[$clave] = $objetivo;
                } else {
                    $sinPrecio[$clave] = $objetivo['motivo'];
                }
            }
            $entran = array_diff_key($entran, $sinPrecio);
        }

        // Regalo: qué regala cada artículo que entra. Sin regalo (sin formato pequeño en su ficha y
        // sin reserva), no entra: no hay nada que darle.
        $regalos = [];
        $sinRegalo = [];
        $cuentasRegalo = ['sin_regalo' => 0, 'mismo' => 0, 'otra_marca' => 0, 'agotados' => 0, 'no_venta' => 0, 'kilos' => 0, 'otros' => 0];
        if ($esRegalo) {
            $parejas = KpcRegalo::parejas($esta, $entran, $articulos['niveles'] ?? [], $articulos['formatos'] ?? []);
            foreach ($parejas as $clave => $p) {
                if ($p['r'] === null) {
                    $sinRegalo[$clave] = $this->t(KpcRegalo::MOTIVOS[$p['aviso']] ?? $p['aviso']);
                }
            }
            $entran = array_diff_key($entran, $sinRegalo);
            $cuentasRegalo['sin_regalo'] = count($sinRegalo);
            $regalos = $this->regalosDichos($parejas, $entran, $cuentasRegalo, (string) ($v['regalo_corto'] ?? ''));
        }

        // Las filas: primero las que entran, luego las que no y por qué.
        $filas = [];
        foreach ($entran as $clave => $idProducto) {
            $filas[] = ['clave' => $clave, 'id_product' => $idProducto, 'estado' => 'entra', 'motivo' => ''];
        }
        foreach ($agotados as $clave => $idProducto) {
            $filas[] = ['clave' => $clave, 'id_product' => $idProducto, 'estado' => 'agotado', 'motivo' => ''];
        }
        foreach ($sinRegalo as $clave => $motivo) {
            $filas[] = ['clave' => $clave, 'id_product' => $articulos['entran'][$clave], 'estado' => 'sinregalo', 'motivo' => $motivo];
        }
        foreach ($otra as $clave => $codigo) {
            $filas[] = ['clave' => $clave, 'id_product' => $articulos['entran'][$clave], 'estado' => 'otra', 'motivo' => $codigo];
        }
        foreach ($sinPrecio as $clave => $motivo) {
            $filas[] = ['clave' => $clave, 'id_product' => $articulos['entran'][$clave], 'estado' => 'sinprecio',
                'motivo' => $this->t(KpcEspecial::MOTIVOS[$motivo] ?? $motivo)];
        }
        foreach ($articulos['especial'] as $clave => $idProducto) {
            $filas[] = ['clave' => $clave, 'id_product' => $idProducto, 'estado' => 'especial', 'motivo' => ''];
        }
        foreach ($fuera as $clave => $idProducto) {
            $filas[] = ['clave' => $clave, 'id_product' => $idProducto, 'estado' => 'fuera', 'motivo' => ''];
        }
        $total = count($filas);
        $filas = $this->detalles(array_slice($filas, 0, self::FILAS_PREVIA), $v, $objetivos, $articulos['valores'] ?? [], $regalos, $articulos['unidades'] ?? []);

        $listas = $this->leerListas($v);

        return [
            'es_precio' => $esPrecio,
            'es_regalo' => $esRegalo,
            'regalo' => $cuentasRegalo,
            'articulos' => count($entran),
            'productos' => count(array_unique($entran)),
            'especial' => count($articulos['especial']),
            'otra' => array_count_values($otra),
            'quita' => $quita,
            'sin_precio' => array_count_values(array_map(fn ($m) => $this->t(KpcEspecial::MOTIVOS[$m] ?? $m), $sinPrecio)),
            'fuera' => count($fuera),
            'agotados' => count($agotados),
            'filas' => $filas,
            'filas_n' => count($filas),
            'total' => $total,
            'recortada' => $total > self::FILAS_PREVIA,
            'momento' => $momento > $ahora ? date('d/m/Y', strtotime($momento)) : '',
            'lista' => isset($listas['']) ? $this->informe($listas['']) : null,
            'excluir' => isset($listas['excluir_']) ? $this->informe($listas['excluir_']) : null,
            'fichero' => $v['leido_fichero'] ? $this->informe($v['leido_fichero']) : null,
        ];
    }

    /** Cómo se ha leído una lista, dicho para la pantalla. */
    private function informe(array $leido): array
    {
        $como = [
            'codigo' => $this->t('EAN o referencias'),
            'id' => $this->t('ids de producto'),
            'combinacion' => $this->t('producto-formato'),
        ];

        return $leido + [
            'como' => $como[$leido['tipo']] ?? $leido['tipo'],
            'encontradas' => $leido['filas'] - count($leido['no']),
            'no_n' => count($leido['no']),
            'no_texto' => implode("\n", $leido['no']),
            'sin_valor_n' => count($leido['sin_valor'] ?? []),
            'sin_valor_texto' => implode("\n", $leido['sin_valor'] ?? []),
            'sin_unidades_n' => count($leido['sin_unidades'] ?? []),
            'sin_unidades_texto' => implode("\n", $leido['sin_unidades'] ?? []),
            'articulos' => count($leido['items']),
        ];
    }

    /**
     * El regalo de cada disparador, dicho para la tabla: nombre, precio, stock, de dónde sale
     * (pequeño de su ficha, el mismo para todos, a mano, reserva) y lo que conviene mirar (agotado,
     * no se vende, otra marca, el mismo artículo). Y las cuentas de esos avisos.
     *
     * @param array<int, array> $parejas lo de `KpcRegalo::parejas()`
     * @param array<int, int> $entran clave => id de producto
     *
     * @return array<int, array> clave del disparador => regalo dicho
     */
    private function regalosDichos(array $parejas, array $entran, array &$cuentas, string $cortoPromo = ''): array
    {
        $articulos = [];
        foreach ($parejas as $clave => $p) {
            if (isset($entran[$clave]) && $p['r'] !== null) {
                $articulos[] = $p['r'];
                if ($p['s'] !== null) {
                    $articulos[] = $p['s'];
                }
            }
        }
        if ($articulos === []) {
            return [];
        }
        $idioma = (int) $this->contexto->language->id;
        $datos = KpcRegalo::datos($articulos, $this->idTienda, $idioma);
        $p = _DB_PREFIX_;
        $marcaDe = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT p.id_product, IFNULL(m.name, '') AS marca FROM {$p}product p LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = p.id_manufacturer
             WHERE p.id_product IN (" . implode(',', array_map('intval', array_unique($entran))) . ')'
        ) ?: [] as $f) {
            $marcaDe[(int) $f['id_product']] = (string) $f['marca'];
        }
        $origenes = [
            'pequeno' => $this->t('el pequeño de su ficha'),
            'mismo' => $this->t('el mismo para todos'),
            'mano' => $this->t('elegido a mano'),
            'reserva' => $this->t('el de reserva (sin pequeño en su ficha)'),
        ];
        $salida = [];
        foreach ($parejas as $clave => $p) {
            if (!isset($entran[$clave]) || $p['r'] === null) {
                continue;
            }
            $k = (int) $p['r']['id'] . '-' . (int) $p['r']['ipa'];
            $d = $datos[$k] ?? null;
            $avisos = [];
            if ($d === null || !$d['vendible'] || $d['apagado']) {
                $avisos[] = ['clase' => 'ojo', 'texto' => $this->t('no se vende')];
                ++$cuentas['no_venta'];
            } elseif ($d['stock'] <= 0) {
                $avisos[] = ['clase' => 'ojo', 'texto' => $p['s'] !== null ? $this->t('agotado: entra el de reserva') : $this->t('agotado')];
                ++$cuentas['agotados'];
            }
            if ($p['aviso'] === 'mismo_articulo') {
                $avisos[] = ['clase' => 'ojo', 'texto' => $this->t('es el mismo artículo que se compra')];
                ++$cuentas['mismo'];
            }
            if ($d !== null && $d['marca'] !== '' && ($marcaDe[(int) $entran[$clave]] ?? '') !== '' && $d['marca'] !== $marcaDe[(int) $entran[$clave]]) {
                $avisos[] = ['clase' => 'info', 'texto' => $this->t('otra marca: %s', [$d['marca']])];
                ++$cuentas['otra_marca'];
            }
            $reserva = $p['s'] !== null ? ($datos[(int) $p['s']['id'] . '-' . (int) $p['s']['ipa']] ?? null) : null;
            // Más de lo mismo («+ 3 kg gratis»: en la landing, «Kilos gratis») u otra cosa («+ Regalo»).
            $mismaFicha = (int) $p['r']['id'] === (int) $entran[$clave];
            $etiqueta = KpcRegalo::etiqueta((string) ($d['formato'] ?? ''), $mismaFicha);
            $clase = KpcRegalo::clase($etiqueta, $mismaFicha);
            ++$cuentas[$clase === 'kilos' ? 'kilos' : 'otros'];
            $salida[(int) $clave] = [
                'valor' => $k,
                'etiqueta' => $etiqueta,
                'corto' => KpcRegalo::corto((string) ($d['formato'] ?? ''), (string) ($d['nombre'] ?? ''), $mismaFicha, $cortoPromo),
                'clase' => $clase,
                'texto' => $d !== null ? KpcRegalo::nombreCorto($d['nombre'], $d['formato']) : '#' . $k,
                'marca' => (string) ($d['marca'] ?? ''),
                'precio' => $d !== null ? (float) $d['precio'] : 0.0,
                'precio_txt' => $d !== null ? $this->euros((float) $d['precio']) : '',
                'stock' => $d !== null ? (int) $d['stock'] : 0,
                'n' => (int) $p['n'],
                'origen' => $origenes[$p['origen']] ?? $p['origen'],
                'a_mano' => $p['origen'] === 'mano',
                'reserva' => $reserva !== null ? KpcRegalo::nombreCorto($reserva['nombre'], $reserva['formato']) : '',
                'avisos' => $avisos,
                'enlace' => $d !== null ? $this->contexto->link->getProductLink((int) $p['r']['id'], null, null, null, $idioma, $this->idTienda, (int) $p['r']['ipa']) : '',
            ];
        }

        return $salida;
    }

    /**
     * Nombre, marca, formato, EAN y precios de las filas de la lista de productos.
     *
     * @param array<int, array{clave: int, id_product: int, estado: string, motivo: string}> $filas
     * @param array<int, array> $objetivos clave => Special Price calculado (`KpcEspecial::objetivo`)
     * @param array<int, array{v: float, de: ?int}> $valores clave => el % o el precio propio
     * @param array<int, array> $regalos clave => el regalo dicho (`regalosDichos()`), en un regalo
     */
    private function detalles(array $filas, array $v, array $objetivos, array $valores, array $regalos = [], array $unidades = []): array
    {
        if ($filas === []) {
            return [];
        }
        // Con unidades por artículo, lo vendido de cada uno (para decir cuántas quedan).
        $estadoArt = (int) $v['id'] > 0 && !empty($v['cupo_lista']) ? KpcCupo::estadoArticulos((int) $v['id']) : [];
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $idioma = (int) $this->contexto->language->id;
        $productos = implode(',', array_unique(array_map(static fn ($f) => (int) $f['id_product'], $filas)));
        $ipas = array_filter(array_map(static fn ($f) => max(0, (int) $f['clave']), $filas));

        $nombres = [];
        foreach ($db->executeS(
            "SELECT p.id_product, pl.name, p.ean13, IFNULL(m.name, '') AS marca
             FROM {$p}product p
             LEFT JOIN {$p}product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = {$idioma} AND pl.id_shop = {$this->idTienda}
             LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = p.id_manufacturer
             WHERE p.id_product IN ({$productos})"
        ) ?: [] as $f) {
            $nombres[(int) $f['id_product']] = $f;
        }
        $formatos = [];
        if ($ipas !== []) {
            foreach (array_chunk($ipas, 1000) as $trozo) {
                foreach ($db->executeS(
                    "SELECT pa.id_product_attribute AS ipa, pa.ean13, GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS nombre
                     FROM {$p}product_attribute pa
                     LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                     LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idioma}
                     WHERE pa.id_product_attribute IN (" . implode(',', $trozo) . ')
                     GROUP BY pa.id_product_attribute'
                ) ?: [] as $f) {
                    $formatos[(int) $f['ipa']] = $f;
                }
            }
        }

        $pct = (float) $v['porcentaje'];
        $esPrecio = $v['tipo'] === 'special_price';
        $locale = $this->contexto->getCurrentLocale();
        $moneda = $this->contexto->currency ? $this->contexto->currency->iso_code : 'EUR';
        $euros = static fn (float $x): string => $locale ? $locale->formatPrice($x, $moneda) : number_format($x, 2, ',', '.') . ' €';
        foreach ($filas as $i => $f) {
            $idProducto = (int) $f['id_product'];
            $ipa = max(0, (int) $f['clave']);
            $objetivo = $objetivos[(int) $f['clave']] ?? null;
            if ($objetivo !== null) {
                $hoy = (float) $objetivo['hoy'];
                $conPromo = (float) $objetivo['final'];
                $dto = KpcElegibles::pct((float) $objetivo['pct']) . ' %';
            } else {
                $hoy = (float) Product::getPriceStatic($idProducto, true, $ipa ?: null, 6, null, false, true);
                $conPromo = $v['tipo'] === 'segunda_unidad' ? $hoy * (2 - $pct / 100) : $hoy * (1 - $pct / 100);
                $dto = $esPrecio ? '' : KpcElegibles::pct($pct) . ' %';
            }
            $propio = $valores[(int) $f['clave']] ?? null;
            $regalo = $regalos[(int) $f['clave']] ?? null;
            if ($v['tipo'] === 'regalo') {
                // Lo que se lleva de más el cliente: el regalo (o los regalos, en un pack). Y cómo se
                // llama la tarjeta de la ficha («12 kg + 3 kg gratis»).
                $conPromo = $regalo !== null ? $regalo['precio'] * max(1, (int) $regalo['n']) : 0.0;
                $dto = '';
                if ($regalo !== null) {
                    $regalo['tarjeta'] = self::tarjeta($ipa ? (string) ($formatos[$ipa]['nombre'] ?? '') : '', ((int) $regalo['n'] > 1 ? (int) $regalo['n'] . ' x ' : '') . (string) $regalo['corto']);
                }
            }
            $filas[$i] += [
                'nombre' => (string) ($nombres[$idProducto]['name'] ?? '#' . $idProducto),
                'marca' => (string) ($nombres[$idProducto]['marca'] ?? ''),
                'formato' => $ipa ? (string) ($formatos[$ipa]['nombre'] ?? '') : '',
                'ean' => (string) ($ipa ? ($formatos[$ipa]['ean13'] ?? '') : ($nombres[$idProducto]['ean13'] ?? '')),
                'precio' => $euros($hoy),
                'con_promo' => $euros(round($conPromo, 2)),
                'regalo' => $regalo,
                'dto' => $dto,
                // Con unidades por artículo (de la lista): las suyas, lo vendido y lo que queda.
                'unidades' => $unidades[(int) $f['clave']] ?? null,
                'uds_vendidas' => (int) ($estadoArt[(int) $f['clave']]['vendidas'] ?? 0),
                'uds_quedan' => isset($unidades[(int) $f['clave']]) ? max(0, (int) $unidades[(int) $f['clave']] - (int) ($estadoArt[(int) $f['clave']]['vendidas'] ?? 0)) : null,
                // De dónde sale el descuento de cada uno, en un Special Price por artículo.
                'valor' => !$esPrecio || $v['modo_precio'] === 'pct' ? ''
                    : ($propio === null ? $this->t('el general') : ($propio['de'] === null ? $this->t('de la lista') : $this->t('como su suelto'))),
                'id' => $idProducto . ($ipa ? '-' . $ipa : ''),
                // Lo que se escribe en «dejar fuera» al quitarlo a mano: siempre «producto-formato».
                'quitar' => $idProducto . '-' . $ipa,
                'enlace' => $this->contexto->link->getProductLink($idProducto, null, null, null, $idioma, $this->idTienda, $ipa),
            ];
        }

        return $filas;
    }

    /** La lista de promociones, con su estado y lo que hace falta para leerla de un vistazo. */
    private function lista(): array
    {
        $filas = Db::getInstance()->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_shop = ' . $this->idTienda . ' ORDER BY id_kpy_coupon DESC'
        ) ?: [];
        $ambitos = KpcCupon::ambitosDe(array_map('intval', array_column($filas, 'id_kpy_coupon')));
        $marcas = array_column($this->marcas(), 'n', 'id');
        $categorias = array_column($this->categorias(), 'n', 'id');
        $rasgos = [];
        foreach ($this->caracteristicas() as $grupo) {
            foreach ($grupo['valores'] as $valor) {
                $rasgos[$valor['id']] = $valor['nombre'];
            }
        }

        // Las marcas a las que llega cada una (de lo ya repartido), para las que van por lista.
        $marcasDeItems = [];
        $p = _DB_PREFIX_;
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT i.id_kpy_coupon, m.name, COUNT(*) AS n FROM {$p}kpy_coupon_item i
             INNER JOIN {$p}product pr ON pr.id_product = i.id_product
             INNER JOIN {$p}manufacturer m ON m.id_manufacturer = pr.id_manufacturer
             GROUP BY i.id_kpy_coupon, m.id_manufacturer ORDER BY n DESC"
        ) ?: [] as $f) {
            $marcasDeItems[(int) $f['id_kpy_coupon']][] = (string) $f['name'];
        }
        foreach ($marcasDeItems as $id => $nombres) {
            $marcasDeItems[$id] = implode(', ', array_slice($nombres, 0, 3)) . (count($nombres) > 3 ? sprintf(' +%d', count($nombres) - 3) : '');
        }

        $ahora = date('Y-m-d H:i:s');
        $orden = ['en_marcha' => 0, 'programada' => 1, 'agotada' => 2, 'apagada' => 3, 'terminada' => 4];
        // El cupo de las que lo tienen: vendidas, en cestas y lo que queda.
        $cupos = KpcCupo::estado(array_map('intval', array_column(array_filter($filas, static fn ($f) => KpcCupo::tieneCupo($f)), 'id_kpy_coupon')));
        $salida = [];
        foreach ($filas as $f) {
            $id = (int) $f['id_kpy_coupon'];
            $porTipo = KpcElegibles::porTipo($ambitos[$id] ?? []);
            [$gamas, $generales] = KpcAlcance::gamas($porTipo['marca'], $porTipo['categoria'], false);
            $partes = [];
            foreach ($porTipo['marca'] as $m) {
                $nombre = $marcas[$m] ?? '#' . $m;
                $deLaMarca = array_map(fn ($c) => $this->sinMarca((string) ($categorias[$c] ?? '#' . $c), $nombre), $gamas[$m] ?? []);
                $partes[] = $nombre . ($deLaMarca ? ' (' . implode(', ', $deLaMarca) . ')' : '');
            }
            foreach ($generales as $c) {
                $partes[] = $categorias[$c] ?? '#' . $c;
            }
            foreach ($porTipo['caracteristica'] as $r) {
                $partes[] = $rasgos[$r] ?? '#' . $r;
            }
            $tamanos = KpcElegibles::tamanos($f);
            if ($tamanos) {
                $partes[] = implode(', ', array_map([KpcFormatos::class, 'etiqueta'], $tamanos));
            }
            $packs = $this->packsDichos((string) $f['packs'], (string) $f['tipo']);
            if ($packs !== '') {
                $partes[] = $packs;
            }
            $enLista = count($porTipo['producto']) + count($porTipo['combinacion']);
            if ($enLista) {
                // Una lista sola no dice nada: se añaden las marcas que lleva.
                $deLista = $porTipo['marca'] ? '' : ($marcasDeItems[$id] ?? '');
                $partes[] = sprintf($this->t('lista de %d'), $enLista) . ($deLista !== '' ? ' (' . $deLista . ')' : '');
            }
            $fuera = count($porTipo['excluir_producto']) + count($porTipo['excluir_combinacion']);
            if ($fuera) {
                $partes[] = sprintf($this->t('menos %d'), $fuera);
            }

            $desde = (string) $f['fecha_desde'];
            $hasta = (string) $f['fecha_hasta'];
            $cupo = $cupos[$id] ?? null;
            $estado = !$f['activo'] ? 'apagada'
                : ($cupo !== null && $cupo['agotado'] !== '' && KpcReparto::enMarcha($f, $ahora) ? 'agotada'
                : (KpcReparto::enMarcha($f, $ahora) ? 'en_marcha' : ($desde > $ahora ? 'programada' : 'terminada')));
            $dias = '';
            if ($estado === 'en_marcha' && strpos($hasta, '0000') !== 0 && $hasta !== '') {
                $n = (int) ceil((strtotime($hasta) - time()) / 86400);
                $dias = $n <= 1 ? $this->t('acaba hoy') : sprintf($this->t('quedan %d días'), $n);
            } elseif ($estado === 'programada') {
                $n = (int) ceil((strtotime($desde) - time()) / 86400);
                $dias = $n <= 1 ? $this->t('empieza mañana') : sprintf($this->t('empieza en %d días'), $n);
            }

            $canales = array_filter(explode(',', (string) $f['auto_canal']));
            $modo = (string) ($f['modo_precio'] ?? 'pct');
            $porArticulo = (int) ($f['cupo_lista'] ?? 0) === 1 ? $this->porArticulo($id) : ['unidades' => 0, 'vendidas' => 0, 'reservadas' => 0, 'quedan' => 0, 'articulos' => 0, 'agotados' => 0];
            $salida[] = [
                'id' => $id,
                'codigo' => (string) $f['codigo'],
                'nombre' => (string) $f['nombre'],
                'tipo' => (string) $f['tipo'],
                'modo' => $modo,
                'regalo' => (string) $f['tipo'] === 'regalo' ? $this->regaloDicho($f) : '',
                'pct' => KpcElegibles::pct((float) $f['porcentaje']),
                'desde' => $this->fechaCorta($desde),
                'hasta' => $this->fechaCorta($hasta),
                'dias' => $dias,
                'estado' => $estado,
                'orden' => $orden[$estado],
                'ambito' => implode(' · ', $partes),
                'formatos' => (int) $f['formatos'],
                'aviso' => (string) $f['aviso'],
                'entra' => $f['auto_web'] ? ['web'] : $canales,
                'en_ficha' => (int) $f['en_ficha'],
                'ver' => array_values(array_filter(explode(',', (string) ($f['ver_canal'] ?? '')))),
                'en_merchant' => (int) $f['en_merchant'],
                // Cómo se llama un Special Price («Liquidación») y, con cupo, cómo va.
                'etiqueta' => $this->t(KpcCupo::nombreEtiqueta((string) ($f['etiqueta'] ?? ''))),
                'etiqueta_clase' => KpcCupo::claseEtiqueta((string) ($f['etiqueta'] ?? '')),
                'cupo' => (int) ($cupo['cupo'] ?? 0),
                'cupo_unidad' => $this->t(KpcCupo::unidadDe((string) $f['tipo'])),
                'vendidas' => (int) ($cupo['vendidas'] ?? 0),
                'reservadas' => (int) ($cupo['reservadas'] ?? 0),
                'quedan' => $porArticulo['articulos'] > 0 && (int) ($cupo['cupo'] ?? 0) <= 0 ? $porArticulo['quedan'] : (int) ($cupo['quedan'] ?? 0),
                'agotado' => $cupo !== null && $cupo['agotado'] !== '' ? date('d/m/Y H:i', (int) strtotime($cupo['agotado'])) : '',
                // Con unidades por artículo (de la lista): sumadas, y cuántos artículos ya se agotaron.
                'cupo_lista' => (int) ($f['cupo_lista'] ?? 0),
                'uds_lista' => $porArticulo['unidades'],
                'articulos_lista' => $porArticulo['articulos'],
                'articulos_agotados' => $porArticulo['agotados'],
            ];
        }
        usort($salida, static fn ($a, $b) => [$a['orden'], -$a['id']] <=> [$b['orden'], -$b['id']]);

        return $salida;
    }

    /** Cómo va el cupo de una promoción, para el formulario: vendidas, en cestas, quedan y si está agotada. */
    private function cupoDicho(int $id): ?array
    {
        $e = KpcCupo::estado([$id])[$id] ?? null;
        if ($e === null || ($e['cupo'] <= 0 && empty($e['lista']))) {
            return null;
        }
        $porArticulo = !empty($e['lista']) ? $this->porArticulo($id) : null;
        $sinTotal = $porArticulo !== null && $e['cupo'] <= 0;

        return [
            'cupo' => $sinTotal ? $porArticulo['unidades'] : $e['cupo'],
            'vendidas' => $e['vendidas'],
            'reservadas' => $e['reservadas'],
            'quedan' => $sinTotal ? $porArticulo['quedan'] : (int) $e['quedan'],
            'agotado' => $e['agotado'] !== '' ? date('d/m/Y \a \l\a\s H:i', (int) strtotime($e['agotado'])) : '',
            'lista' => $porArticulo !== null,
            'articulos' => $porArticulo['articulos'] ?? 0,
            'agotados' => $porArticulo['agotados'] ?? 0,
        ];
    }

    /** Las unidades por artículo de una promoción (de la lista), sumadas, para la lista y el formulario. */
    private function porArticulo(int $id): array
    {
        $salida = ['unidades' => 0, 'vendidas' => 0, 'reservadas' => 0, 'quedan' => 0, 'articulos' => 0, 'agotados' => 0];
        foreach (KpcCupo::estadoArticulos($id) as $a) {
            ++$salida['articulos'];
            $salida['unidades'] += $a['unidades'];
            $salida['vendidas'] += $a['vendidas'];
            $salida['reservadas'] += $a['reservadas'];
            $salida['quedan'] += $a['quedan'];
            if ($a['agotado'] !== '') {
                ++$salida['agotados'];
            }
        }

        return $salida;
    }

    /**
     * Los Special Price que no son de esta pantalla (los que carga Julián o el SQL antiguo), por
     * tandas: para verlos todos en el mismo sitio. Solo se enseñan; se cambian donde se cargaron.
     */
    private function especialesDeFuera(): array
    {
        if (!KpcCupon::hayTablaEspeciales()) {
            return [];
        }
        $p = _DB_PREFIX_;
        // Por tandas (mismas fechas): una carga de Julián lleva a veces un % distinto por artículo.
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT DATE(sp.date_from) AS desde, DATE(sp.expire) AS hasta, MIN(sp.date_from) AS empieza,
                    MIN(sp.special_discount) AS minimo, MAX(sp.special_discount) AS maximo, COUNT(*) AS n,
                    GROUP_CONCAT(DISTINCT m.name ORDER BY m.name SEPARATOR ', ') AS marcas
             FROM {$p}kpy_special_price sp
             LEFT JOIN {$p}kpy_coupon_precio cp ON cp.id_product = sp.id_product
                  AND cp.id_product_attribute = sp.id_product_attribute AND cp.date_from = sp.date_from
             LEFT JOIN {$p}product pr ON pr.id_product = sp.id_product
             LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = pr.id_manufacturer
             WHERE cp.id_kpy_coupon IS NULL AND sp.id_shop = {$this->idTienda} AND sp.expire >= NOW()
             GROUP BY DATE(sp.date_from), DATE(sp.expire)
             ORDER BY desde DESC"
        ) ?: [];
        $ahora = date('Y-m-d H:i:s');

        return array_map(fn ($f) => [
            'estado' => (string) $f['empieza'] > $ahora ? 'programada' : 'en_marcha',
            'pct' => (float) $f['minimo'] === (float) $f['maximo']
                ? KpcElegibles::pct((float) $f['minimo']) . ' %'
                : KpcElegibles::pct((float) $f['minimo']) . '–' . KpcElegibles::pct((float) $f['maximo']) . ' %',
            'desde' => $this->fechaCorta((string) $f['desde']),
            'hasta' => $this->fechaCorta((string) $f['hasta']),
            'n' => (int) $f['n'],
            'marcas' => mb_strimwidth((string) $f['marcas'], 0, 90, '…'),
        ], $filas);
    }

    /** Los ficheros de Google Merchant, sacados de estas mismas promociones. */
    private function merchant(): array
    {
        $direcciones = KpcMerchant::direcciones();
        $que = [
            'promociones' => [$this->t('Promociones (cupones y 2ª unidad)'), $this->t('Marketing → Promociones → Añadir promociones desde un archivo')],
            'precios' => [$this->t('Precios de oferta (Special Price)'), $this->t('Fuentes de datos → fuente complementaria «Promos: precio oferta»')],
            'etiquetas' => [$this->t('Etiquetas de los artículos'), $this->t('Fuentes de datos → fuente complementaria «Promos: etiquetas»')],
        ];
        $salida = [];
        foreach ($que as $cual => $textos) {
            $salida[] = [
                'fichero' => KpcMerchant::NOMBRES[$cual],
                'que' => $textos[0],
                'donde' => $textos[1],
                'url' => (string) ($direcciones[$cual] ?? ''),
            ];
        }

        return $salida;
    }

    /** @return array<int, array{id: int, n: string, c: int}> las marcas, con cuántos productos a la venta tienen */
    private function marcas(): array
    {
        static $marcas = null;
        if ($marcas !== null) {
            return $marcas;
        }
        $p = _DB_PREFIX_;
        $marcas = array_map(static fn ($f) => ['id' => (int) $f['id_manufacturer'], 'n' => (string) $f['name'], 'c' => (int) $f['n']],
            Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                "SELECT m.id_manufacturer, m.name, COUNT(ps.id_product) AS n
                 FROM {$p}manufacturer m
                 INNER JOIN {$p}product p ON p.id_manufacturer = m.id_manufacturer
                 INNER JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = {$this->idTienda}
                      AND ps.active = 1 AND ps.visibility IN ('both', 'catalog', 'search') AND ps.available_for_order = 1
                 GROUP BY m.id_manufacturer
                 ORDER BY m.name"
            ) ?: []);

        return $marcas;
    }

    /**
     * Lo que se enseña al elegir una marca, para afinar sin escribir nada: sus **gamas** (el árbol
     * de categorías que cuelga de su página de marca), sus **tamaños** («saco 12 kg») y sus **niveles
     * de pack**, cada uno con cuántos productos a la venta tiene. Solo para esta pantalla, en el
     * back office; la tienda no lo usa.
     *
     * Cambia poco (es el catálogo) y cuesta unas décimas de segundo calcularlo: se guarda 10
     * minutos en la caché de PrestaShop, con la versión del módulo en el nombre.
     *
     * @return array<int, array{g: array, t: array, p: array}> por id de marca. `g`: [id, sangría,
     *         nombre, productos] en orden de árbol; `t`: [tamaño, grupo, nombre corto, productos];
     *         `p`: [nivel, productos]
     */
    private function detalleMarcas(): array
    {
        $fichero = _PS_CACHE_DIR_ . 'kpycoupons-marcas-' . $this->modulo->version . '-' . $this->idTienda . '.json';
        if (is_file($fichero) && filemtime($fichero) > time() - 600) {
            $guardado = json_decode((string) file_get_contents($fichero), true);
            if (is_array($guardado)) {
                return $guardado;
            }
        }
        $salida = $this->calcularDetalleMarcas();
        // Desde la consola no se escribe: el fichero sería de otro usuario y la tienda no podría
        // renovarlo.
        if (PHP_SAPI !== 'cli') {
            @file_put_contents($fichero, json_encode($salida, JSON_UNESCAPED_UNICODE), LOCK_EX);
        }

        return $salida;
    }

    /** @see detalleMarcas() */
    private function calcularDetalleMarcas(): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $idioma = (int) $this->contexto->language->id;
        $aLaVenta = "ps.id_shop = {$this->idTienda} AND ps.active = 1 AND ps.visibility IN ('both', 'catalog', 'search') AND ps.available_for_order = 1";

        $marcaDe = [];
        foreach ($db->executeS(
            "SELECT p.id_product, p.id_manufacturer FROM {$p}product p
             INNER JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND {$aLaVenta}
             WHERE p.id_manufacturer > 0"
        ) ?: [] as $f) {
            $marcaDe[(int) $f['id_product']] = (int) $f['id_manufacturer'];
        }
        if ($marcaDe === []) {
            return [];
        }

        // Tamaños y packs. Todos los formatos (los apagados hacen falta para saber qué es pack de
        // qué), pero solo cuentan los que se enseñan y no son OUTLET.
        $formatos = [];
        $apagados = [];
        foreach ($db->executeS(
            "SELECT pa.id_product, pa.id_product_attribute AS ipa, " . KpcAlcance::sqlApagado() . " AS apagado,
                    GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS nombre
             FROM {$p}product_attribute pa
             INNER JOIN {$p}product_shop ps ON ps.id_product = pa.id_product AND {$aLaVenta}
             LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idioma}
             GROUP BY pa.id_product_attribute"
        ) ?: [] as $f) {
            $formatos[(int) $f['id_product']][] = ['ipa' => (int) $f['ipa'], 'nombre' => (string) $f['nombre']];
            if ((int) $f['apagado']) {
                $apagados[(int) $f['ipa']] = true;
            }
        }
        $packs = KpcAlcance::tablaPacks(array_keys($formatos));
        $tamanos = [];
        $niveles = [];
        foreach ($marcaDe as $idProducto => $marca) {
            if (!isset($formatos[$idProducto])) {
                $niveles[$marca][1][$idProducto] = true;
                continue;
            }
            $suyos = KpcFormatos::niveles($formatos[$idProducto], $packs[$idProducto] ?? []);
            foreach ($formatos[$idProducto] as $c) {
                if (isset($apagados[$c['ipa']]) || KpcFormatos::esOutlet($c['nombre'])) {
                    continue;
                }
                $n = $suyos[$c['ipa']];
                if ($n['tam'] !== '') {
                    $tamanos[$marca][$n['tam']][$idProducto] = true;
                }
                $niveles[$marca][$n['nivel']][$idProducto] = true;
            }
        }

        // Gamas: el árbol de la categoría de cada marca, con los productos a la venta de cada rama
        // (la categoría y lo que cuelga de ella).
        $gamas = [];
        if (KpcAlcance::hayTabla('kpy_manufacturer')) {
            $nodos = [];
            foreach ($db->executeS(
                "SELECT km.id_manufacturer, c.id_category, c.nleft, c.nright, CAST(c.level_depth AS SIGNED) - CAST(r.level_depth AS SIGNED) AS nivel, cl.name
                 FROM {$p}kpy_manufacturer km
                 INNER JOIN {$p}category r ON r.id_category = km.id_category_related
                 INNER JOIN {$p}category c ON c.nleft > r.nleft AND c.nright < r.nright AND c.active = 1
                 INNER JOIN {$p}category_lang cl ON cl.id_category = c.id_category AND cl.id_lang = {$idioma} AND cl.id_shop = {$this->idTienda}
                 WHERE km.id_category_related > 0
                 ORDER BY km.id_manufacturer, c.nleft"
            ) ?: [] as $f) {
                $nodos[(int) $f['id_manufacturer']][] = $f;
            }
            $enCategoria = [];
            $todas = [];
            foreach ($nodos as $suyos) {
                foreach ($suyos as $n) {
                    $todas[(int) $n['id_category']] = true;
                }
            }
            foreach (array_chunk(array_keys($todas), 2000) as $trozo) {
                foreach ($db->executeS(
                    "SELECT id_category, id_product FROM {$p}category_product WHERE id_category IN (" . implode(',', $trozo) . ')'
                ) ?: [] as $f) {
                    if (isset($marcaDe[(int) $f['id_product']])) {
                        $enCategoria[(int) $f['id_category']][] = (int) $f['id_product'];
                    }
                }
            }
            $nombres = array_column($this->marcas(), 'n', 'id');
            foreach ($nodos as $marca => $suyos) {
                foreach ($suyos as $n) {
                    $productos = [];
                    foreach ($suyos as $otro) {
                        if ((int) $otro['nleft'] >= (int) $n['nleft'] && (int) $otro['nright'] <= (int) $n['nright']) {
                            foreach ($enCategoria[(int) $otro['id_category']] ?? [] as $idProducto) {
                                if ($marcaDe[$idProducto] === $marca) {
                                    $productos[$idProducto] = true;
                                }
                            }
                        }
                    }
                    if ($productos !== []) {
                        $gamas[$marca][] = [(int) $n['id_category'], max(0, (int) $n['nivel'] - 1),
                            $this->sinMarca((string) $n['name'], (string) ($nombres[$marca] ?? '')), count($productos)];
                    }
                }
            }
        }

        $salida = [];
        foreach (array_unique(array_values($marcaDe)) as $marca) {
            $t = [];
            foreach ($tamanos[$marca] ?? [] as $tam => $productos) {
                [$grupo, $corto] = KpcFormatos::grupo($tam);
                $t[] = [$tam, $grupo, $corto, count($productos)];
            }
            usort($t, static fn ($a, $b) => KpcFormatos::orden($a[0]) <=> KpcFormatos::orden($b[0]));
            $n = [];
            foreach ($niveles[$marca] ?? [] as $nivel => $productos) {
                $n[] = [(int) $nivel, count($productos)];
            }
            sort($n);
            $salida[$marca] = ['g' => $gamas[$marca] ?? [], 't' => $t, 'p' => $n];
        }

        return $salida;
    }

    /**
     * Cómo se llama la tarjeta de la ficha: el tamaño del disparador y lo que regala («12 kg + 3 kg
     * gratis», «24 latas + Snacks»); sin formato, «+ 3 kg gratis».
     */
    public static function tarjeta(string $formatoDisparador, string $corto): string
    {
        $tam = $formatoDisparador !== '' ? KpcFormatos::tamano($formatoDisparador) : '';
        $tam = trim((string) preg_replace('/^(?:saco|bolsa|caja|envase|bote)\s+/u', '', $tam));
        $packs = $formatoDisparador !== '' ? KpcFormatos::unidades($formatoDisparador) : 1;
        $tam = ($packs > 1 && $tam !== '' ? $packs . ' x ' : '') . $tam;

        return ($tam !== '' ? $tam . ' ' : '') . '+ ' . $corto;
    }

    /** Lo que regala una promoción, para la lista: «kilos gratis: el pequeño de su ficha», «regalo: Boske… · Caja 6 latas · por pedido · tope 200». */
    private function regaloDicho(array $f): string
    {
        $partes = [];
        if ((string) ($f['regalo_modo'] ?? 'pequeno') === 'mismo') {
            $dichos = $this->articulosDichos([(string) ($f['regalo_mismo'] ?? '')]);
            $partes[] = $this->t('regalo: %s', [$dichos !== [] ? (string) reset($dichos)['texto'] : $this->t('un artículo')]);
        } else {
            $partes[] = $this->t('kilos gratis: el pequeño de su ficha');
        }
        if ((string) ($f['regalo_por'] ?? 'unidad') === 'pedido') {
            $partes[] = $this->t('uno por pedido');
        }

        return implode(' · ', $partes);
    }

    /** «sin packs», «solo packs de 2»; vacío si entran los sueltos y todos sus packs. */
    private function packsDichos(string $packs, string $tipo): string
    {
        $niveles = array_values(array_filter(array_map([self::class, 'nivel'], explode(',', $packs))));
        if ($tipo === 'segunda_unidad' || $niveles === []) {
            return '';
        }
        $sueltos = in_array('1', $niveles, true);
        $dePacks = array_map(
            fn ($n) => substr($n, -1) === '+' ? $this->t('cualquier pack') : sprintf($this->t('packs de %s'), $n),
            array_values(array_diff($niveles, ['1']))
        );
        if ($dePacks === []) {
            return $this->t('sin packs');
        }

        return ($sueltos ? $this->t('sueltos y') : $this->t('solo')) . ' ' . implode(', ', $dePacks);
    }

    /** «Royal canin razas pienso gato» en Royal Canin → «Razas pienso gato». */
    private function sinMarca(string $nombre, string $marca): string
    {
        $sin = $marca !== '' ? (preg_replace('/^' . preg_quote($marca, '/') . '\s+/iu', '', $nombre) ?? $nombre) : $nombre;
        $sin = $sin !== '' ? $sin : $nombre;

        return mb_strtoupper(mb_substr($sin, 0, 1)) . mb_substr($sin, 1);
    }

    /**
     * Las categorías con su camino («Perros › Comida › Pienso › Pienso perros royal canin»), para
     * buscarlas escribiendo. Sin «Raíz» ni «Inicio», que no dicen nada.
     *
     * @return array<int, array{id: int, n: string, r: string, c: int}>
     */
    private function categorias(): array
    {
        static $categorias = null;
        if ($categorias !== null) {
            return $categorias;
        }
        $p = _DB_PREFIX_;
        $idioma = (int) $this->contexto->language->id;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT c.id_category, c.id_parent, c.level_depth, cl.name,
                    (SELECT COUNT(*) FROM {$p}category_product cp WHERE cp.id_category = c.id_category) AS n
             FROM {$p}category c
             INNER JOIN {$p}category_lang cl ON cl.id_category = c.id_category AND cl.id_lang = {$idioma} AND cl.id_shop = {$this->idTienda}
             WHERE c.active = 1
             ORDER BY c.nleft"
        ) ?: [];
        $porId = [];
        foreach ($filas as $f) {
            $porId[(int) $f['id_category']] = $f;
        }
        $categorias = [];
        foreach ($filas as $f) {
            if ((int) $f['level_depth'] < 2) {
                continue;
            }
            $camino = [];
            $padre = (int) $f['id_parent'];
            while (isset($porId[$padre]) && (int) $porId[$padre]['level_depth'] >= 3) {
                array_unshift($camino, (string) $porId[$padre]['name']);
                $padre = (int) $porId[$padre]['id_parent'];
            }
            $categorias[] = [
                'id' => (int) $f['id_category'],
                'n' => (string) $f['name'],
                'r' => implode(' › ', $camino),
                'c' => (int) $f['n'],
            ];
        }

        return $categorias;
    }

    /** Las características que se ofrecen como filtro, con sus valores. */
    private function caracteristicas(): array
    {
        static $salida = null;
        if ($salida !== null) {
            return $salida;
        }
        $p = _DB_PREFIX_;
        $idioma = (int) $this->contexto->language->id;
        $salida = [];
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT fv.id_feature, fv.id_feature_value, fvl.value,
                    (SELECT COUNT(*) FROM {$p}feature_product fp WHERE fp.id_feature_value = fv.id_feature_value) AS n
             FROM {$p}feature_value fv
             INNER JOIN {$p}feature_value_lang fvl ON fvl.id_feature_value = fv.id_feature_value AND fvl.id_lang = {$idioma}
             WHERE fv.id_feature IN (" . implode(',', array_keys(self::CARACTERISTICAS)) . ') AND fv.custom = 0
             ORDER BY fv.id_feature, fv.position, fvl.value'
        ) ?: [];
        foreach (self::CARACTERISTICAS as $id => $nombre) {
            $valores = [];
            foreach ($filas as $f) {
                if ((int) $f['id_feature'] === $id && (int) $f['n'] > 0) {
                    $valores[] = ['id' => (int) $f['id_feature_value'], 'nombre' => (string) $f['value'], 'n' => (int) $f['n']];
                }
            }
            if ($valores !== []) {
                $salida[] = ['id' => $id, 'nombre' => $this->t($nombre), 'valores' => $valores];
            }
        }

        return $salida;
    }

    /** @return array<int, array<int, int>> id de cupón => [clave => id de producto], de lo guardado */
    private function itemsDe(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_kpy_coupon, clave, id_product FROM ' . _DB_PREFIX_ . 'kpy_coupon_item WHERE id_kpy_coupon IN (' . implode(',', $ids) . ')'
        ) ?: [] as $f) {
            $salida[(int) $f['id_kpy_coupon']][(int) $f['clave']] = (int) $f['id_product'];
        }

        return $salida;
    }

    /**
     * Los artículos de la lista (o de «dejar fuera») como «producto-formato», uno por línea, con su
     * valor detrás si lo llevan («7214-90684;13»).
     */
    private function comoTexto(array $ambitos, string $prefijo): string
    {
        $lineas = [];
        $combinaciones = [];
        foreach ($ambitos as $a) {
            $valor = isset($a['valor']) && $a['valor'] !== null ? ';' . rtrim(rtrim(number_format((float) $a['valor'], 3, ',', ''), '0'), ',') : '';
            if ($a['tipo'] === $prefijo . 'producto') {
                $lineas[] = $a['id'] . '-0' . $valor;
            } elseif ($a['tipo'] === $prefijo . 'combinacion') {
                $combinaciones[(int) $a['id']] = $valor;
            }
        }
        if ($combinaciones !== []) {
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_product, id_product_attribute FROM ' . _DB_PREFIX_ . 'product_attribute
                 WHERE id_product_attribute IN (' . implode(',', array_keys($combinaciones)) . ')
                 ORDER BY id_product, id_product_attribute'
            ) ?: [] as $f) {
                $lineas[] = $f['id_product'] . '-' . $f['id_product_attribute'] . $combinaciones[(int) $f['id_product_attribute']];
            }
        }

        return implode("\n", $lineas);
    }

    /** Un código interno para la 2ª unidad y el Special Price (el cliente no lo escribe nunca). */
    private function codigoPorDefecto(array $v): string
    {
        $marca = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(strtr($this->primeraMarca($v), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N', 'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ñ' => 'N']))) ?? '';
        $prefijo = ['segunda_unidad' => '2UD', 'special_price' => 'SP', 'cupon' => 'CUPON', 'regalo' => 'REGALO'][$v['tipo']] ?? 'PROMO';
        if ($v['tipo'] === 'special_price' && ($v['etiqueta'] ?? '') !== '') {
            $prefijo = ['liquidacion' => 'LIQ', 'outlet' => 'OUTLET', 'bajada' => 'BAJADA'][$v['etiqueta']] ?? 'SP';
        }
        if ($v['tipo'] === 'regalo') {
            $cuanto = ($v['regalo_modo'] ?? 'pequeno') === 'mismo' ? 'MISMO' : 'PEQ';
        } else {
            $cuanto = $v['tipo'] === 'special_price' && $v['modo_precio'] !== 'pct'
                ? ($v['modo_precio'] === 'precio_articulo' ? 'PRECIOS' : 'PCTS')
                : (string) (int) round($v['porcentaje']);
        }
        $base = mb_substr($prefijo . '-' . ($marca !== '' ? mb_substr($marca, 0, 12) . '-' : '')
            . $cuanto . '-' . date('ymd', strtotime($v['fecha_desde'] ?: 'now')), 0, 28);
        $codigo = $base;
        for ($n = 2; $this->codigoOcupado($codigo, (int) $v['id']) !== ''; ++$n) {
            $codigo = $base . '-' . $n;
        }

        return $codigo;
    }

    /** «Boske −10 %», «2ª unidad al 30 % en Boske», «Special Price −13 % en Royal Canin». */
    private function nombrePorDefecto(array $v): string
    {
        $pct = KpcElegibles::pct((float) $v['porcentaje']);
        $marca = $this->primeraMarca($v);
        $en = $marca !== '' ? ' en ' . $marca : '';
        switch ($v['tipo']) {
            case 'segunda_unidad':
                return '2ª unidad al ' . $pct . ' %' . $en;
            case 'special_price':
                $nombre = KpcCupo::nombreEtiqueta((string) ($v['etiqueta'] ?? ''));

                return $v['modo_precio'] === 'pct' ? $nombre . ' -' . $pct . ' %' . $en : $nombre . ' por artículo' . $en;
            case 'regalo':
                return (($v['regalo_modo'] ?? 'pequeno') === 'mismo' ? 'Regalo con la compra' : 'Kilos gratis') . $en;
            default:
                return trim(($marca !== '' ? $marca . ' ' : '') . '-' . $pct . ' %');
        }
    }

    /** Lo que lee el cliente en Google: sin mayúsculas gritadas, 60 caracteres como mucho. */
    private function tituloPorDefecto(array $v): string
    {
        $pct = KpcElegibles::pct((float) $v['porcentaje']);
        $marca = $this->primeraMarca($v);
        if ($v['tipo'] === 'regalo') {
            $titulo = (($v['regalo_modo'] ?? 'pequeno') === 'mismo' ? 'Regalo con la compra' : 'Kilos gratis') . ($marca !== '' ? ' en ' . $marca : '');
        } else {
            $titulo = $v['tipo'] === 'segunda_unidad'
                ? '2a unidad al ' . $pct . ' %' . ($marca !== '' ? ' en ' . $marca : '')
                : $pct . ' % de descuento' . ($marca !== '' ? ' en ' . $marca : '') . ' con el codigo ' . $v['codigo'];
        }

        return mb_substr($titulo, 0, 60);
    }

    private function primeraMarca(array $v): string
    {
        if (count($v['marcas']) !== 1) {
            return '';
        }
        foreach ($this->marcas() as $m) {
            if ($m['id'] === $v['marcas'][0]) {
                return $m['n'];
            }
        }

        return '';
    }

    private function fechaLimpia($valor): string
    {
        $valor = trim((string) $valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : '';
    }

    private function horaLimpia($valor): string
    {
        $valor = trim((string) $valor);

        return preg_match('/^\d{2}:\d{2}$/', $valor) ? $valor : '';
    }

    /** «2026-10-01» + «09:00» → «2026-10-01 09:00:00». El fin llega hasta el último segundo. */
    private function fechaHora(string $fecha, string $hora, string $porDefecto, bool $fin = false): ?string
    {
        if ($fecha === '') {
            return null;
        }

        return $fecha . ' ' . ($hora !== '' ? $hora : $porDefecto) . ($fin ? ':59' : ':00');
    }

    /**
     * Si la fecha nueva es el mismo minuto que la guardada, se queda la guardada (con sus
     * segundos): el formulario no los enseña, y cambiarlos rehacería todos los Special Price.
     */
    private function mismoMinuto(?string $nueva, string $guardada): ?string
    {
        return $nueva !== null && $guardada !== '' && substr($nueva, 0, 16) === substr($guardada, 0, 16) ? $guardada : $nueva;
    }

    private function horaDe(string $fechaHora, string $porDefecto): string
    {
        $hora = substr($fechaHora, 11, 5);

        return $hora !== '' && $hora !== $porDefecto ? $hora : '';
    }

    private function fechaCorta(string $fecha): string
    {
        if ($fecha === '' || strpos($fecha, '0000') === 0) {
            return '';
        }
        $t = strtotime($fecha);

        return $t ? date('d/m/Y', $t) : '';
    }

    /** En PrestaShop 9 `Module::trans()` es protegido: se traduce por el módulo. */
    private function t(string $texto, array $reemplazos = []): string
    {
        $traducido = $this->modulo->tAdmin($texto);

        return $reemplazos ? vsprintf($traducido, $reemplazos) : $traducido;
    }
}
