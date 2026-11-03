<?php
/**
 * 3.2.0 (1/11/2026): varios cupones a la vez en la misma cesta (uno por marca), el cupón «para
 * todos» baja el precio grande de la ficha, y la campaña (Black Friday) en toda la ficha y en las
 * filas de la cesta. Nada que hacer en la base de datos: la cookie `kpy_cupon` pasa a ser una lista
 * (lo que ya hubiera se lee igual) y no hay tablas ni hooks nuevos. Se puede ejecutar más de una vez.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_2_0(Module $module): bool
{
    // Las filas de los hooks propios con su descripción al día (idempotente).
    if (method_exists($module, 'asegurarHooks')) {
        $module->asegurarHooks();
    }

    return true;
}
