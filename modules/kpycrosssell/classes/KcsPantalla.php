<?php
/**
 * La pantalla del módulo: los ajustes del bloque, el descuento por llevarlos juntos y las parejas
 * puestas a mano (que mandan sobre lo que deduce el catálogo).
 *
 * Con un buscador para comprobar, sin salir de aquí, qué se le ofrecería a un producto concreto: es
 * lo primero que se quiere mirar cuando alguien pregunta «¿y a este qué le sale?».
 */
class KcsPantalla
{
    private Kpycrosssell $modulo;
    private Context $contexto;

    public function __construct(Kpycrosssell $modulo)
    {
        $this->modulo = $modulo;
        $this->contexto = Context::getContext();
    }

    public function pintar(): string
    {
        return $this->procesar() . $this->formulario() . $this->distribuidores() . $this->descuentos() . $this->probar() . $this->parejas();
    }

    private function procesar(): string
    {
        if (Tools::isSubmit('submitKcsAjustes')) {
            Configuration::updateValue('KCS_CUANTAS', max(1, min(6, (int) Tools::getValue('cuantas'))));
            Configuration::updateValue('KCS_PARTE', max(5, min(100, (int) Tools::getValue('parte'))));
            Configuration::updateValue('KCS_TOPE', max(1, (float) str_replace(',', '.', (string) Tools::getValue('tope'))));

            return $this->modulo->displayConfirmation($this->t('Guardado.'));
        }

        if (Tools::isSubmit('submitKcsPareja')) {
            $ambito = in_array(Tools::getValue('ambito'), ['producto', 'categoria', 'marca'], true)
                ? (string) Tools::getValue('ambito')
                : 'producto';
            $idRef = (int) Tools::getValue('id_ref');
            $posicion = max(1, min(3, (int) Tools::getValue('posicion')));
            $ids = $this->ids((string) Tools::getValue('sugeridos'));
            if ($idRef <= 0 || $ids === []) {
                return $this->modulo->displayError($this->t('Hace falta el id y al menos una sugerencia.'));
            }
            $db = Db::getInstance();
            $puestas = 0;
            foreach ($ids as $idSugerido) {
                if ($ambito === 'producto' && $idSugerido === $idRef) {
                    continue;
                }
                $db->execute(
                    'INSERT INTO ' . _DB_PREFIX_ . "kpy_cross_sell (ambito, id_ref, id_sugerido, posicion, activo)
                     VALUES ('" . pSQL($ambito) . "', " . $idRef . ', ' . $idSugerido . ', ' . $posicion . ", 1)
                     ON DUPLICATE KEY UPDATE posicion = VALUES(posicion), activo = 1"
                );
                ++$puestas;
            }

            return $this->modulo->displayConfirmation(sprintf($this->t('Guardadas %d sugerencias en la posición %d.'), $puestas, $posicion));
        }

        if (Tools::isSubmit('submitKcsDistribuidores')) {
            $reparto = (array) Tools::getValue('distribuidor', []);
            KcsDistribuidor::guardar($reparto);

            return $this->modulo->displayConfirmation($this->t('Guardado quién sirve cada marca.'));
        }

        if (Tools::isSubmit('submitKcsPromo')) {
            KcsPromo::guardar([
                'id_promo' => (int) Tools::getValue('id_promo'),
                'id_manufacturer' => (int) Tools::getValue('id_manufacturer'),
                'nombre' => trim((string) Tools::getValue('nombre')),
                'porcentaje' => (float) str_replace(',', '.', (string) Tools::getValue('porcentaje')),
                'misma_marca' => (bool) Tools::getValue('misma_marca'),
                'activo' => (bool) Tools::getValue('activo'),
                'fecha_desde' => $this->fecha(Tools::getValue('fecha_desde')),
                'fecha_hasta' => $this->fecha(Tools::getValue('fecha_hasta')),
            ]);

            return $this->modulo->displayConfirmation($this->t('Descuento guardado.'));
        }

        if (Tools::isSubmit('cambiarKcsPromo')) {
            KcsPromo::cambiar((int) Tools::getValue('cambiarKcsPromo'));

            return $this->modulo->displayConfirmation($this->t('Cambiado.'));
        }

        if (Tools::isSubmit('borrarKcsPromo')) {
            KcsPromo::borrar((int) Tools::getValue('borrarKcsPromo'));

            return $this->modulo->displayConfirmation($this->t('Descuento borrado.'));
        }

        // Quitar una sugerencia que no pega: se apunta como vetada y no vuelve a salir.
        if (Tools::isSubmit('vetarKcs')) {
            $idRef = (int) Tools::getValue('ref');
            $idSugerido = (int) Tools::getValue('vetarKcs');
            if ($idRef > 0 && $idSugerido > 0) {
                Db::getInstance()->execute(
                    'INSERT INTO ' . _DB_PREFIX_ . "kpy_cross_sell (ambito, id_ref, id_sugerido, posicion, activo, veto)
                     VALUES ('producto', " . $idRef . ', ' . $idSugerido . ", 1, 1, 1)
                     ON DUPLICATE KEY UPDATE veto = 1"
                );

                return $this->modulo->displayConfirmation($this->t('Quitado. Ya no le saldrá.'));
            }
        }

        // Fijar una sugerencia en su hueco, sin tener que escribir los ids a mano.
        if (Tools::isSubmit('fijarKcs')) {
            $idRef = (int) Tools::getValue('ref');
            $idSugerido = (int) Tools::getValue('fijarKcs');
            $posicion = max(1, min(3, (int) Tools::getValue('pos')));
            if ($idRef > 0 && $idSugerido > 0) {
                Db::getInstance()->execute(
                    'INSERT INTO ' . _DB_PREFIX_ . "kpy_cross_sell (ambito, id_ref, id_sugerido, posicion, activo, veto)
                     VALUES ('producto', " . $idRef . ', ' . $idSugerido . ', ' . $posicion . ", 1, 0)
                     ON DUPLICATE KEY UPDATE posicion = VALUES(posicion), activo = 1, veto = 0"
                );

                return $this->modulo->displayConfirmation(sprintf($this->t('Fijado en la posición %d.'), $posicion));
            }
        }

        if (Tools::isSubmit('borrarKcs')) {
            Db::getInstance()->delete('kpy_cross_sell', 'id_cross = ' . (int) Tools::getValue('borrarKcs'));

            return $this->modulo->displayConfirmation($this->t('Pareja borrada.'));
        }

        return '';
    }

    private function formulario(): string
    {
        $helper = new HelperForm();
        $helper->module = $this->modulo;
        $helper->name_controller = $this->modulo->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->modulo->name;
        $helper->submit_action = 'submitKcsAjustes';
        $helper->default_form_language = (int) $this->contexto->language->id;
        $helper->fields_value = [
            'cuantas' => (int) Configuration::get('KCS_CUANTAS'),
            'parte' => (int) Configuration::get('KCS_PARTE'),
            'tope' => (float) Configuration::get('KCS_TOPE'),
        ];

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->t('Cómo se ofrece'), 'icon' => 'icon-cogs'],
                'input' => [
                    ['type' => 'text', 'name' => 'cuantas', 'label' => $this->t('Cuántas sugerencias'),
                        'desc' => $this->t('Como mucho. Con más de tres el bloque empieza a estorbar.')],
                    ['type' => 'text', 'name' => 'parte', 'label' => $this->t('Precio máximo'), 'suffix' => '%',
                        'desc' => $this->t('Respecto al producto que se está viendo. Al 60 %, a un pienso de 50 € solo se le ofrece algo de 30 € o menos.')],
                    ['type' => 'text', 'name' => 'tope', 'label' => $this->t('Y nunca más de'), 'suffix' => '€',
                        'desc' => $this->t('Tope en euros, pase lo que pase.')],
                ],
                'submit' => ['title' => $this->t('Guardar')],
            ],
        ]]);
    }

    /**
     * Quién sirve cada marca.
     *
     * Es lo que decide qué se puede ofrecer con qué: una sugerencia tiene que salir del mismo sitio
     * que el producto que se está mirando, porque si no el pedido se parte en dos envíos. Las marcas
     * que nadie ha repartido cuentan como del almacén, y salen las primeras para completarlas.
     */
    private function distribuidores(): string
    {
        $p = _DB_PREFIX_;
        $marcas = Db::getInstance()->executeS(
            "SELECT m.id_manufacturer, m.name, COUNT(pr.id_product) AS n
             FROM {$p}manufacturer m
             INNER JOIN {$p}product pr ON pr.id_manufacturer = m.id_manufacturer AND pr.active = 1
             GROUP BY m.id_manufacturer, m.name
             HAVING n >= 3
             ORDER BY n DESC"
        ) ?: [];
        $mapa = KcsDistribuidor::mapa();

        $html = '<div class="panel"><h3><i class="icon-truck"></i> ' . $this->t('Quién sirve cada marca') . '</h3>'
            . '<p>' . $this->t('Solo se ofrecen juntos productos del mismo sitio: un pienso de Neftys con un húmedo de Distrivet serían dos envíos. Lo que no esté repartido cuenta como del almacén.') . '</p>'
            . '<form method="post"><table class="table"><thead><tr><th>' . $this->t('Marca') . '</th>'
            . '<th>' . $this->t('Productos') . '</th><th>' . $this->t('Lo sirve') . '</th></tr></thead><tbody>';

        // Primero las que faltan por repartir, que son las que hay que mirar.
        usort($marcas, static function (array $a, array $b) use ($mapa): int {
            $sinA = isset($mapa[(int) $a['id_manufacturer']]) ? 1 : 0;
            $sinB = isset($mapa[(int) $b['id_manufacturer']]) ? 1 : 0;

            return $sinA === $sinB ? (int) $b['n'] <=> (int) $a['n'] : $sinA <=> $sinB;
        });

        foreach ($marcas as $m) {
            $id = (int) $m['id_manufacturer'];
            $suyo = $mapa[$id] ?? '';
            $html .= '<tr' . ($suyo === '' ? ' style="background:#fff8e1"' : '') . '>'
                . '<td>' . htmlspecialchars((string) $m['name']) . '</td><td>' . (int) $m['n'] . '</td><td>'
                . '<select name="distribuidor[' . $id . ']" class="form-control" style="max-width:200px">'
                . '<option value="">' . $this->t('— sin repartir —') . '</option>';
            foreach (KcsDistribuidor::NOMBRES as $clave => $nombre) {
                $html .= '<option value="' . $clave . '"' . ($suyo === $clave ? ' selected' : '') . '>'
                    . htmlspecialchars($nombre) . '</option>';
            }
            $html .= '</select></td></tr>';
        }

        return $html . '</tbody></table>'
            . '<button type="submit" name="submitKcsDistribuidores" value="1" class="btn btn-default">'
            . $this->t('Guardar') . '</button></form></div>';
    }

    /**
     * Los descuentos por llevarlos juntos, **marca a marca**.
     *
     * No es una regla general de la tienda: se decide con cada marca y se enciende y se apaga cuando
     * toca. Mientras no haya ninguno, el bloque sugiere pero no descuenta.
     */
    private function descuentos(): string
    {
        $base = AdminController::$currentIndex . '&configure=' . $this->modulo->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $editar = (int) Tools::getValue('editarKcsPromo');
        $actual = ['id_promo' => 0, 'id_manufacturer' => 0, 'nombre' => '', 'porcentaje' => 5, 'misma_marca' => 1, 'activo' => 1, 'fecha_desde' => '', 'fecha_hasta' => ''];
        $filas = KcsPromo::todas();
        foreach ($filas as $f) {
            if ((int) $f['id_promo'] === $editar) {
                $actual = $f;
            }
        }

        $marcas = Db::getInstance()->executeS(
            'SELECT m.id_manufacturer, m.name FROM ' . _DB_PREFIX_ . 'manufacturer m WHERE m.active = 1 ORDER BY m.name'
        ) ?: [];
        $opciones = '<option value="0">' . $this->t('— Todas las marcas —') . '</option>';
        foreach ($marcas as $m) {
            $opciones .= '<option value="' . (int) $m['id_manufacturer'] . '"'
                . ((int) $m['id_manufacturer'] === (int) $actual['id_manufacturer'] ? ' selected' : '') . '>'
                . htmlspecialchars((string) $m['name']) . '</option>';
        }

        $si = static fn (string $nombre, $valor): string => '<label style="margin-right:14px"><input type="checkbox" name="' . $nombre . '" value="1"'
            . ($valor ? ' checked' : '') . '> ';

        $html = '<div class="panel"><h3><i class="icon-percent"></i> ' . $this->t('Descuento por llevarlos juntos') . '</h3>'
            . '<p>' . $this->t('Se decide marca a marca: el porcentaje se aplica en los dos productos cuando el cliente se lleva el par. Mientras una marca no tenga descuento encendido, su bloque sugiere pero no descuenta.') . '</p>'
            . '<form method="post" class="form-horizontal" style="margin-bottom:18px">'
            . '<input type="hidden" name="id_promo" value="' . (int) $actual['id_promo'] . '">'
            . '<div class="form-group"><label class="control-label col-lg-2">' . $this->t('Marca') . '</label>'
            . '<div class="col-lg-4"><select name="id_manufacturer" class="form-control fixed-width-xxl">' . $opciones . '</select></div></div>'
            . '<div class="form-group"><label class="control-label col-lg-2">' . $this->t('Descuento') . '</label>'
            . '<div class="col-lg-2"><div class="input-group"><input type="text" name="porcentaje" class="form-control" value="' . htmlspecialchars((string) $actual['porcentaje']) . '"><span class="input-group-addon">%</span></div></div></div>'
            . '<div class="form-group"><label class="control-label col-lg-2">' . $this->t('Nombre') . '</label>'
            . '<div class="col-lg-4"><input type="text" name="nombre" class="form-control" placeholder="' . $this->t('Royal Canin: pienso + húmedo') . '" value="' . htmlspecialchars((string) $actual['nombre']) . '"></div></div>'
            . '<div class="form-group"><label class="control-label col-lg-2">' . $this->t('Fechas') . '</label>'
            . '<div class="col-lg-2"><input type="date" name="fecha_desde" class="form-control" value="' . htmlspecialchars((string) $actual['fecha_desde']) . '"></div>'
            . '<div class="col-lg-2"><input type="date" name="fecha_hasta" class="form-control" value="' . htmlspecialchars((string) $actual['fecha_hasta']) . '"></div></div>'
            . '<div class="form-group"><div class="col-lg-offset-2 col-lg-8">'
            . $si('misma_marca', $actual['misma_marca']) . $this->t('Solo con productos de la misma marca') . '</label>'
            . $si('activo', $actual['activo']) . $this->t('Encendido') . '</label>'
            . '</div></div>'
            . '<div class="form-group"><div class="col-lg-offset-2 col-lg-8">'
            . '<button type="submit" name="submitKcsPromo" value="1" class="btn btn-primary">' . $this->t('Guardar descuento') . '</button>'
            . ($editar ? ' <a class="btn btn-default" href="' . $base . '">' . $this->t('Cancelar') . '</a>' : '')
            . '</div></div></form>';

        if ($filas === []) {
            return $html . '<div class="alert alert-info">' . $this->t('Todavía no hay ninguno: el bloque sugiere, pero no descuenta.') . '</div></div>';
        }

        $html .= '<table class="table"><thead><tr><th>' . $this->t('Marca') . '</th><th>' . $this->t('Descuento') . '</th>'
            . '<th>' . $this->t('Fechas') . '</th><th>' . $this->t('Solo su marca') . '</th><th>' . $this->t('Estado') . '</th><th></th></tr></thead><tbody>';
        foreach ($filas as $f) {
            $html .= '<tr' . ($f['activo'] ? '' : ' class="text-muted"') . '>'
                . '<td><strong>' . htmlspecialchars((string) ($f['marca'] ?: $this->t('Todas las marcas'))) . '</strong>'
                . ($f['nombre'] !== '' ? '<br><small>' . htmlspecialchars((string) $f['nombre']) . '</small>' : '') . '</td>'
                . '<td>' . KcsJuntos::texto((float) $f['porcentaje']) . ' %</td>'
                . '<td>' . htmlspecialchars((string) ($f['fecha_desde'] ?: '—')) . ' → ' . htmlspecialchars((string) ($f['fecha_hasta'] ?: '—')) . '</td>'
                . '<td>' . ($f['misma_marca'] ? $this->t('Sí') : $this->t('No')) . '</td>'
                . '<td>' . ($f['activo'] ? $this->t('Encendido') : $this->t('Apagado')) . '</td>'
                . '<td class="text-right">'
                . '<a class="btn btn-default" href="' . $base . '&editarKcsPromo=' . (int) $f['id_promo'] . '">' . $this->t('Cambiar') . '</a> '
                . '<a class="btn btn-default" href="' . $base . '&cambiarKcsPromo=' . (int) $f['id_promo'] . '">' . ($f['activo'] ? $this->t('Apagar') : $this->t('Encender')) . '</a> '
                . '<a class="btn btn-default" href="' . $base . '&borrarKcsPromo=' . (int) $f['id_promo'] . '">' . $this->t('Borrar') . '</a>'
                . '</td></tr>';
        }

        return $html . '</tbody></table></div>';
    }

    /**
     * Probar un producto: qué se le ofrecería ahora mismo, de dónde sale cada sugerencia, y los dos
     * botones que hacen falta a diario: **quitar** lo que no pega y **fijar** lo que sí.
     *
     * Se busca por id o por nombre, como en el panel de siempre.
     */
    private function probar(): string
    {
        $id = (int) Tools::getValue('probarKcs');
        $busca = trim((string) Tools::getValue('buscaKcs'));
        $base = AdminController::$currentIndex . '&configure=' . $this->modulo->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $html = '<div class="panel"><h3><i class="icon-search"></i> ' . $this->t('Probar un producto') . '</h3>'
            . '<form method="get" action="' . htmlspecialchars(AdminController::$currentIndex) . '">'
            . '<input type="hidden" name="configure" value="' . $this->modulo->name . '">'
            . '<input type="hidden" name="token" value="' . Tools::getAdminTokenLite('AdminModules') . '">'
            . '<div class="form-group"><label>' . $this->t('Id o nombre del producto') . '</label> '
            . '<input type="text" name="buscaKcs" class="form-control" style="max-width:320px;display:inline-block" placeholder="'
            . $this->t('7214, o «Boske Adult Chicken»') . '" value="' . htmlspecialchars($busca) . '"> '
            . '<button class="btn btn-default">' . $this->t('Buscar') . '</button></div></form>';

        // Si lo que se ha escrito es un número, es el id; si es texto, se busca y se elige.
        if ($busca !== '' && $id <= 0) {
            if (ctype_digit($busca)) {
                $id = (int) $busca;
            } else {
                $encontrados = Db::getInstance()->executeS(
                    'SELECT pl.id_product, pl.name FROM ' . _DB_PREFIX_ . 'product_lang pl
                     INNER JOIN ' . _DB_PREFIX_ . 'product p ON p.id_product = pl.id_product AND p.active = 1
                     WHERE pl.id_lang = ' . (int) $this->contexto->language->id . "
                       AND pl.name LIKE '%" . pSQL($busca) . "%'
                     ORDER BY pl.name LIMIT 25"
                ) ?: [];
                if ($encontrados === []) {
                    return $html . '<div class="alert alert-warning">' . $this->t('No hay ningún producto con ese nombre.') . '</div></div>';
                }
                if (count($encontrados) === 1) {
                    $id = (int) $encontrados[0]['id_product'];
                } else {
                    $html .= '<ul class="list-unstyled">';
                    foreach ($encontrados as $e) {
                        $html .= '<li><a href="' . $base . '&probarKcs=' . (int) $e['id_product'] . '">#'
                            . (int) $e['id_product'] . ' ' . htmlspecialchars((string) $e['name']) . '</a></li>';
                    }

                    return $html . '</ul></div>';
                }
            }
        }

        if ($id <= 0) {
            return $html . '</div>';
        }

        $producto = new Product($id, false, (int) $this->contexto->language->id);
        if (!Validate::isLoadedObject($producto)) {
            return $html . '<div class="alert alert-warning">' . $this->t('No existe ese producto.') . '</div></div>';
        }

        $rasgos = KcsSugerencias::rasgos($id);
        $precio = (float) Product::getPriceStatic($id, true, null);
        $ajustes = Kpycrosssell::comoBuscar();
        $sugerencias = KcsSugerencias::para([
            'id_product' => $id,
            'id_manufacturer' => (int) $producto->id_manufacturer,
            'precio' => $precio,
        ], $ajustes);

        $suServidor = KcsDistribuidor::NOMBRES[KcsDistribuidor::deLaMarca((int) $producto->id_manufacturer)] ?? '';
        $html .= '<p><strong>' . htmlspecialchars((string) $producto->name) . '</strong> — '
            . htmlspecialchars(implode(' · ', array_filter($rasgos))) . ' — '
            . sprintf($this->t('lo sirve %s'), htmlspecialchars($suServidor)) . ' — '
            . sprintf($this->t('precio %s €, se le puede ofrecer hasta %s €'), number_format($precio, 2, ',', '.'), number_format(KcsSugerencias::techo($precio, $ajustes), 2, ',', '.'))
            . '</p>';

        if ($sugerencias === []) {
            return $html . '<div class="alert alert-info">' . $this->t('No hay nada que ofrecerle con estos ajustes.') . '</div></div>';
        }

        // Lo que la gente se lleva de verdad de esta ficha, que es lo que manda a la larga.
        $clics = [];
        foreach (Db::getInstance()->executeS(
            'SELECT id_sugerido, vistas, clics FROM ' . _DB_PREFIX_ . 'kpy_cross_sell_clic WHERE id_ref = ' . $id
        ) ?: [] as $c) {
            $clics[(int) $c['id_sugerido']] = $c;
        }

        $html .= '<table class="table"><thead><tr><th>' . $this->t('Hueco') . '</th><th>' . $this->t('Id') . '</th><th>' . $this->t('Producto') . '</th>'
            . '<th>' . $this->t('Formato') . '</th><th>' . $this->t('Precio') . '</th><th>' . $this->t('De dónde sale') . '</th>'
            . '<th>' . $this->t('Se lo llevan') . '</th><th></th></tr></thead><tbody>';
        $hueco = 0;
        foreach (KcsBloque::articulos($sugerencias, $this->modulo) as $a) {
            ++$hueco;
            $idS = (int) $a['id_product'];
            $c = $clics[$idS] ?? ['vistas' => 0, 'clics' => 0];
            $html .= '<tr><td>' . $hueco . 'ª</td><td>' . $idS . '</td><td>' . htmlspecialchars($a['nombre']) . '</td>'
                . '<td>' . htmlspecialchars($a['formato']) . '</td><td>' . htmlspecialchars($a['precio_txt']) . '</td>'
                . '<td>' . htmlspecialchars($this->origen($a['origen'])) . '</td>'
                . '<td>' . (int) $c['clics'] . ' / ' . (int) $c['vistas'] . '</td>'
                . '<td class="text-right">'
                . '<a class="btn btn-default btn-xs" href="' . $base . '&probarKcs=' . $id . '&ref=' . $id . '&fijarKcs=' . $idS . '&pos=1">' . $this->t('Fijar 1ª') . '</a> '
                . '<a class="btn btn-default btn-xs" href="' . $base . '&probarKcs=' . $id . '&ref=' . $id . '&fijarKcs=' . $idS . '&pos=2">2ª</a> '
                . '<a class="btn btn-default btn-xs" href="' . $base . '&probarKcs=' . $id . '&ref=' . $id . '&fijarKcs=' . $idS . '&pos=3">3ª</a> '
                . '<a class="btn btn-danger btn-xs" href="' . $base . '&probarKcs=' . $id . '&ref=' . $id . '&vetarKcs=' . $idS . '">'
                . '<i class="icon-trash"></i> ' . $this->t('Quitar') . '</a></td></tr>';
        }
        $html .= '</tbody></table>'
            . '<p class="help-block">' . $this->t('«Quitar» lo saca de esta ficha para siempre; «Fijar» lo clava en ese hueco. Lo que no se toca lo decide el catálogo.') . '</p>';

        // Y lo que se ha añadido a mano para esta ficha, por si hay que deshacerlo.
        $suyas = Db::getInstance()->executeS(
            'SELECT cs.*, sl.name AS sugerido FROM ' . _DB_PREFIX_ . 'kpy_cross_sell cs
             LEFT JOIN ' . _DB_PREFIX_ . 'product_lang sl ON sl.id_product = cs.id_sugerido AND sl.id_lang = ' . (int) $this->contexto->language->id . "
             WHERE cs.ambito = 'producto' AND cs.id_ref = " . $id . ' ORDER BY cs.veto, cs.posicion'
        ) ?: [];
        if ($suyas !== []) {
            $html .= '<h4>' . $this->t('Puesto a mano en esta ficha') . '</h4><table class="table"><tbody>';
            foreach ($suyas as $f) {
                $html .= '<tr><td>' . ((int) $f['veto'] === 1
                        ? '<span class="badge" style="background:#c23a2c">' . $this->t('quitado') . '</span>'
                        : (int) $f['posicion'] . 'ª')
                    . '</td><td>#' . (int) $f['id_sugerido'] . ' ' . htmlspecialchars((string) $f['sugerido']) . '</td>'
                    . '<td class="text-right"><a class="btn btn-default btn-xs" href="' . $base . '&probarKcs=' . $id . '&borrarKcs=' . (int) $f['id_cross'] . '">'
                    . $this->t('Deshacer') . '</a></td></tr>';
            }
            $html .= '</tbody></table>';
        }

        return $html . '</div>';
    }

    private function parejas(): string
    {
        $p = _DB_PREFIX_;
        $idLang = (int) $this->contexto->language->id;
        $filas = Db::getInstance()->executeS(
            "SELECT cs.*, pl.name AS nombre, cl.name AS categoria, ma.name AS marca, sl.name AS sugerido
             FROM {$p}kpy_cross_sell cs
             LEFT JOIN {$p}product_lang pl ON pl.id_product = cs.id_ref AND pl.id_lang = {$idLang} AND cs.ambito = 'producto'
             LEFT JOIN {$p}category_lang cl ON cl.id_category = cs.id_ref AND cl.id_lang = {$idLang} AND cs.ambito = 'categoria'
             LEFT JOIN {$p}manufacturer ma ON ma.id_manufacturer = cs.id_ref AND cs.ambito = 'marca'
             LEFT JOIN {$p}product_lang sl ON sl.id_product = cs.id_sugerido AND sl.id_lang = {$idLang}
             ORDER BY cs.ambito, cs.id_ref, cs.posicion LIMIT 300"
        ) ?: [];

        $base = AdminController::$currentIndex . '&configure=' . $this->modulo->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $html = '<div class="panel"><h3><i class="icon-link"></i> ' . $this->t('Parejas puestas a mano') . '</h3>'
            . '<p>' . $this->t('Mandan sobre lo que deduce el catálogo, y mandan en su posición: si pones algo en la 1ª, lo demás baja a la 2ª y a la 3ª. Valen para un producto, para una categoría entera o para una marca entera.') . '</p>'
            . '<form method="post" class="form-inline" style="margin-bottom:18px">'
            . '<label>' . $this->t('Para') . ' <select name="ambito" class="form-control" style="max-width:130px">'
            . '<option value="producto">' . $this->t('un producto') . '</option>'
            . '<option value="categoria">' . $this->t('una categoría') . '</option>'
            . '<option value="marca">' . $this->t('una marca') . '</option></select></label> '
            . '<label>' . $this->t('Id') . ' <input type="text" name="id_ref" class="form-control" style="max-width:110px"></label> '
            . '<label>' . $this->t('Posición') . ' <select name="posicion" class="form-control" style="max-width:90px">'
            . '<option value="1">1ª</option><option value="2">2ª</option><option value="3">3ª</option></select></label> '
            . '<label>' . $this->t('Le va bien') . ' <input type="text" name="sugeridos" class="form-control" style="max-width:260px" placeholder="9770, 9771"></label> '
            . '<button type="submit" name="submitKcsPareja" value="1" class="btn btn-default">' . $this->t('Guardar') . '</button>'
            . '</form>';

        if ($filas === []) {
            return $html . '<div class="alert alert-info">' . $this->t('Todavía no hay ninguna. El bloque funciona igual: las saca del catálogo.') . '</div></div>';
        }

        $html .= '<table class="table"><thead><tr><th>' . $this->t('Para') . '</th><th>' . $this->t('Posición') . '</th>'
            . '<th>' . $this->t('Le va bien') . '</th><th></th></tr></thead><tbody>';
        foreach ($filas as $f) {
            $quien = (string) ($f['nombre'] ?: $f['categoria'] ?: $f['marca'] ?: '');
            $html .= '<tr>'
                . '<td><small>' . htmlspecialchars((string) $f['ambito']) . '</small> #' . (int) $f['id_ref'] . ' ' . htmlspecialchars($quien) . '</td>'
                . '<td>' . (int) $f['posicion'] . 'ª</td>'
                . '<td>#' . (int) $f['id_sugerido'] . ' ' . htmlspecialchars((string) $f['sugerido']) . '</td>'
                . '<td class="text-right"><a class="btn btn-default" href="' . $base . '&borrarKcs=' . (int) $f['id_cross'] . '">' . $this->t('Borrar') . '</a></td>'
                . '</tr>';
        }

        return $html . '</tbody></table></div>';
    }

    private function origen(string $origen): string
    {
        switch ($origen) {
            case 'mano':
                return $this->t('puesta a mano');
            case 'propia':
                return $this->t('húmedo clínico de Boske');
            case 'marca':
                return $this->t('de su marca');
            case 'reserva':
                return $this->t('marca de reserva');
            case 'refuerzo':
                return $this->t('complemento de su patología');
            case 'funcional':
                return $this->t('snack de su patología');
            case 'cuidado':
                return $this->t('snack o higiene');
            default:
                return $this->t('capricho');
        }
    }

    /** @return int[] */
    private function ids(string $texto): array
    {
        return array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,;]+/', $texto) ?: []))));
    }

    private function fecha($valor): string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '';
        }
        $t = strtotime($valor);

        return $t ? date('Y-m-d', $t) : '';
    }

    /** En PrestaShop 9 `Module::trans()` es protegido: desde fuera del módulo, por su traductor. */
    private function t(string $texto): string
    {
        return $this->modulo->getTranslator()->trans($texto, [], 'Modules.Kpycrosssell.Admin');
    }
}
