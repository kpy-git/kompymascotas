<?php
/**
 * `/promociones/todas`: todos los productos en promoción, de todas las claves vivas, con el
 * filtro de mascota y el orden de siempre (más descuento primero).
 */
class KpypromolandingTodasModuleFrontController extends KplListadoFrontController
{
    protected function nombre(): string
    {
        return 'todas';
    }

    protected function preparar(): void
    {
        $base = KplPromos::urlTodas();
        $this->categorias = KplCategorias::activas();
        $para = $this->mascota !== '' ? ' para ' . KplClaves::mascotaTexto($this->mascota) : '';
        $this->kpl = [
            'modo' => 'todas',
            'titulo' => 'Todo lo que está en promoción' . $para,
            'titulo_plano' => 'Todas las promociones',
            'texto' => 'Special Price, cupones, 2ª unidad, kilos gratis, regalos y outlet, en una sola lista. Lo más rebajado, primero.',
            'meta_titulo' => 'Todos los productos en promoción' . $para,
            'meta_texto' => 'Todo lo que está en promoción en Kompy ahora mismo, de todas las marcas, con el mayor descuento primero.',
            'canonical' => $base,
            'quien' => $this->quien($base),
            'migas' => [['title' => 'Todas', 'url' => $base]],
            'tipos' => KplPromos::tiposConCampana(KplPromos::tipos($this->mascota), $this->campana),
            'en_campana' => $this->campana !== null,
        ];
        if ($this->categorias === []) {
            $this->kpl['vacio'] = ['titulo' => 'Ahora mismo no hay promociones', 'texto' => 'Vuelve pronto: cuando una empieza, esta página cambia sola.'];
            $this->kpl['noindex'] = true;
        }
    }
}
