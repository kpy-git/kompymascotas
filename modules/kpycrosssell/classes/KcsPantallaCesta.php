<?php
/**
 * La pantalla de la cesta de un solo almacén (dentro de la del módulo): los ajustes, qué pasa con las
 * alternativas (cuántas veces se enseñan, se cambian o se dejan) y «probar una cesta»: con el número de
 * una cesta se ve qué decide el reparto de Julián, cuál es el almacén de referencia, qué líneas bloquean
 * y qué se le ofrecería a cada una. Es lo primero que se quiere mirar cuando alguien pregunta «¿y a esta
 * cesta qué le sale?».
 */
class KcsPantallaCesta
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
        return $this->procesar() . $this->formulario() . $this->probar() . $this->cuentas();
    }

    private function procesar(): string
    {
        if (!Tools::isSubmit('submitKcsCesta')) {
            return '';
        }
        Configuration::updateValue('KCS_CESTA_ON', (int) (bool) Tools::getValue('KCS_CESTA_ON'));
        Configuration::updateValue('KCS_CESTA_VENTANA', (int) (bool) Tools::getValue('KCS_CESTA_VENTANA'));
        Configuration::updateValue('KCS_CESTA_RESUMEN', (int) (bool) Tools::getValue('KCS_CESTA_RESUMEN'));
        Configuration::updateValue('KCS_CESTA_MIN', max(10, min(100, (int) Tools::getValue('KCS_CESTA_MIN'))));
        Configuration::updateValue('KCS_CESTA_MAX', max(100, min(400, (int) Tools::getValue('KCS_CESTA_MAX'))));
        Configuration::updateValue('KCS_CESTA_LEJOS_MIN', max(1, min(100, (int) Tools::getValue('KCS_CESTA_LEJOS_MIN'))));
        Configuration::updateValue('KCS_CESTA_LEJOS_MAX', max(100, min(2000, (int) Tools::getValue('KCS_CESTA_LEJOS_MAX'))));
        Configuration::updateValue('KCS_CESTA_LEJOS_EUROS', max(0, min(100, (int) Tools::getValue('KCS_CESTA_LEJOS_EUROS'))));
        Configuration::updateValue('KCS_CESTA_TARJETAS', max(1, min(6, (int) Tools::getValue('KCS_CESTA_TARJETAS'))));
        Configuration::updateValue('KCS_CESTA_TOPE', max(1, min(24, (int) Tools::getValue('KCS_CESTA_TOPE'))));
        Configuration::updateValue('KCS_CESTA_MARCAS', max(0, min(20, (int) Tools::getValue('KCS_CESTA_MARCAS'))));
        Configuration::updateValue('KCS_CESTA_DIETA_MARCAS', trim((string) Tools::getValue('KCS_CESTA_DIETA_MARCAS')));
        Configuration::updateValue('KCS_CESTA_NUNCA', KcsAlmacenes::escribirVetos(KcsAlmacenes::leerVetos((string) Tools::getValue('KCS_CESTA_NUNCA'))));
        // El mapa de marcas se rehace con los ajustes nuevos.
        Configuration::deleteByName('KCS_CESTA_MAPA');

        return $this->modulo->displayConfirmation($this->t('Guardado.'));
    }

    private function formulario(): string
    {
        $helper = new HelperForm();
        $helper->module = $this->modulo;
        $helper->name_controller = $this->modulo->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->modulo->name;
        $helper->submit_action = 'submitKcsCesta';
        $helper->default_form_language = (int) $this->contexto->language->id;
        $valores = [];
        foreach (array_keys(KcsCesta::AJUSTES) as $clave) {
            $valores[$clave] = Configuration::get($clave);
        }
        $helper->fields_value = $valores;
        $si = $this->t('Sí');
        $no = $this->t('No');
        $interruptor = static fn (string $nombre, string $etiqueta, string $desc) => [
            'type' => 'switch', 'name' => $nombre, 'label' => $etiqueta, 'desc' => $desc, 'is_bool' => true,
            'values' => [['id' => $nombre . '_on', 'value' => 1, 'label' => $si], ['id' => $nombre . '_off', 'value' => 0, 'label' => $no]],
        ];

        $reparto = KcsAlmacenes::disponible()
            ? $this->t('El reparto de Julián (kpyorderdispatcher) responde: el módulo funciona.')
            : $this->t('El reparto de Julián (kpyorderdispatcher) no responde: el módulo no enseña nada en la cesta.');

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->t('La cesta de un solo almacén'), 'icon' => 'icon-shopping-cart'],
                'description' => $this->t('Cuando la cesta implica más de un almacén (lo decide el reparto de Julián), cada producto que no sale del almacén de referencia lleva su aviso y alternativas parecidas de ese almacén. Al cliente no se le dice nunca de qué almacén sale nada ni ninguna fecha.') . ' ' . $reparto,
                'input' => [
                    $interruptor('KCS_CESTA_ON', $this->t('Encendido'), $this->t('Apagado, no se enseña nada en la cesta ni en la ventana.')),
                    $interruptor('KCS_CESTA_VENTANA', $this->t('También en «Añadido a tu cesta»'), $this->t('La ventana que sale al añadir un producto.')),
                    $interruptor('KCS_CESTA_RESUMEN', $this->t('El aviso al pie de la cesta'), $this->t('«Tu pedido sale de varios almacenes y puede retrasarse», con el enlace al producto. Solo cuando alguna línea tiene alternativas: sin ellas no se enseña nada.')),
                    ['type' => 'text', 'name' => 'KCS_CESTA_MIN', 'label' => $this->t('Precio parecido, desde'), 'suffix' => '%',
                        'desc' => $this->t('Respecto a lo que vale el producto de la línea (sin su promoción de hoy), comparando lo que se paga por el formato. Al 70 %, a un saco de 50 € se le ofrecen antes los de 35 € o más.')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_MAX', 'label' => $this->t('Precio parecido, hasta'), 'suffix' => '%',
                        'desc' => $this->t('Al 130 %, hasta 65 €; y nunca más caro por kilo que ese 130 %, cuando los dos formatos tienen peso.')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_LEJOS_MIN', 'label' => $this->t('Si no hay parecidos, como mucho desde'), 'suffix' => '%',
                        'desc' => $this->t('Los de precio parecido van primero; si no los hay o son pocos, se completan con los más cercanos en precio dentro de esta banda (van marcados como no parecidos y el texto no promete «precio parecido»).')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_LEJOS_MAX', 'label' => $this->t('Si no hay parecidos, como mucho hasta'), 'suffix' => '%',
                        'desc' => $this->t('Al 400 %, a un snack de 3 € se le puede ofrecer uno de hasta 12 € antes que nada.')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_LEJOS_EUROS', 'label' => $this->t('…o hasta estos euros más'), 'suffix' => '€',
                        'desc' => $this->t('Lo que sea mayor: a un snack de 1,55 € el 400 % son 6 €; con 10 € más llega a 11,55 €. 0 para no usarlo.')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_TARJETAS', 'label' => $this->t('Alternativas a la vista'),
                        'desc' => $this->t('Las primeras que se enseñan; el resto, con «Ver más productos elegibles».')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_TOPE', 'label' => $this->t('Alternativas como mucho'),
                        'desc' => $this->t('Contando las de «Ver más».')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_MARCAS', 'label' => $this->t('Marcas que no retrasan'),
                        'desc' => $this->t('Cuántas marcas se enseñan debajo («Productos de estas marcas no retrasan tu pedido»). 0 para no enseñar ninguna.')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_DIETA_MARCAS', 'label' => $this->t('Marcas preferidas para dietas'),
                        'desc' => $this->t('Por orden y separadas por comas (Farmina, Hill\'s…): en las dietas veterinarias salen antes que las demás, si cumplen lo mismo.')],
                    ['type' => 'text', 'name' => 'KCS_CESTA_NUNCA', 'label' => $this->t('Categorías que nunca salen de un almacén'),
                        'desc' => $this->t('Lo que el reparto de pedidos de Julián no deja salir de un almacén aunque el de la cesta diga que sí: hoy, los snacks de Natural Greatness (categoría 5576) no salen de Evolution Pet. Nada de esas categorías se ofrece como alternativa de ese almacén. Formato: ALMACEN:categorías separadas por comas, y otro almacén tras un punto y coma (EVOLUTION:5576; NEFTYS:12). Almacenes: TIENDA, NEFTYS, DISTRIVET, EVOLUTION.')],
                ],
                'submit' => ['title' => $this->t('Guardar')],
            ],
        ]]);
    }

    /** Probar una cesta: qué decide el reparto y qué se le ofrecería a cada línea que bloquea. */
    private function probar(): string
    {
        $base = AdminController::$currentIndex . '&configure=' . $this->modulo->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $idCesta = (int) Tools::getValue('probarKcsCesta');
        $html = '<div class="panel"><h3><i class="icon-search"></i> ' . $this->t('Probar una cesta') . '</h3>'
            . '<form method="get" action="' . htmlspecialchars(AdminController::$currentIndex) . '">'
            . '<input type="hidden" name="configure" value="' . $this->modulo->name . '">'
            . '<input type="hidden" name="token" value="' . Tools::getAdminTokenLite('AdminModules') . '">'
            . '<div class="form-group"><label>' . $this->t('Número de la cesta (id_cart)') . '</label> '
            . '<input type="text" name="probarKcsCesta" class="form-control" style="max-width:200px;display:inline-block" value="' . ($idCesta ?: '') . '"> '
            . '<button class="btn btn-default">' . $this->t('Ver') . '</button></div></form>';
        if ($idCesta <= 0) {
            return $html . '</div>';
        }
        $cart = new Cart($idCesta);
        if (!Validate::isLoadedObject($cart)) {
            return $html . '<div class="alert alert-warning">' . $this->t('No existe esa cesta.') . '</div></div>';
        }
        if (!KcsAlmacenes::disponible()) {
            return $html . '<div class="alert alert-danger">' . $this->t('El reparto de Julián no responde: mira el registro de PrestaShop.') . '</div></div>';
        }
        // La decisión se calcula con la cesta en el contexto, como en la tienda.
        $antes = $this->contexto->cart;
        $this->contexto->cart = $cart;
        try {
            $d = KcsCesta::decision($cart);
            $html .= '<p>' . sprintf($this->t('Cesta %d: %d líneas. %s'), $idCesta, count($d['lineas']),
                $d['multi'] ? $this->t('Implica más de un almacén.') . ' ' . sprintf($this->t('Almacén de referencia: %s.'), htmlspecialchars(KcsAlmacenes::NOMBRES[$d['referencia']] ?? $d['referencia'])) : $this->t('Un solo almacén (o el reparto dice que no hay mezcla): no se enseña nada.')) . '</p>';
            $html .= '<table class="table"><thead><tr><th>' . $this->t('Línea') . '</th><th>' . $this->t('Cantidad') . '</th><th>' . $this->t('Total') . '</th><th>' . $this->t('Almacén') . '</th><th>' . $this->t('Bloquea') . '</th></tr></thead><tbody>';
            foreach ($d['lineas'] as $l) {
                $html .= '<tr><td>#' . (int) $l['id_product'] . '-' . (int) $l['id_product_attribute'] . ' ' . htmlspecialchars($l['nombre']) . '</td>'
                    . '<td>' . (int) $l['cantidad'] . '</td><td>' . number_format((float) $l['total'], 2, ',', '.') . ' €</td>'
                    . '<td>' . htmlspecialchars(KcsAlmacenes::NOMBRES[$l['almacen'] ?? ''] ?? ($l['almacen'] ?? '—')) . '</td>'
                    . '<td>' . (!empty($l['bloquea']) ? '<span class="badge" style="background:#b7651a">' . $this->t('sí') . '</span>' : '') . '</td></tr>';
            }
            $html .= '</tbody></table>';
            foreach ($d['bloqueantes'] as $clave) {
                $linea = $d['lineas'][$clave] + ['referencia' => $d['referencia']];
                $alternativas = KcsAlternativas::para($linea, $d['referencia'], $idCesta, KcsCesta::ajustes(), array_keys($d['lineas']));
                $html .= '<h4>' . sprintf($this->t('Alternativas para %s'), htmlspecialchars($linea['nombre'])) . '</h4>';
                if ($alternativas === []) {
                    $html .= '<div class="alert alert-info">' . $this->t('Ninguna: en esa línea no se enseña nada (ni etiqueta ni aviso), para no duplicar el mensaje de la fecha.') . '</div>';
                    continue;
                }
                $perfil = KcsAlternativas::perfil(KcsSugerencias::rasgos((int) $linea['id_product']), (string) $linea['nombre']);
                if ($perfil !== null && $perfil['temas'] !== []) {
                    $html .= '<p>' . sprintf($this->t('Tema de la línea: %s.'), htmlspecialchars(implode(', ', $perfil['temas']))) . '</p>';
                }
                $html .= '<table class="table"><thead><tr><th>#</th><th>' . $this->t('Producto') . '</th><th>' . $this->t('Formato') . '</th><th>' . $this->t('Precio') . '</th><th>' . $this->t('Diferencia') . '</th>'
                    . '<th>' . $this->t('Precio parecido') . '</th><th>' . $this->t('Mismo tema') . '</th><th>' . $this->t('Categorías compartidas') . '</th><th>' . $this->t('Marca') . '</th></tr></thead><tbody>';
                foreach ($alternativas as $i => $a) {
                    $html .= '<tr><td>' . ($i + 1) . '</td><td>#' . (int) $a['id_product'] . ' ' . htmlspecialchars($a['nombre']) . '</td>'
                        . '<td>' . htmlspecialchars($a['formato']) . '</td><td>' . htmlspecialchars($a['precio_txt']) . '</td>'
                        . '<td>' . htmlspecialchars($a['diferencia_txt']) . '</td>'
                        . '<td>' . (!empty($a['parecido']) ? $this->t('sí') : $this->t('no')) . '</td>'
                        . '<td>' . [$this->t('sí'), $this->t('genérico'), $this->t('otro tema')][(int) ($a['tema'] ?? 1)] . '</td>'
                        . '<td>' . (int) ($a['categoria'] ?? 0) . '</td>'
                        . '<td>' . htmlspecialchars($a['marca']) . '</td></tr>';
                }
                $html .= '</tbody></table>';
            }
            $memoria = KcsAlmacenes::memoria();
            $html .= '<p class="help-block">' . sprintf($this->t('La memoria de almacenes recuerda %d respuestas del reparto de Julián (valen %d horas; lo que se enseña se confirma en vivo).'), $memoria['n'], (int) (KcsAlmacenes::VIGENCIA / 3600)) . '</p>';
        } catch (Throwable $e) {
            $html .= '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
        } finally {
            $this->contexto->cart = $antes;
        }

        return $html . '</div>';
    }

    /** Lo que pasa: por día y evento, y los últimos cambios hechos. */
    private function cuentas(): string
    {
        $filas = KcsCestaLog::resumen(30);
        $html = '<div class="panel"><h3><i class="icon-bar-chart"></i> ' . $this->t('Qué pasa con las alternativas (últimos 30 días)') . '</h3>';
        if ($filas === []) {
            return $html . '<div class="alert alert-info">' . $this->t('Todavía nada.') . '</div></div>';
        }
        $totales = [];
        $porDia = [];
        foreach ($filas as $f) {
            $totales[$f['evento']] = ($totales[$f['evento']] ?? 0) + (int) $f['n'];
            $porDia[$f['dia']][$f['evento']] = (int) $f['n'];
        }
        $nombres = [
            'vista' => $this->t('bloques enseñados'), 'ventana' => $this->t('en la ventana'), 'cambio' => $this->t('cambios hechos'),
            'dejar' => $this->t('«dejarlo como está»'), 'vermas' => $this->t('«ver más»'), 'info' => $this->t('(i) abiertas'), 'error' => $this->t('cambios fallidos'),
        ];
        $html .= '<p>';
        foreach ($nombres as $evento => $nombre) {
            if (isset($totales[$evento])) {
                $html .= '<strong>' . (int) $totales[$evento] . '</strong> ' . htmlspecialchars($nombre) . ' · ';
            }
        }
        $html = rtrim($html, ' ·') . '</p>';
        $html .= '<table class="table"><thead><tr><th>' . $this->t('Día') . '</th>';
        foreach ($nombres as $evento => $nombre) {
            $html .= '<th>' . htmlspecialchars($nombre) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($porDia as $dia => $eventos) {
            $html .= '<tr><td>' . htmlspecialchars($dia) . '</td>';
            foreach (array_keys($nombres) as $evento) {
                $html .= '<td>' . (int) ($eventos[$evento] ?? 0) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $cambios = KcsCestaLog::cambios(30, 50);
        if ($cambios !== []) {
            $html .= '<h4>' . $this->t('Últimos cambios') . '</h4><table class="table"><thead><tr><th>' . $this->t('Cuándo') . '</th><th>' . $this->t('Cesta') . '</th><th>' . $this->t('Salió') . '</th><th>' . $this->t('Entró') . '</th><th>' . $this->t('Almacén de referencia') . '</th></tr></thead><tbody>';
            foreach ($cambios as $c) {
                $html .= '<tr><td>' . htmlspecialchars($c['fecha']) . '</td><td>' . (int) $c['id_cart'] . '</td>'
                    . '<td>#' . (int) $c['id_product'] . ' ' . htmlspecialchars((string) $c['salio']) . '</td>'
                    . '<td>#' . (int) $c['id_sugerido'] . ' ' . htmlspecialchars((string) $c['entro']) . '</td>'
                    . '<td>' . htmlspecialchars(KcsAlmacenes::NOMBRES[$c['almacen_ref']] ?? $c['almacen_ref']) . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        return $html . '</div>';
    }

    private function t(string $texto): string
    {
        return $this->modulo->getTranslator()->trans($texto, [], 'Modules.Kpycrosssell.Admin');
    }
}
