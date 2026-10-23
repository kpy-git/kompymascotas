<?php
/**
 * `/blackfriday` (y cualquier campaña): la misma portada de promociones con la piel, los textos y las
 * fechas de la campaña. Antes de empezar dice cuándo empieza; durante, sus promociones con el lema;
 * después, que ha terminado (y las promociones de ahora). Todo por la hora de la visita.
 */
require_once __DIR__ . '/landing.php';

class KpypromolandingCampanaModuleFrontController extends KpypromolandingLandingModuleFrontController
{
    public function init(): void
    {
        $c = KplCampanas::porClave((string) Tools::getValue('campana'));
        if ($c === null || (int) $c['activa'] !== 1) {
            $this->redirect_after = '404';
            $this->redirect();
        }
        $this->campana = $c;
        parent::init();
    }
}
