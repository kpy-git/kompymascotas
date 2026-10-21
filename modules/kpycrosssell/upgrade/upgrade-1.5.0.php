<?php
/**
 * 1.5.0 (Rafa, 30/09/2026):
 *
 *   - Se llama **«Kompy · Caprichitos»** (antes «Kompy · Llévalo junto»): en el menú y en la lista de
 *     módulos. El nombre técnico, `kpycrosssell`, no cambia.
 *   - La foto y el nombre de cada sugerencia llevan a su ficha, y «Atrás» vuelve con todo como estaba.
 *   - La regla de «llevados juntos» de cada cesta dura hasta el fin del descuento de la marca (no un
 *     mes) y las que sobran se barren solas cada 6 horas.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_0($modulo): bool
{
    $id = (int) Tab::getIdFromClassName(Kpycrosssell::PESTANA);
    if ($id > 0) {
        $tab = new Tab($id);
        foreach (Language::getLanguages(false) as $idioma) {
            $tab->name[(int) $idioma['id_lang']] = Kpycrosssell::NOMBRE;
        }
        $tab->save();
    }
    try {
        KcsJuntos::barrer();
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycrosssell 1.5.0, barrido: ' . $e->getMessage(), 2, null, 'Module', 0, true);
    }
    Configuration::updateGlobalValue(KcsJuntos::LIMPIEZA, time() + 21600);

    return true;
}
