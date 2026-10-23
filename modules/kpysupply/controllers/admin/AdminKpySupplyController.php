<?php
/**
 * La pestaña Catálogo → «Kompy · Acuerdos con proveedores». Ordenada como se usa:
 *
 *   0. Cómo funciona y cómo está (cuántos acuerdos en marcha, programados, terminados).
 *   1. Los acuerdos (lotes: tipo + fechas + promoción + marca): sus artículos, las ventas del
 *      periodo (con su Excel), la lista a Excel, prolongar o cerrar, borrar.
 *   2. Subir una lista (pegada o el Excel del proveedor): comprobar (qué es cada fila, qué no se
 *      encuentra, qué se cruza con otro acuerdo) y guardar.
 *   3. Ajustes: qué estados de pedido no cuentan como venta.
 *
 * Los acuerdos de una promoción se meten en su formulario (Kompy · Promociones, paso «Acuerdo con
 * el proveedor»); aquí se ven como un lote más, con su promoción al lado.
 */
class AdminKpySupplyController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = 'Kompy · Acuerdos con proveedores';
    }

    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);
        $this->addCSS(_MODULE_DIR_ . 'kpysupply/views/css/admin.css?v=' . $this->module->version);
        $this->addJS(_MODULE_DIR_ . 'kpysupply/views/js/admin.js?v=' . $this->module->version);
    }

    public function initContent(): void
    {
        $url = $this->context->link->getAdminLink('AdminKpySupply');
        $this->descargar();
        $comprobacion = $this->procesar($url);
        $this->context->smarty->assign(['kps' => $this->datosPantalla($url, $comprobacion)]);
        $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'kpysupply/views/templates/admin/pantalla.tpl');
        parent::initContent();
    }

    /** Todo lo que pinta la pantalla (también lo usa la vista previa en consola, sin sesión del back office). */
    public function datosPantalla(string $url, ?array $comprobacion = null): array
    {
        $lotes = KsAcuerdos::lotes();
        $cuentas = ['vivo' => 0, 'programado' => 0, 'terminado' => 0, 'articulos_vivos' => 0, 'solapes' => 0];
        $porClave = [];
        foreach ($lotes as $l) {
            ++$cuentas[$l['estado']];
            if ($l['estado'] === 'vivo') {
                $cuentas['articulos_vivos'] += (int) $l['n'];
            }
            if ($l['solapes'] > 0) {
                ++$cuentas['solapes'];
            }
            $porClave[$l['clave']] = $l;
        }
        $base = ['n' => 0, 'n_lista' => 0, 'marca' => '', 'promo' => null, 'solapes' => 0, 'valor_texto' => ''];

        $ver = null;
        $clave = (string) Tools::getValue('ver');
        if ($clave !== '' && ($lote = KsAcuerdos::lote($clave)) !== null) {
            $ver = ($porClave[$clave] ?? $lote + $base) + ['filas' => KsAcuerdos::filasDeLote($lote)];
            foreach ($ver['filas'] as &$f) {
                $f['coste_texto'] = (float) ($f['coste'] ?? 0) > 0 ? self::euros((float) $f['coste']) : '';
            }
            unset($f);
            $ver['n'] = count($ver['filas']);
            $ver['hasta_corto'] = $ver['hasta'] !== null ? substr((string) $ver['hasta'], 0, 10) : '';
        }
        $informe = null;
        $clave = (string) Tools::getValue('informe');
        if ($clave !== '' && ($lote = KsAcuerdos::lote($clave)) !== null) {
            $pendientes = (bool) Tools::getValue('pendientes');
            $informe = ($porClave[$clave] ?? $lote + $base) + ['ventas' => KsVentas::deLote($lote, $pendientes), 'pendientes' => $pendientes];
            $conVentas = array_values(array_filter($informe['ventas']['articulos'], static fn (array $a): bool => (int) $a['uds'] > 0));
            $informe['sin_ventas'] = count($informe['ventas']['articulos']) - count($conVentas);
            $informe['ventas']['articulos'] = $conVentas;
            foreach ($informe['ventas']['articulos'] as &$a) {
                $a['neto_texto'] = self::euros((float) $a['neto']);
                $a['valor_texto'] = KsTipos::valorTexto((string) $lote['tipo'], (float) $a['valor']);
                $a['importe_texto'] = $a['importe'] !== null ? self::euros((float) $a['importe']) : '';
                $a['coste_texto'] = $a['coste'] !== null ? self::euros((float) $a['coste']) : '';
                $a['margen_texto'] = $a['margen'] !== null ? self::euros((float) $a['margen']) : '';
            }
            unset($a);
            foreach ($informe['ventas']['detalle'] as &$d) {
                $d['fecha_texto'] = date('d/m/Y H:i', strtotime((string) $d['fecha']));
                $d['bruto_texto'] = self::euros((float) $d['bruto']);
                $d['vales_texto'] = self::euros((float) $d['vales']);
                $d['neto_texto'] = self::euros((float) $d['neto']);
                $d['importe_texto'] = $d['importe'] !== null ? self::euros((float) $d['importe']) : '';
            }
            unset($d);
            $t = $informe['ventas']['totales'];
            $informe['totales_texto'] = [
                'neto' => self::euros((float) $t['neto']), 'vales' => self::euros((float) $t['vales']),
                'importe' => $t['importe'] !== null ? self::euros((float) $t['importe']) : '',
                'coste' => $t['coste'] !== null ? self::euros((float) $t['coste']) : '',
                'margen' => $t['margen'] !== null ? self::euros((float) $t['margen']) : '',
            ];
            $informe['es_redencion'] = (string) $lote['tipo'] === KsTipos::REDENCION;
            $informe['es_coste'] = (string) $lote['tipo'] === KsTipos::COSTE;
            $informe['periodo_texto'] = $lote['hasta'] !== null && $lote['hasta'] < date('Y-m-d H:i:s') ? $lote['fechas'] : 'desde el ' . date('d/m/Y', strtotime((string) $lote['desde'])) . ' hasta hoy';
            $informe['hay_ventas'] = (int) $t['uds'] > 0;
            $informe['excel'] = $url . '&exportar=ventas&lote=' . urlencode($clave) . ($pendientes ? '&pendientes=1' : '');
            $informe['url_pendientes'] = $url . '&informe=' . urlencode($clave) . ($pendientes ? '' : '&pendientes=1') . '#detalle';
        }

        $form = $comprobacion['form'] ?? [
            'tipo' => KsTipos::REDENCION, 'valor_texto' => '', 'promo' => 0, 'desde_texto' => date('Y-m-d'), 'hasta_texto' => '',
            'sin_fin' => false, 'texto' => '', 'fichero' => '', 'solapes' => 'parar', 'promo_texto' => '',
        ];

        return [
            'url' => $url,
            'url_promociones' => $this->context->link->getAdminLink('AdminKpyPromociones'),
            'version' => $this->module->version,
            'aviso' => (string) Tools::getValue('aviso'),
            'error' => (string) Tools::getValue('error'),
            'tipos' => KsTipos::TEXTOS,
            'tipo_coste' => KsTipos::COSTE,
            'tipo_redencion' => KsTipos::REDENCION,
            'lotes' => $lotes,
            'cuentas' => $cuentas,
            'promo_filtro' => (int) Tools::getValue('promo'),
            'ver' => $ver,
            'informe' => $informe,
            'form' => $form,
            'promos' => KsAcuerdos::promosParaElegir(),
            'comprobacion' => $comprobacion,
            'hoy' => date('Y-m-d'),
            'excel' => class_exists('ZipArchive'),
            'estados' => $this->estados(),
            'estados_fuera_texto' => implode(', ', array_column(array_filter($this->estados(), static fn (array $e): bool => $e['fuera']), 'nombre')),
        ];
    }

    /** «12,34 €», como lo escribe la tienda. */
    public static function euros(float $x): string
    {
        return number_format($x, 2, ',', '.') . ' €';
    }

    /** Los estados de pedido de la tienda, con los que no cuentan como venta marcados. */
    private function estados(): array
    {
        static $estados = null;
        if ($estados !== null) {
            return $estados;
        }
        $fuera = array_flip(KsVentas::estadosFuera());
        $estados = [];
        foreach (OrderState::getOrderStates((int) $this->context->language->id) ?: [] as $e) {
            $id = (int) $e['id_order_state'];
            $estados[] = ['id' => $id, 'nombre' => (string) $e['name'], 'pagado' => (int) $e['logable'] === 1, 'fuera' => isset($fuera[$id])];
        }

        return $estados;
    }

    // ---------- lo que llega ----------

    /** Lo que llega por POST: cada acción hace lo suyo y vuelve a la pantalla con su aviso; comprobar (o un guardar que se para) devuelve la comprobación para pintarla. */
    private function procesar(string $url): ?array
    {
        $volver = static function (string $texto, bool $error = false, string $ancla = '') use ($url): void {
            Tools::redirectAdmin($url . '&' . ($error ? 'error' : 'aviso') . '=' . urlencode($texto) . $ancla);
        };
        try {
            if (Tools::isSubmit('kpsComprobar') || Tools::isSubmit('kpsGuardar')) {
                $c = $this->comprobar($this->leerFormulario());
                if (!Tools::isSubmit('kpsGuardar') || $c['errores'] !== [] || $c['items'] === []) {
                    return $c;
                }
                if ($c['solapes'] > 0 && $c['form']['solapes'] === 'parar') {
                    $c['pide_politica'] = true;

                    return $c;
                }
                $r = KsAcuerdos::guardar($c['items'], KsTipos::id($c['form']['tipo']), $c['form']['desde'], $c['form']['hasta'], $c['form']['promo'] > 0 ? $c['form']['promo'] : null, $c['form']['solapes']);
                $texto = sprintf('Guardado: %d artículos nuevos y %d actualizados', $r['guardados'], $r['actualizados']);
                if ($r['iguales'] > 0) {
                    $texto .= sprintf(', %d ya estaban igual', $r['iguales']);
                }
                if ($r['saltados'] > 0) {
                    $texto .= sprintf(', %d saltados por tener otro acuerdo en esas fechas', $r['saltados']);
                }
                if ($r['recortados'] > 0 || $r['borrados'] > 0) {
                    $texto .= sprintf(', %d acuerdos anteriores recortados y %d quitados para que no se pisen', $r['recortados'], $r['borrados']);
                }
                $no = (array) $c['r']['no'];
                if ($no !== []) {
                    $texto .= sprintf('. %d filas sin encontrar: %s', count($no), implode(', ', array_slice($no, 0, 12)) . (count($no) > 12 ? '…' : ''));
                }
                $volver($texto . '.', false, $c['form']['promo'] > 0 ? '&promo=' . (int) $c['form']['promo'] . '#acuerdos' : '#acuerdos');
            }
            if (Tools::isSubmit('kpsFinal')) {
                $lote = KsAcuerdos::lote((string) Tools::getValue('clave'));
                if ($lote === null) {
                    $volver('Ese acuerdo ya no existe.', true);
                }
                if ((int) $lote['id_kpy_coupon'] > 0) {
                    $volver('Este acuerdo es de una promoción: sus fechas se cambian en la promoción (Kompy · Promociones, paso 3) y el acuerdo la sigue al guardarla.', true);
                }
                $modo = (string) Tools::getValue('modo');
                $hasta = null;
                if ($modo === 'hoy') {
                    $hasta = date('Y-m-d 23:59:59');
                    if ($hasta < $lote['desde']) {
                        $volver('Este acuerdo empieza el ' . date('d/m/Y', strtotime((string) $lote['desde'])) . ', después de hoy: no se puede cerrar hoy. Bórralo, o ponle una fecha de fin.', true, '&ver=' . urlencode((string) $lote['clave']) . '#detalle');
                    }
                } elseif ($modo === 'fecha') {
                    $f = (string) Tools::getValue('hasta');
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
                        $volver('Falta la fecha.', true, '&ver=' . urlencode((string) $lote['clave']) . '#detalle');
                    }
                    $hasta = $f . ' 23:59:59';
                    if ($hasta < $lote['desde']) {
                        $volver('La fecha de fin es anterior a la de inicio (' . $lote['fechas'] . ').', true, '&ver=' . urlencode((string) $lote['clave']) . '#detalle');
                    }
                }
                $r = KsAcuerdos::cambiarFinal($lote, $hasta);
                if (is_string($r)) {
                    $volver($r, true, '&ver=' . urlencode((string) $lote['clave']) . '#detalle');
                }
                $nuevo = KsAcuerdos::claveLote((int) $lote['id_supplier_promo_type'], $lote['desde'], $hasta, 0, (int) $lote['id_manufacturer']);
                $volver(sprintf('%d artículos, ahora %s.', $r, KsAcuerdos::fechasTexto($lote['desde'], $hasta)), false, '&ver=' . urlencode($nuevo) . '#detalle');
            }
            if (Tools::isSubmit('kpsBorrarLote')) {
                $lote = KsAcuerdos::lote((string) Tools::getValue('clave'));
                if ($lote === null) {
                    $volver('Ese acuerdo ya no existe.', true);
                }
                if ((string) Tools::getValue('confirmar') !== 'BORRAR') {
                    $volver('Para borrar un acuerdo entero hay que escribir BORRAR en la casilla.', true, '&ver=' . urlencode((string) $lote['clave']) . '#detalle');
                }
                $n = KsAcuerdos::borrarLote($lote);
                $volver(sprintf('Acuerdo borrado: %d artículos (%s, %s).', $n, $lote['tipo_titulo'], $lote['fechas']), false, '#acuerdos');
            }
            if (Tools::isSubmit('kpsBorrarFila')) {
                $id = (int) Tools::getValue('id');
                $clave = (string) Tools::getValue('clave');
                KsAcuerdos::borrarFila($id);
                $volver('Artículo quitado del acuerdo.', false, '&ver=' . urlencode($clave) . '#detalle');
            }
            if (Tools::isSubmit('kpsSincronizarPromo')) {
                // Los artículos de la promoción se le piden a kpycoupons por su hook de consulta; si no contesta, no se toca nada.
                $id = (int) Tools::getValue('id');
                $r = KsAcuerdos::ponerAlDia($id, (int) $this->context->shop->id);
                $volver($r['texto'], !$r['ok'], '&promo=' . $id . '#acuerdos');
            }
            if (Tools::isSubmit('kpsAjustes')) {
                $ids = array_values(array_unique(array_filter(array_map('intval', (array) Tools::getValue('kps_estados_fuera', [])))));
                if ($ids === []) {
                    $volver('Hay que dejar marcado al menos un estado (cancelado, como poco).', true, '#ajustes');
                }
                Configuration::updateGlobalValue(KsVentas::CONF_ESTADOS, implode(',', $ids));
                $volver(sprintf('Ajustes guardados: %d estados de pedido no cuentan como venta.', count($ids)), false, '#ajustes');
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpysupply, pantalla: ' . $e->getMessage(), 3, null, 'Kpysupply', 0, true);
            $volver('No se ha podido hacer: ' . $e->getMessage(), true);
        }

        return null;
    }

    /** El formulario de subir una lista, leído y limpio. Si viene de una promoción, sus fechas mandan. */
    private function leerFormulario(): array
    {
        $tipo = (string) Tools::getValue('kps_tipo');
        if (!in_array($tipo, KsTipos::claves(), true)) {
            $tipo = KsTipos::REDENCION;
        }
        $valorTexto = trim((string) Tools::getValue('kps_valor'));
        $valor = $valorTexto !== '' ? KsLectura::leerValor($valorTexto, 'pct') : null;
        $promo = (int) Tools::getValue('kps_promo');
        $sinFin = (bool) Tools::getValue('kps_sin_fin');
        $desdeTexto = trim((string) Tools::getValue('kps_desde'));
        $hastaTexto = trim((string) Tools::getValue('kps_hasta'));
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desdeTexto) ? $desdeTexto . ' 00:00:00' : null;
        $hasta = !$sinFin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hastaTexto) ? $hastaTexto . ' 23:59:59' : null;
        $promoTexto = '';
        if ($promo > 0) {
            $f = Db::getInstance()->getRow('SELECT codigo, nombre, fecha_desde, fecha_hasta FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . $promo);
            if ($f) {
                $desde = KsAcuerdos::fechaValida($f['fecha_desde'] ?? '') ?? $desde;
                $hasta = KsAcuerdos::fechaValida($f['fecha_hasta'] ?? '');
                $sinFin = $hasta === null;
                $desdeTexto = $desde !== null ? substr($desde, 0, 10) : $desdeTexto;
                $hastaTexto = $hasta !== null ? substr($hasta, 0, 10) : '';
                $promoTexto = trim((string) $f['codigo'] . ' · ' . (string) $f['nombre']);
            } else {
                $promo = 0;
            }
        }
        $texto = (string) Tools::getValue('kps_texto');
        $fichero = $_FILES['kps_fichero'] ?? null;
        $nombreFichero = '';
        $ficheroError = '';
        if (is_array($fichero) && (string) ($fichero['name'] ?? '') !== '') {
            $codigoSubida = (int) ($fichero['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($codigoSubida !== UPLOAD_ERR_OK) {
                $ficheroError = 'El fichero «' . (string) $fichero['name'] . '» no ha llegado entero (código ' . $codigoSubida . ': ¿demasiado grande?). Pega la lista copiada del Excel.';
            } elseif (!empty($fichero['tmp_name']) && is_uploaded_file($fichero['tmp_name'])) {
                try {
                    $texto = KsLista::aTexto(KsLectura::deFichero($fichero['tmp_name'], (string) $fichero['name']));
                    $nombreFichero = (string) $fichero['name'];
                } catch (Throwable $e) {
                    $ficheroError = 'No se ha podido leer el fichero «' . (string) $fichero['name'] . '» (' . $e->getMessage() . '). Pega la lista copiada del Excel.';
                }
            }
        }
        $politica = (string) Tools::getValue('kps_solapes');
        if (!in_array($politica, ['parar', 'sustituir', 'saltar'], true)) {
            $politica = 'parar';
        }
        // La columna del valor: vacío = la decide la comprobación; −1 = ninguna (todos al general).
        $columna = trim((string) Tools::getValue('kps_columna'));
        // Con un fichero nuevo, la columna elegida para el anterior ya no vale.
        if ($nombreFichero !== '') {
            $columna = '';
        }

        return [
            'tipo' => $tipo, 'valor_texto' => $valorTexto, 'valor' => $valor, 'promo' => $promo, 'promo_texto' => $promoTexto,
            'desde_texto' => $desdeTexto, 'hasta_texto' => $hastaTexto, 'desde' => $desde, 'hasta' => $hasta, 'sin_fin' => $sinFin,
            'texto' => $texto, 'fichero' => $nombreFichero, 'fichero_error' => $ficheroError, 'solapes' => $politica,
            'columna' => $columna === '' ? null : (int) $columna, 'columna_texto' => $columna,
        ];
    }

    /** La comprobación de la lista: qué es cada fila, qué falta, qué se cruza; con lo que la pantalla pinta. */
    private function comprobar(array $form): array
    {
        $tipo = (string) $form['tipo'];
        $esCoste = $tipo === KsTipos::COSTE;
        $idTipo = KsTipos::id($tipo);
        $errores = [];
        if ($idTipo <= 0) {
            $errores[] = 'Faltan los tipos de acuerdo en la base de datos: hay que reinstalar el módulo.';
        }
        if ((string) ($form['fichero_error'] ?? '') !== '') {
            $errores[] = (string) $form['fichero_error'];
        }
        if ($form['desde'] === null) {
            $errores[] = 'Falta la fecha de inicio.';
        }
        if (!$form['sin_fin'] && $form['hasta'] === null) {
            $errores[] = 'Falta la fecha de fin (o marca «hasta nueva orden»).';
        }
        if ($form['desde'] !== null && $form['hasta'] !== null && $form['hasta'] < $form['desde']) {
            $errores[] = 'La fecha de fin es anterior a la de inicio.';
        }
        if (trim((string) $form['texto']) === '') {
            $errores[] = 'Pega la lista del proveedor (o sube su fichero).';
        }
        if (!$esCoste && $form['valor_texto'] !== '' && $form['valor'] === null) {
            $errores[] = 'El % general no se entiende (un número entre 0 y 90).';
        }
        $r = ['tipo' => '', 'columna' => 0, 'columna_valor' => 0, 'motivo_valor' => 'ninguna', 'cabeceras' => [], 'filas' => 0, 'apagados' => 0, 'items' => [], 'no' => [], 'sin_valor' => [], 'repetidos' => []];
        $filas = trim((string) $form['texto']) !== '' ? KsLectura::celdas((string) $form['texto']) : [];
        if ($filas !== []) {
            $r = KsLista::comprobar($filas, KsTipos::como($tipo), $esCoste ? null : $form['valor'], KsLectura::indice((int) $this->context->shop->id), $form['columna'] ?? null);
        } elseif (trim((string) $form['texto']) !== '') {
            $errores[] = 'En la lista no hay ningún código (EAN, referencia…).';
        }
        if ($r['items'] === [] && $r['sin_valor'] !== []) {
            $errores[] = $esCoste
                ? 'Ningún artículo tiene un precio de coste: la lista necesita una columna con el precio (con cabecera «Neto», «Coste» o «Precio»; «PVP» no vale) o elígela abajo.'
                : 'Ningún artículo tiene su %: pon el % general, o elige abajo la columna del % (las que no dicen «Dto.» o «%» en la cabecera no se leen solas).';
        }
        // Qué se ha leído de dónde, en palabras.
        $cab = (array) $r['cabeceras'];
        $nombre = static fn (int $c): string => '«' . ($cab[$c - 1] ?? ('columna ' . $c)) . '»';
        $lectura = (int) $r['columna'] > 0 ? 'Los códigos, de la columna ' . (int) $r['columna'] . ($cab ? ' ' . $nombre((int) $r['columna']) : '') . '. ' : '';
        if ((int) $r['columna_valor'] > 0) {
            $lectura .= ($esCoste ? 'El precio' : 'El %') . ' de cada fila, de la columna ' . (int) $r['columna_valor'] . ($cab ? ' ' . $nombre((int) $r['columna_valor']) : '')
                . ($r['motivo_valor'] === 'cabecera' ? ' (por su cabecera)' : ($r['motivo_valor'] === 'dos' ? ' (la lista es código + valor)' : ($r['motivo_valor'] === 'elegida' ? ' (elegida a mano)' : '')))
                . (!$esCoste && $form['valor'] !== null ? '; donde falte, el general.' : '.');
        } elseif ($esCoste) {
            $lectura .= 'Ninguna columna dice que sea el precio de coste.';
        } else {
            $lectura .= $form['valor'] !== null ? 'Todos al % general (' . KsTipos::valorTexto($tipo, (float) $form['valor']) . '): ninguna columna dice «Dto.» o «%».' : 'Ninguna columna dice «Dto.» o «%».';
        }

        $items = $r['items'];
        $datos = $items !== [] ? KsAcuerdos::datosArticulos(array_map(static fn (array $i): array => [$i['id_product'], $i['ipa']], $items)) : [];
        $solapes = $form['desde'] !== null && $items !== [] ? KsLista::solapes($items, $form['desde'], $form['hasta'], KsAcuerdos::existentes($items, $form['desde'], $form['hasta'])) : [];
        $pintar = [];
        $nSolapes = 0;
        $nIguales = 0;
        $nAvisos = 0;
        foreach ($items as $it) {
            $clave = $it['id_product'] . '-' . $it['ipa'];
            $d = $datos[$clave] ?? ['producto' => 'producto #' . $it['id_product'], 'formato' => '', 'ean' => '', 'ref' => '', 'coste' => 0.0, 'marca' => ''];
            $aviso = $esCoste ? KsLista::avisoCoste((float) $it['valor'], (float) $d['coste']) : '';
            if ($aviso !== '') {
                ++$nAvisos;
            }
            $cruces = [];
            $igual = false;
            foreach ($solapes[$clave] ?? [] as $f) {
                if ($form['desde'] !== null && KsLista::igual($f, $idTipo, (float) $it['valor'], $form['desde'], $form['hasta'])) {
                    $igual = true;
                    continue;
                }
                $claveTipo = KsTipos::clave((int) $f['id_supplier_promo_type']);
                $cruces[] = KsTipos::titulo($claveTipo) . ' ' . KsTipos::valorTexto($claveTipo, (float) $f['value']) . ', ' . KsAcuerdos::fechasTexto((string) $f['date_from'], $f['date_to'])
                    . ((int) ($f['id_kpy_coupon'] ?? 0) > 0 ? ' (de una promoción)' : '');
            }
            if ($cruces !== []) {
                ++$nSolapes;
            } elseif ($igual) {
                ++$nIguales;
            }
            $pintar[] = $d + [
                'codigo' => (string) $it['codigo'],
                'valor_texto' => KsTipos::valorTexto($tipo, (float) $it['valor']),
                'con_dto' => $tipo === KsTipos::FACTURA && (float) $d['coste'] > 0 ? self::euros(round((float) $d['coste'] * (1 - (float) $it['valor'] / 100), 2)) : '',
                'coste_texto' => (float) $d['coste'] > 0 ? self::euros((float) $d['coste']) : '',
                'aviso' => $aviso,
                'cruces' => $cruces,
                'igual' => $igual && $cruces === [],
            ];
        }

        return [
            'form' => $form,
            'errores' => $errores,
            'r' => $r,
            'items' => $errores === [] ? $items : [],
            'filas' => $pintar,
            'solapes' => $nSolapes,
            'iguales' => $nIguales,
            'avisos_coste' => $nAvisos,
            'pide_politica' => false,
            'no_texto' => implode("\n", (array) $r['no']),
            'sin_valor_texto' => implode(', ', (array) $r['sin_valor']),
            'repetidos_texto' => implode('; ', (array) $r['repetidos']),
            'lectura' => $lectura,
            'columnas' => count($cab) >= 2 ? $cab : [],
        ];
    }

    // ---------- Excel ----------

    /** `&exportar=lista|ventas&lote=…`: manda el fichero y termina. */
    private function descargar(): void
    {
        $que = (string) Tools::getValue('exportar');
        if (!in_array($que, ['lista', 'ventas'], true)) {
            return;
        }
        $lote = KsAcuerdos::lote((string) Tools::getValue('lote'));
        if ($lote === null) {
            return;
        }
        $filas = KsAcuerdos::filasDeLote($lote);
        $marca = (string) ($filas[0]['marca'] ?? '');
        $promo = (int) $lote['id_kpy_coupon'] > 0 ? (string) (Db::getInstance()->getValue('SELECT codigo FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . (int) $lote['id_kpy_coupon']) ?: '#' . $lote['id_kpy_coupon']) : '';
        $tipo = (string) $lote['tipo'];
        $desdeCorto = substr((string) $lote['desde'], 0, 10);
        $hastaCorto = $lote['hasta'] !== null ? substr((string) $lote['hasta'], 0, 10) : 'hasta nueva orden';
        $hojas = [];
        if ($que === 'lista') {
            $cuerpo = [];
            foreach ($filas as $f) {
                $cuerpo[] = [(string) $f['ean'], (string) $f['ref'], (string) $f['producto'], (string) $f['formato'], (string) $f['marca'], KsTipos::titulo($tipo), (float) $f['value'], $desdeCorto, $hastaCorto, $promo,
                    $f['origen'] === KsAcuerdos::ORIGEN_PROMO ? 'paso de la promoción' : 'lista'];
            }
            $hojas[] = ['nombre' => 'Acuerdo', 'cabecera' => ['EAN', 'Referencia', 'Producto', 'Formato', 'Marca', 'Tipo de acuerdo', $tipo === KsTipos::COSTE ? 'Precio de coste (€, sin IVA)' : 'Valor (%)', 'Desde', 'Hasta', 'Promoción', 'Origen'],
                'filas' => $cuerpo, 'anchos' => [16, 16, 44, 18, 16, 24, 14, 12, 18, 18, 20]];
            $nombre = 'acuerdo-' . Tools::str2url(KsTipos::titulo($tipo)) . ($marca !== '' ? '-' . Tools::str2url($marca) : '') . '-' . $desdeCorto;
        } else {
            $pendientes = (bool) Tools::getValue('pendientes');
            $v = KsVentas::deLote($lote, $pendientes);
            $esRedencion = $tipo === KsTipos::REDENCION;
            $esCoste = $tipo === KsTipos::COSTE;
            $cabValor = $esCoste ? 'Precio de coste (€)' : ($esRedencion ? 'Redención (%)' : 'Dto en factura (%)');
            $cabecera = ['EAN', 'Referencia', 'Producto', 'Formato', 'Unidades vendidas', 'De ellas, de regalo', 'Pedidos', 'Ventas sin IVA (€)', $cabValor];
            if ($esRedencion) {
                $cabecera[] = 'Importe redención (€)';
            }
            if ($esCoste) {
                $cabecera[] = 'Coste de lo vendido (€)';
                $cabecera[] = 'Margen (€)';
            }
            $cuerpo = [];
            foreach ($v['articulos'] as $a) {
                if ((int) $a['uds'] <= 0) {
                    continue;
                }
                $fila = [(string) $a['ean'], (string) $a['ref'], (string) $a['producto'], (string) $a['formato'], (int) $a['uds'], (int) $a['regalo'], (int) $a['pedidos'], (float) $a['neto'], (float) $a['valor']];
                if ($esRedencion) {
                    $fila[] = (float) $a['importe'];
                }
                if ($esCoste) {
                    $fila[] = (float) $a['coste'];
                    $fila[] = (float) $a['margen'];
                }
                $cuerpo[] = $fila;
            }
            $total = ['', '', 'TOTAL', '', (int) $v['totales']['uds'], (int) $v['totales']['regalo'], (int) $v['n_pedidos'], (float) $v['totales']['neto'], null];
            if ($esRedencion) {
                $total[] = (float) $v['totales']['importe'];
            }
            if ($esCoste) {
                $total[] = (float) $v['totales']['coste'];
                $total[] = (float) $v['totales']['margen'];
            }
            $cuerpo[] = $total;
            $hojas[] = ['nombre' => 'Por artículo', 'cabecera' => $cabecera, 'filas' => $cuerpo, 'anchos' => [16, 16, 44, 18, 12, 12, 10, 16, 14, 18, 18]];
            $detalle = [];
            foreach ($v['detalle'] as $d) {
                $fila = [(string) $d['pedido'], substr((string) $d['fecha'], 0, 16), $d['pagado'] ? 'sí' : 'pendiente', (string) $d['ean'], (string) $d['ref'], (string) $d['producto'], (int) $d['uds'], (int) $d['regalo'], (float) $d['bruto'], (float) $d['vales'], (float) $d['neto']];
                if ($esRedencion) {
                    $fila[] = (float) $d['importe'];
                }
                $detalle[] = $fila;
            }
            $cabecera = ['Pedido', 'Fecha', 'Pagado', 'EAN', 'Referencia', 'Producto', 'Unidades', 'De regalo', 'Línea sin IVA (€)', 'Vales de la cesta (€)', 'Ventas sin IVA (€)'];
            if ($esRedencion) {
                $cabecera[] = 'Importe redención (€)';
            }
            $hojas[] = ['nombre' => 'Por pedido', 'cabecera' => $cabecera, 'filas' => $detalle, 'anchos' => [12, 16, 10, 16, 16, 48, 10, 10, 16, 18, 16, 18]];
            $hojas[] = ['nombre' => 'Qué es esto', 'cabecera' => ['Dato', 'Valor'], 'filas' => [
                ['Tipo de acuerdo', KsTipos::titulo($tipo)],
                ['Marca', $marca],
                ['Promoción', $promo],
                ['Periodo', KsAcuerdos::fechasTexto((string) $lote['desde'], $lote['hasta'])],
                ['Ventas contadas', $pendientes ? 'pedidos pagados y pendientes de pago' : 'solo pedidos pagados'],
                ['No cuentan los pedidos en estado', implode(', ', array_column(array_filter($this->estados(), static fn (array $e): bool => $e['fuera']), 'nombre'))],
                ['Pedidos contados', (int) $v['n_pedidos']],
                ['Pedidos pendientes de pago fuera', (int) $v['pendientes_fuera']],
                ['Ventas sin IVA', 'lo cobrado por cada línea sin IVA, menos los vales de la cesta que le tocan (regalo, 2ª unidad, cupo, cupones y códigos)'],
                ['Unidades', 'las vendidas menos las devueltas o reembolsadas; «de regalo», las que se dieron gratis con una promoción de regalo (a 0 €)'],
                ['Generado', date('d/m/Y H:i') . ' desde Kompy · Acuerdos con proveedores'],
            ], 'anchos' => [34, 90]];
            $nombre = 'ventas-' . Tools::str2url(KsTipos::titulo($tipo)) . ($marca !== '' ? '-' . Tools::str2url($marca) : '') . '-' . $desdeCorto;
        }
        $bytes = KsExcel::libro($hojas);
        $tipoFichero = KsExcel::TIPO_XLSX;
        $extension = 'xlsx';
        if ($bytes === null) {
            $bytes = KsExcel::csv($hojas[0]);
            $tipoFichero = KsExcel::TIPO_CSV;
            $extension = 'csv';
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $tipoFichero);
        header('Content-Disposition: attachment; filename="' . $nombre . '.' . $extension . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: private, no-store, max-age=0');
        echo $bytes;
        exit;
    }
}
