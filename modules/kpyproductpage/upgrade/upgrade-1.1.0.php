<?php
/**
 * 1.1.0 (30/09/2026): las fichas de Boske pasan a la ficha nueva, con su contenido propio en
 * `marcas/178-boske.json` (classes/KppMarca.php). Boske estaba en «marcas con su ficha de
 * siempre» (KPP_EXCLUIR_MARCAS) porque sus fichas eran plantillas hechas a mano: se quita de ahí.
 * Las demás marcas que hubiera en la lista se quedan.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    $conMarca = [];
    foreach (glob(__DIR__ . '/../marcas/*.json') ?: [] as $fichero) {
        $conMarca[] = (int) basename($fichero);
    }
    $excluidas = array_filter(array_map('intval', explode(',', (string) Configuration::get(Kpyproductpage::CONF_EXCLUIR))));
    $quedan = array_values(array_diff($excluidas, $conMarca));
    Configuration::updateValue(Kpyproductpage::CONF_EXCLUIR, implode(',', $quedan));

    return true;
}
