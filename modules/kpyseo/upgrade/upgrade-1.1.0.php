<?php
/**
 * kpyseo 1.0.0 → 1.1.0: redirecciones (hook actionDispatcherBefore y cuatro ajustes nuevos).
 * No toca ningún ajuste ya configurado: solo crea las claves que falten.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0(Module $module): bool
{
    require_once dirname(__DIR__) . '/classes/KseoAjustes.php';
    KseoAjustes::instalar();

    return $module->isRegisteredInHook('actionDispatcherBefore') || $module->registerHook('actionDispatcherBefore');
}
