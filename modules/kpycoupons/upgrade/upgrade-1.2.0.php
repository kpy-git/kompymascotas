<?php
/**
 * 1.2.0: las etiquetas de promoción en los listados y en el buscador, y la entrada del menú
 * (Catálogo → Kompy · Promociones).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0($modulo): bool
{
    if (!$modulo->isRegisteredInHook('actionProductFlagsModifier')
        && !$modulo->registerHook('actionProductFlagsModifier')) {
        return false;
    }

    if (Tab::getIdFromClassName(Kpycoupons::PESTANA)) {
        return true;
    }
    $tab = new Tab();
    $tab->class_name = Kpycoupons::PESTANA;
    $tab->module = $modulo->name;
    $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
    $tab->active = 1;
    $tab->icon = 'local_offer';
    foreach (Language::getLanguages(false) as $idioma) {
        $tab->name[(int) $idioma['id_lang']] = 'Kompy · Promociones';
    }

    return (bool) $tab->add();
}
