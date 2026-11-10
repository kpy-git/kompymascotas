<?php
/**
 * kpygooglefeedmodifier 1.0.0 → 1.1.0: controlador «etiquetas» y página del back office.
 * Solo crea la clave y tres ajustes en Configuration. La tabla del feed NO se toca (de ella lee el generador).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0(Module $module): bool
{
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    \PrestaShop\Module\KpyGoogleFeedModifier\Etiquetas\Importador::prepararAjustes();

    return true;
}
