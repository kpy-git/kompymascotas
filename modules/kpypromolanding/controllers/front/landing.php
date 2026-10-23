<?php
/**
 * `/promociones`: la portada. Perros / Gatos, los tipos que tienen algo este mes, los banners de
 * las promociones vivas (marca a marca), lo más rebajado y los accesos. Todo sale del resumen que
 * dejó escrito la sincronización: aquí no se calcula nada.
 *
 * El boletín (1.4.0): la tarjeta «¡No te suscribas!» llamaba a `ps_emailsubscription` para hacer
 * el alta, y eso ya no se hace (ningún módulo llama a funciones de otro). Su widget no sirve aquí:
 * la plantilla del tema para ese bloque solo pinta en una lista fija de páginas (`$page.page_name`)
 * y en las nuestras devuelve vacío. Así que el boletín está fuera de la landing hasta que Julián
 * decida (pregunta 15 del plan): ver el README.
 */
class KpypromolandingLandingModuleFrontController extends ModuleFrontController
{
    protected string $mascota = '';
    /** La campaña que se pinta (`/blackfriday`), o null en `/promociones`. */
    protected ?array $campana = null;
    /** La campaña viva ahora mismo (sea esta u otra), o null: sus nombres en las etiquetas y su piel. */
    protected ?array $viva = null;

    public function init(): void
    {
        parent::init();
        $this->mascota = KplPromos::mascotaPedida();
        $this->viva = KplCampanas::viva();
        // Con una campaña viva que lo pide, /promociones manda a su dirección.
        if ($this->campana === null && $this->viva !== null && (int) $this->viva['redirigir'] === 1) {
            Tools::redirect(KplPromos::conMascota(KplCampanas::url($this->viva), $this->mascota));
        }
    }

    /** La dirección de esta portada (la de la campaña o la general). */
    protected function urlPortada(): string
    {
        return $this->campana !== null ? KplCampanas::url($this->campana) : KplPromos::urlLanding();
    }

    public function initContent(): void
    {
        parent::initContent();
        $base = $this->urlPortada();
        $resumen = KplPromos::resumen();
        $vivas = KplPromos::vivas($this->mascota);
        $campana = null;
        if ($this->campana !== null) {
            $c = $this->campana;
            if ($c['estado'] === 'viva') {
                $vivas = KplPromos::deCampana($c, $vivas); // solo lo que entra en la campaña
            }
            $vivas = KplCampanas::ordenar($vivas, $c);
            $campana = $c + KplCampanas::textos($c) + ['url' => KplCampanas::url($c), 'url_promociones' => KplPromos::urlLanding()];
        }
        // Con una campaña viva (esta, o la que haya mientras /promociones no manda a la suya), las
        // etiquetas se llaman como la campaña y la página lleva su piel.
        $enCampana = $this->campana !== null && $this->campana['estado'] === 'viva' ? $this->campana : $this->viva;
        $conPiel = $this->campana ?? $this->viva;
        $vivas = array_values(KplPromos::conCampana(KplPromos::conUrls($vivas, $this->mascota), $enCampana));
        $tipos = KplPromos::tiposConCampana(KplPromos::tipos($this->mascota), $enCampana);
        // La imagen de cada tarjeta de tipo: el saco del primer banner de ese tipo (o el de la categoría
        // permanente). Con el filtro de perros o gatos, el saco de esa mascota: el primer banner del
        // tipo que lo tenga.
        foreach ($tipos as $tipo => $d) {
            $candidatas = $d['marcas'];
            if (isset(KplClaves::PERMANENTES[$tipo])) {
                $candidatas[] = KplClaves::PERMANENTES[$tipo];
            }
            $imagen = '';
            $claveImagen = $candidatas[0] ?? '';
            foreach ($candidatas as $clave) {
                $c = $resumen['claves'][$clave] ?? null;
                if ($c !== null && $this->mascota !== '' && !empty($c['imagenes_' . $this->mascota][0])) {
                    $imagen = (string) $c['imagenes_' . $this->mascota][0];
                    break;
                }
            }
            if ($imagen === '' && $claveImagen !== '' && isset($resumen['claves'][$claveImagen])) {
                $imagen = (string) ($resumen['claves'][$claveImagen]['imagenes'][0] ?? '');
            }
            $tipos[$tipo]['imagen'] = $imagen;
            $tipos[$tipo]['codigo'] = $tipo === 'cupon' && $claveImagen !== '' ? (string) ($resumen['claves'][$claveImagen]['codigo'] ?? '') : '';
        }
        $top = KplPresentador::productos(KplPromos::top($this->mascota), 8);
        $enTotal = count(KplPromos::vivas());
        $this->context->smarty->assign([
            'kpl' => [
                'modo' => 'landing',
                'campana' => $campana,
                'piel' => $conPiel !== null ? (string) $conPiel['piel'] : '',
                'estilo' => $conPiel !== null ? KplCampanas::estilo($conPiel) : '',
                // El fondo de la página entera, en la piel negra (el de la paleta de la campaña).
                'fondo' => $conPiel !== null && $conPiel['piel'] === 'negra' ? KplCampanas::coloresDe($conPiel)['fondo'] : '',
                'mascota' => $this->mascota,
                'url_landing' => $base,
                'url_todas' => KplPromos::urlTodas(),
                'quien' => ['todos' => $base, 'perro' => KplPromos::conMascota($base, 'perro'), 'gato' => KplPromos::conMascota($base, 'gato')],
                'n_vivas' => count($vivas),
                'n_total' => $enTotal,
                'tipos' => array_values($tipos),
                'banners' => $vivas,
                'top' => $top,
                'accesos' => $this->accesos(),
                'fecha' => (string) ($resumen['fecha'] ?? ''),
            ],
        ]);
        $this->setTemplate('module:kpypromolanding/views/templates/front/landing.tpl');
    }

    public function setMedia(): void
    {
        parent::setMedia();
        if ($this->module instanceof Kpypromolanding) {
            $this->module->hojasDeEstilo($this);
        }
    }

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();
        $page['body_classes']['page-kpypromo'] = true;
        $page['body_classes']['page-kpypromo-landing'] = true;
        $para = $this->mascota !== '' ? ' para ' . KplClaves::mascotaTexto($this->mascota) : '';
        $conPiel = $this->campana ?? $this->viva;
        if ($conPiel !== null) {
            $page['body_classes']['kpl-piel-' . $conPiel['piel']] = true;
        }
        if ($this->campana !== null) {
            $page['meta']['title'] = $this->campana['nombre'] . $para . ' · ' . Configuration::get('PS_SHOP_NAME');
            $page['meta']['description'] = ($this->campana['titulo'] !== '' ? $this->campana['titulo'] . '. ' : '') . $this->campana['texto'] . ($this->campana['lema'] !== '' ? ' ' . $this->campana['lema'] . '.' : '');
        } else {
            $page['meta']['title'] = 'Ofertas y promociones' . $para . ' · ' . Configuration::get('PS_SHOP_NAME');
            $page['meta']['description'] = 'Las promociones de las marcas que tu peludo adora, todas aquí: Special Price, cupones, 2ª unidad, kilos gratis, regalos y outlet. Cuando una empieza o termina, esta página cambia sola.';
        }
        $page['meta']['keywords'] = '';
        $page['meta']['robots'] = $this->mascota !== '' ? 'noindex,follow' : 'index,follow';

        return $page;
    }

    public function getCanonicalURL(): string
    {
        return $this->urlPortada();
    }

    public function getBreadcrumbLinks(): array
    {
        $migas = parent::getBreadcrumbLinks();
        $migas['links'][] = ['title' => 'Ofertas y promociones', 'url' => KplPromos::urlLanding()];
        if ($this->campana !== null) {
            $migas['links'][] = ['title' => $this->campana['nombre'], 'url' => KplCampanas::url($this->campana)];
        }

        return $migas;
    }

    /** Los accesos de abajo: categorías de la tienda que siempre interesan, más el outlet. */
    private function accesos(): array
    {
        $salida = [];
        $img = $this->module->getPathUri() . 'views/img/';
        foreach ([
            ['id' => (int) Configuration::getGlobalValue('KPL_ACCESO_1'), 'nombre' => 'Especial antiparasitarios', 'sub' => 'Pipetas, collares y comprimidos', 'img' => $img . 'acceso-antiparasitarios.webp'],
            ['id' => (int) Configuration::getGlobalValue('KPL_ACCESO_2'), 'nombre' => 'Cachorro o gatito', 'sub' => 'Todo para empezar bien', 'img' => $img . 'acceso-cachorros.webp'],
        ] as $a) {
            if ($a['id'] <= 0) {
                continue;
            }
            $c = new Category($a['id'], (int) $this->context->language->id);
            if (!Validate::isLoadedObject($c) || !$c->active) {
                continue;
            }
            $salida[] = ['nombre' => $a['nombre'], 'sub' => $a['sub'], 'img' => $a['img'], 'url' => $this->context->link->getCategoryLink($c)];
        }
        $outlet = KplPromos::clave('outlet');
        if ($outlet !== null && $outlet['estado'] === 'viva') {
            $salida[] = ['nombre' => 'Outlet', 'sub' => 'Últimas unidades, hasta agotar', 'img' => $img . 'acceso-outlet.webp', 'url' => KplPromos::urlClave('outlet')];
        }

        return $salida;
    }
}
