<?php
/**
 * 1.2.0 (16/10/2026): el regalo con la compra de kpycoupons (2.1.0) entra en la landing por lo que
 * regala: «Kilos gratis» si es el saco pequeño de la misma ficha (`boske-kilos-gratis`, «Boske ·
 * Kilos gratis») y «Regalos» si es otra cosa (`boske-regalos`, «Boske · Regalos»). Con textos
 * propios en el banner («Boske: kilos GRATIS con tu saco», con los tamaños).
 *   - Las categorías del tipo «regalos» se llamaban «Regalo» y pasan a «Regalos» (PrestaShop no
 *     cambia el nombre de una categoría que ya existe: se hace aquí, una vez).
 *   - Una clave escrita «kilosgratis» (la tienda de pruebas, el 16/10) pasa a «kilos-gratis», con su
 *     categoría, para que no quede una vacía de más.
 * Se puede ejecutar más de una vez.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0(Module $module): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $ok = true;

    $ok = $ok && (bool) $db->execute(
        "UPDATE {$p}category_lang cl
         INNER JOIN {$p}kpy_promo_categoria pc ON pc.id_category = cl.id_category AND pc.tipo = 'regalos'
         SET cl.meta_title = IF(cl.meta_title LIKE '%Regalos%', cl.meta_title, REPLACE(cl.meta_title, 'Regalo', 'Regalos')),
             cl.name = REPLACE(cl.name, 'Regalo', 'Regalos')
         WHERE cl.name LIKE '%Regalo%' AND cl.name NOT LIKE '%Regalos%'"
    );

    foreach ($db->executeS("SELECT id_kpy_promo_categoria, clave, id_category FROM {$p}kpy_promo_categoria WHERE clave LIKE '%kilosgratis%'") ?: [] as $f) {
        $nueva = str_replace('kilosgratis', 'kilos-gratis', (string) $f['clave']);
        if ((int) $db->getValue("SELECT COUNT(*) FROM {$p}kpy_promo_categoria WHERE clave = '" . pSQL($nueva) . "'") > 0) {
            continue;
        }
        $ok = $ok && $db->update('kpy_promo_categoria', ['clave' => pSQL($nueva)], 'id_kpy_promo_categoria = ' . (int) $f['id_kpy_promo_categoria']);
        $ok = $ok && $db->update('category_lang', ['link_rewrite' => pSQL($nueva)], 'id_category = ' . (int) $f['id_category']);
    }
    $ok = $ok && (bool) $db->execute("UPDATE {$p}kpy_promo_promo SET clave = REPLACE(clave, 'kilosgratis', 'kilos-gratis') WHERE clave LIKE '%kilosgratis%'");

    // Los textos y las categorías nuevas se escriben en la próxima sincronización.
    Configuration::updateGlobalValue(Kpypromolanding::CONF_PENDIENTE, 1);

    return $ok;
}
