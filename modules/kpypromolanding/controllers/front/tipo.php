<?php
/**
 * `/promociones/{tipo}` y `/promociones/{tipo}/{perros|gatos}`: un tipo de promoción (Special
 * Price, cupones, 2ª unidad, kilos gratis, regalos, outlet) con sus marcas y su rebaja, y debajo
 * todos sus productos.
 */
class KpypromolandingTipoModuleFrontController extends KplListadoFrontController
{
    protected function nombre(): string
    {
        return 'tipo';
    }

    protected function preparar(): void
    {
        $tipo = KplClaves::tipoDeUrl((string) Tools::getValue('tipo'));
        if ($tipo === null) {
            $this->noEsta();
        }
        $nombre = KplClaves::NOMBRE_TIPO[$tipo];
        $base = KplPromos::urlTipo($tipo);
        $this->categorias = KplCategorias::activas($tipo);
        $marcas = array_values($this->conUrls(KplPromos::vivas($this->mascota, $tipo)));
        $para = $this->mascota !== '' ? ' para ' . KplClaves::mascotaTexto($this->mascota) : '';
        // [cómo se llama, qué es, la explicación]. Con una campaña viva que cambia este tipo, su
        // nombre manda en la cabecera («Black Friday: el precio ya rebajado en la ficha»).
        $textos = [
            'special_price' => ['Special Price', 'el precio ya rebajado en la ficha', 'Sin códigos ni condiciones. Lo ves rebajado, lo pagas rebajado. Elige para quién y luego la marca.'],
            'cupon' => ['Cupones con código', 'el descuento, en la cesta', 'Pulsa «Aplicar cupón» en la marca que quieras y queda puesto mientras compras.'],
            'segunda_unidad' => ['2ª unidad al −20 o −30 %', 'llévate dos', 'Se aplica sola en la cesta al llevar dos unidades del mismo formato.'],
            'kilos_gratis' => ['Kilos gratis', 'sacos con kilos de regalo', 'Formatos promocionales con más kilos por el mismo precio. Mientras haya stock.'],
            'regalos' => ['Regalos', 'un regalo con tu compra', 'Packs con un detalle dentro y productos con regalo: elige en la ficha el formato «+ Regalo» y entra en la cesta a 0 €.'],
            'outlet' => ['Outlet y liquidación', 'últimas unidades', 'Formatos que se van y liquidaciones puntuales de todas las marcas, hasta agotar existencias. Elige perros o gatos; para filtrar por marca o precio, entra en la categoría.'],
        ][$tipo];
        $campana = $this->etiquetaCampana($tipo);
        $cabeza = $campana !== '' ? $campana : $textos[0];
        $titulo = $cabeza . ($textos[1] !== '' ? ': ' . $textos[1] : '');
        $this->kpl = [
            'modo' => 'tipo',
            'tipo' => $tipo,
            'nombre_tipo' => $campana !== '' ? $campana : $nombre,
            'en_campana' => $campana !== '',
            'titulo' => $titulo,
            'titulo_plano' => $nombre,
            'texto' => $textos[2],
            'meta_titulo' => ($campana !== '' ? $campana . ' (' . $nombre . ')' : $nombre) . $para . ': promociones de ahora',
            'meta_texto' => $titulo . '. ' . $textos[2],
            'canonical' => KplPromos::urlTipo($tipo, $this->mascota),
            'quien' => ['todos' => $base, 'perro' => KplPromos::urlTipo($tipo, 'perro'), 'gato' => KplPromos::urlTipo($tipo, 'gato')],
            'marcas' => $marcas,
            'h2_marcas' => $this->mascota === '' ? 'Elige la marca' : 'Marcas con ' . $nombre . $para,
            'h2_productos' => 'Todos los productos con ' . mb_strtolower($nombre) . $para,
            'migas' => [['title' => $nombre, 'url' => $base]],
            'tipos' => KplPromos::tiposConCampana(KplPromos::tipos($this->mascota), $this->campana),
            // El cajón «Outlet y liquidación» tiene categoría de verdad: ahí están los filtros de la tienda.
            'filtros_url' => $tipo === 'outlet' ? $this->urlFiltros() : '',
        ];
        if ($this->categorias === [] || ($this->mascota !== '' && $marcas === [] && !in_array($tipo, ['kilos_gratis', 'regalos', 'outlet'], true))) {
            $this->kpl['vacio'] = ['titulo' => 'Este mes no hay ' . mb_strtolower($nombre) . $para, 'texto' => 'Pero hay más: estas son las promociones de ahora mismo.'];
            $this->kpl['noindex'] = true;
        }
    }

    /** La categoría de verdad del cajón (con los filtros de marca, precio y mascota de la tienda), o vacío. */
    private function urlFiltros(): string
    {
        $idCategoria = (int) (KplCategorias::todas()[KplClaves::PERMANENTES['outlet']]['id_category'] ?? 0);
        if ($idCategoria <= 0 || !KplCategorias::existe($idCategoria)) {
            return '';
        }

        return $this->context->link->getCategoryLink($idCategoria);
    }
}
