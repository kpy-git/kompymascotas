<?php
/**
 * El centro de soporte (`/soporte` y `/soporte/<pregunta>`, del módulo kpyfaq): título y meta descripción
 * propios de cada artículo. El módulo pone en los 27 artículos el mismo título («Centro de soporte - Tienda»)
 * y una descripción vacía; aquí se sustituyen por la pregunta y el principio de la respuesta.
 *
 * Solo se lee la tabla `kpy_faq_element_lang` (y `kpy_faq_element` para el estado); si no existen, no se hace
 * nada. No se llama al módulo kpyfaq.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoSoporte
{
    private static ?bool $tablas = null;

    public static function esArticulo(): bool
    {
        return Tools::getValue('module') === 'kpyfaq'
            && Tools::getValue('controller') === 'element'
            && Tools::getValue('name', '') !== '';
    }

    public static function esIndice(): bool
    {
        return Tools::getValue('module') === 'kpyfaq' && Tools::getValue('controller') === 'display';
    }

    public static function tablasDisponibles(): bool
    {
        if (self::$tablas === null) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $a = $db->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'kpy_faq_element\'');
            $b = $db->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'kpy_faq_element_lang\'');
            self::$tablas = !empty($a) && !empty($b);
        }

        return self::$tablas;
    }

    /** Cambia título, meta descripción y Open Graph del índice o del artículo. */
    public static function reescribirCabecera(string $html, Context $ctx): string
    {
        if (!KseoAjustes::activo('KPYSEO_SOPORTE_META')) {
            return $html;
        }
        $tienda = KseoAjustes::texto('KPYSEO_MARCA_TEXTO') ?: (string) Configuration::get('PS_SHOP_NAME');

        if (self::esIndice()) {
            $titulo = 'Centro de soporte de ' . $tienda . ': envíos, pagos y devoluciones';
            $descripcion = KseoAjustes::texto('KPYSEO_SOPORTE_DESC');
            if ($descripcion === '') {
                $descripcion = 'Las respuestas del equipo de ' . $tienda . ' sobre envíos y plazos, formas de pago, devoluciones, cupones y el estado de tu pedido.';
            }

            return KseoMarcas::reemplazarCabecera($html, KseoMarcas::recortar($titulo, 70), KseoMarcas::recortar($descripcion, 160));
        }

        $articulo = self::articulo((string) Tools::getValue('name', ''), (int) $ctx->language->id);
        if ($articulo === null) {
            return $html;
        }
        $sufijo = ' · Centro de soporte de ' . $tienda;
        $pregunta = KseoMarcas::recortar($articulo['pregunta'], max(20, 70 - mb_strlen($sufijo)));
        $descripcion = KseoMarcas::recortar($articulo['respuesta'], 160);
        if ($descripcion === '') {
            $descripcion = $articulo['pregunta'] . ': la respuesta del equipo de ' . $tienda . '.';
        }

        return KseoMarcas::reemplazarCabecera($html, $pregunta . $sufijo, $descripcion);
    }

    /** @return array{pregunta: string, respuesta: string}|null */
    public static function articulo(string $rewrite, int $idLang): ?array
    {
        $rewrite = (string) preg_replace('~[^a-z0-9_-]~i', '', $rewrite);
        if ($rewrite === '' || !self::tablasDisponibles()) {
            return null;
        }
        $fila = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT fel.question, fel.answer
             FROM `' . _DB_PREFIX_ . 'kpy_faq_element_lang` fel
             INNER JOIN `' . _DB_PREFIX_ . 'kpy_faq_element` fe ON fe.id_element = fel.id_element
             WHERE fel.link_rewrite = \'' . pSQL($rewrite) . '\' AND fel.id_lang = ' . $idLang . ' AND fe.active = 1'
        );
        if (!is_array($fila) || trim((string) $fila['question']) === '') {
            return null;
        }

        return [
            'pregunta' => KseoMarcas::textoPlano((string) $fila['question']),
            'respuesta' => KseoMarcas::textoPlano((string) $fila['answer']),
        ];
    }
}
