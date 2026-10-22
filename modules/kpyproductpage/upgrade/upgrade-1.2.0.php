<?php
/**
 * 1.2.0 (22/10/2026): «marcas con la ficha nueva en todas sus fichas» (KPP_MARCAS_COMPLETAS). Hasta
 * ahora la ficha nueva solo salía en las fichas con ingredientes y composición (analítica); en una
 * marca repasada entera, los henos, snacks, lechos y accesorios que no la publican se quedaban con la
 * de siempre. Se estrena con Bunny (219), que se añade a la lista si no está.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0($module)
{
    $lista = array_filter(array_map('intval', explode(',', (string) Configuration::get(Kpyproductpage::CONF_COMPLETAS))));
    if (!in_array(219, $lista, true)) {
        $lista[] = 219;
    }

    return Configuration::updateValue(Kpyproductpage::CONF_COMPLETAS, implode(',', $lista));
}
