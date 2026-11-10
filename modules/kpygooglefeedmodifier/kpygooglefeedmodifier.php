<?php

declare(strict_types=1);

use PrestaShop\Module\KpyGoogleFeedModifier\Etiquetas\Importador;
use PrestaShop\Module\KpyGoogleFeedModifier\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyGoogleFeedModifier extends Module
{
    public function __construct()
    {
        $this->name = 'kpygooglefeedmodifier';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'KPY Team';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = [
            'min' => '9.1',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('KpyGoogleFeedModifier', [], 'Modules.Kpygooglefeedmodifier.Admin');
        $this->description = $this->trans('Google feed attributes modifier', [], 'Modules.Kpygooglefeedmodifier.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpygooglefeedmodifier.Admin');
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $installer = new Installer();
        if (!$installer->install($this)) {
            return false;
        }
        Importador::prepararAjustes();

        return true;
    }

    /**
     * Ojo: la desinstalación borra la tabla de la que lee el feed (uninstall.sql). Las versiones nuevas llegan
     * siempre como actualización, nunca desinstalando ni reinicializando.
     *
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        Importador::quitarAjustes();
        $installer = new Installer();

        return $installer->uninstall($this);
    }

    /**
     * @see https://devdocs.prestashop.com/8/modules/creation/module-translation/new-system/#translating-your-module
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    // ------------------------------------------------------------ back office

    /**
     * Página del módulo: última actualización, recuento por etiqueta, buscador por id y la clave con «Generar nueva».
     * Solo lectura salvo la clave.
     */
    public function getContent(): string
    {
        $avisos = [];
        if (Tools::isSubmit('submitKgfmNuevaClave')) {
            Configuration::updateValue(Importador::CLAVE_AJUSTE, Importador::claveNueva());
            $avisos[] = ['success', 'Clave nueva generada. Hay que pegarla en el Etiquetador (Google Ads): la anterior ya no vale.'];
        }

        $busqueda = trim((string) Tools::getValue('kgfm_buscar', ''));
        $resultado = null;
        if ($busqueda !== '') {
            $resultado = Importador::buscar($busqueda);
        }

        $estado = Importador::estado();
        $clave = (string) Configuration::get(Importador::CLAVE_AJUSTE);
        $url = $this->context->link->getModuleLink($this->name, 'etiquetas', [], true);
        $accion = htmlspecialchars((string) ($_SERVER['REQUEST_URI'] ?? ''), ENT_QUOTES, 'UTF-8');

        $e = static fn ($texto): string => htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
        $html = '';
        foreach ($avisos as [$tipo, $texto]) {
            $html .= '<div class="alert alert-' . $e($tipo) . '">' . $e($texto) . '</div>';
        }

        // última actualización
        $html .= '<div class="panel"><div class="panel-heading"><i class="icon-tags"></i> Etiquetas de campaña del feed (custom_label_0)</div>';
        $html .= '<p>La tabla <code>' . $e(_DB_PREFIX_ . Importador::TABLA) . '</code> tiene <strong>' . $e(number_format($estado['total'], 0, ',', '.')) . '</strong> filas (producto-combinación → etiqueta). De ella lee el generador del feed de Merchant.</p>';
        if ($estado['ultima_fecha'] !== '') {
            $html .= '<p>Última actualización recibida: <strong>' . $e($estado['ultima_fecha']) . '</strong> · ' . $e($estado['ultima_filas']) . ' filas · origen «' . $e($estado['ultima_origen']) . '».</p>';
        } else {
            $html .= '<p>Todavía no ha llegado ninguna actualización por el controlador (la tabla tiene la carga inicial).</p>';
        }
        $html .= '<table class="table" style="max-width:32rem"><thead><tr><th>Etiqueta</th><th class="text-right">Filas</th></tr></thead><tbody>';
        foreach ($estado['por_etiqueta'] as $etiqueta => $n) {
            $html .= '<tr><td><code>' . $e($etiqueta) . '</code></td><td class="text-right">' . $e(number_format($n, 0, ',', '.')) . '</td></tr>';
        }
        if ($estado['por_etiqueta'] === []) {
            $html .= '<tr><td colspan="2">La tabla está vacía.</td></tr>';
        }
        $html .= '</tbody></table></div>';

        // buscador
        $html .= '<div class="panel"><div class="panel-heading"><i class="icon-search"></i> Buscar un id</div>';
        $html .= '<form method="post" action="' . $accion . '" class="form-inline">';
        $html .= '<input type="text" name="kgfm_buscar" class="form-control" placeholder="producto-combinación, p. ej. 236-328" value="' . $e($busqueda) . '" style="width:22rem"> ';
        $html .= '<button type="submit" class="btn btn-default"><i class="icon-search"></i> Buscar</button></form>';
        if ($busqueda !== '') {
            if ($resultado === null) {
                $html .= '<p class="help-block">«' . $e($busqueda) . '» no está en la tabla (o no tiene la forma producto-combinación).</p>';
            } else {
                $html .= '<p class="help-block">«' . $e($busqueda) . '» → <code>' . $e($resultado) . '</code></p>';
            }
        }
        $html .= '</div>';

        // clave
        $html .= '<div class="panel"><div class="panel-heading"><i class="icon-key"></i> Clave del Etiquetador</div>';
        $html .= '<p>El Etiquetador manda la lista con <code>POST ' . $e($url) . '</code> y la clave en la cabecera <code>X-Kompy-Clave</code>. Si la clave no coincide, contesta 403 y no toca nada.</p>';
        $html .= '<div class="form-group"><label>Clave</label><input type="text" class="form-control" readonly value="' . $e($clave) . '" onclick="this.select()" style="max-width:32rem;font-family:monospace"></div>';
        $html .= '<form method="post" action="' . $accion . '"><button type="submit" name="submitKgfmNuevaClave" value="1" class="btn btn-default"><i class="icon-refresh"></i> Generar nueva</button> <span class="help-block" style="display:inline">La anterior deja de valer en el acto; hay que pegar la nueva en el Etiquetador.</span></form>';
        $html .= '</div>';

        return $html;
    }
}
