<?php
/**
 * `/promociones/{clave}`: la página de una promoción (`royal-canin-special-price-perro`). Viva: su
 * banner, el filtro perro/gato y sus productos. Terminada o programada: la misma dirección
 * contesta (sin 404) diciendo qué pasa y enseñando las promociones de ahora.
 */
class KpypromolandingPromoModuleFrontController extends KplListadoFrontController
{
    protected function nombre(): string
    {
        return 'promo';
    }

    protected function preparar(): void
    {
        $clave = mb_strtolower(trim((string) Tools::getValue('clave')));
        if (!KplClaves::claveValida($clave)) {
            $this->noEsta();
        }
        $c = KplPromos::clave($clave);
        if ($c === null) {
            // Una clave que el resumen no conoce (promoción borrada): si hay categoría nuestra, es «terminada».
            $categorias = KplCategorias::todas();
            if (!isset($categorias[$clave])) {
                $this->noEsta();
            }
            $f = $categorias[$clave];
            $c = [
                'clave' => $clave, 'tipo' => (string) $f['tipo'], 'nombre_tipo' => KplClaves::NOMBRE_TIPO[$f['tipo']] ?? '', 'id_category' => (int) $f['id_category'],
                'id_manufacturer' => (int) $f['id_manufacturer'], 'marca' => '', 'marca_url' => '', 'mascota' => (string) $f['mascota'], 'pct' => 0, 'pct_texto' => '0',
                'codigo' => '', 'desde' => null, 'hasta' => null, 'hasta_texto' => '', 'n' => ['todos' => 0, 'perro' => 0, 'gato' => 0],
                'titulo' => 'Esta promoción ha terminado', 'texto' => '', 'imagenes' => [], 'lado' => false, 'logo' => '', 'estado' => 'terminada', 'permanente' => false,
            ];
        }
        $base = KplPromos::urlClave($clave);
        // Con el filtro de mascota, la cabecera enseña el saco de esa mascota; con una campaña viva,
        // su etiqueta se llama como la campaña.
        $c = $this->conUrls([$c])[0];
        $viva = $c['estado'] === 'viva' && (int) $c['id_category'] > 0;
        $this->categorias = $viva ? [(int) $c['id_category']] : [];
        $duracion = KplTextos::duracion($c['desde'] ?? null, $c['hasta'] ?? null);
        $cupon = '';
        $aplicado = false;
        if ($viva && $c['tipo'] === 'cupon' && (string) $c['codigo'] !== '') {
            $cupon = (string) $c['codigo'];
            // El cupón que lleva puesto el cliente: la cookie `kpy_cupon` de kpycoupons (ya escrita si
            // llegó con `?kpc=`), sin preguntarle nada a kpycoupons.
            $aplicado = KplPromos::cuponPuesto() === mb_strtoupper($cupon);
        }
        $miga = trim(($c['marca'] !== '' ? $c['marca'] . ' · ' : '') . $c['nombre_tipo'] . ($c['mascota'] !== '' ? ' · ' . ucfirst(KplClaves::mascotaTexto($c['mascota'])) : ''));
        $this->kpl = [
            'modo' => 'promo',
            'clave' => $clave,
            'c' => $c,
            'estado' => $viva ? 'viva' : $c['estado'],
            'en_campana' => $viva && !empty($c['en_campana']),
            'titulo' => $c['titulo'],
            'titulo_plano' => strip_tags($c['titulo']),
            'texto' => $c['texto'],
            'meta_titulo' => strip_tags($c['titulo']),
            'meta_texto' => $c['texto'] . ($c['hasta_texto'] !== '' ? ' ' . $c['hasta_texto'] . '.' : ''),
            'canonical' => $base,
            'quien' => $this->quien($base),
            'duracion' => $duracion,
            'cupon' => $cupon,
            'cupon_aplicado' => $aplicado,
            'url_cupon' => $cupon !== '' ? KplPromos::conMascota($base, $this->mascota) . (str_contains($base, '?') ? '&' : '?') . 'kpc=' . rawurlencode($cupon) : '',
            'con_chips' => $viva && $c['mascota'] === '' && (int) $c['n']['perro'] > 0 && (int) $c['n']['gato'] > 0,
            'migas' => [['title' => $miga !== '' ? $miga : $clave, 'url' => $base]],
            'otras' => array_values($this->conUrls(array_slice(array_filter(KplPromos::vivas(), static fn (array $x): bool => $x['clave'] !== $clave), 0, 4))),
        ];
        if (!$viva) {
            $this->kpl['noindex'] = true;
            if ($c['estado'] === 'programada') {
                $this->kpl['vacio'] = [
                    'icono' => '📅',
                    'titulo' => 'Esta promoción aún no ha empezado',
                    'texto' => strip_tags($c['titulo']) . ($c['desde'] ? ' empieza el ' . KplTextos::fecha($c['desde']) . '.' : '.') . ' Mientras tanto, estas son las promociones de ahora mismo.',
                ];
            } else {
                $this->kpl['vacio'] = [
                    'icono' => '⏰',
                    'titulo' => 'Esta promoción ha terminado',
                    'texto' => ($c['marca'] !== '' ? strip_tags($c['titulo']) . ($c['hasta'] ? ' terminó el ' . KplTextos::fecha($c['hasta']) . '.' : '.') : 'La promoción de esta dirección ya no está en marcha.') . ' Pero hay más: estas son las promociones de ahora mismo.',
                ];
            }
        } elseif ($this->mascota !== '' && (int) ($c['n'][$this->mascota] ?? 0) === 0) {
            $this->kpl['vacio'] = ['icono' => '🐾', 'titulo' => 'En esta promoción no hay nada para ' . KplClaves::mascotaTexto($this->mascota), 'texto' => 'Quita el filtro para ver todo lo que entra, o mira las demás promociones.'];
        }
    }
}
