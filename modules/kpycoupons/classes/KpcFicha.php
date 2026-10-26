<?php
/**
 * La ficha de producto, pintada por este módulo en los huecos del tema de Julián (3.0.0, 23/10/2026).
 *
 * Hasta la 2.4 la ficha la pintaba `kpyproductbuy`, que preguntaba a este módulo por un hook suyo.
 * Julián no lo instala en producción (la ficha rediseñada vive en el tema), así que todo lo que es
 * de promociones lo pinta ahora `kpycoupons` por los hooks que el tema ya llama:
 *
 *   displayProductPriceBlock (type=weight, product_sheet)   el bloque bajo el precio: cupón, 2ª unidad,
 *                                                           la elección «12 kg» / «12 kg + 3 kg gratis»,
 *                                                           «Quedan N», nuestro €/kg
 *   displayProductAttributeFlags                            el chip de cada tarjeta de formato
 *   actionPresentProduct                                    el precio presentado cuando no es el del core
 *                                                           (regalo elegido, cupo agotado, cupón puesto)
 *   actionProductFlagsModifier                              la etiqueta de la foto, coherente con lo anterior
 *   actionFrontControllerSetVariables                       `$modules.kpycoupons`, por si el tema lo quiere
 *
 * Todo sale de **una** instancia por producto y petición (`de()`): el refresco de PrestaShop llama a
 * `getTemplateVarProduct` dos veces y a los hooks del precio cinco, y aquí se calcula una vez.
 *
 * La regla (qué se enseña, qué precio se presenta, qué texto lleva cada cosa) es pura y se prueba sin
 * tienda (`tests/ficha.php`): `modelo()`, `quedanTexto()`, `fin()`, `esRobot()`, `cortoDe()`.
 *
 * Lo que decide el precio presentado (ver `docs/plan-desacoplar-kpycoupons.md`, 3.4):
 *   - opción con regalo elegida (`kpy_regalo=1`): el precio habitual, sin su Special Price;
 *   - cupo agotado para esta cesta: el precio habitual (es lo que cobrará la cesta);
 *   - cupón puesto por el cliente (cookie), que no sea «para todos» (`auto_web`) ni un robot: el
 *     precio con el cupón.
 *   Nunca se toca `price_amount`: el JSON-LD y `og:price` dicen el precio del feed (norma de Merchant).
 */
class KpcFicha
{
    /** @var array<int, self> una por producto, en la petición */
    private static array $instancias = [];

    private Kpycoupons $modulo;
    private int $idTienda;
    private int $idProducto;
    private int $idCart;
    /** @var array<int, array> por `id_product_attribute` (0 si el producto no tiene formatos) */
    private array $formatos = [];
    /** @var array<int, ?int> `id_attribute` => `id_product_attribute` (null si un atributo cae en varios formatos) */
    private array $porAtributo = [];
    /** @var ?array el cupón o la 2ª unidad del producto (ver `promoDeCesta`) */
    private ?array $promo = null;
    /** @var ?array el regalo con la compra, por formato disparador (`KpcRegalo::paraFicha`) */
    private ?array $regalo = null;
    /** @var array<int, array{etiqueta: string, clase: string, quedan: ?int, id_kpy_coupon: int}> el Special Price propio de cada formato */
    private array $precios = [];
    private int $kpyRegalo;
    private int $cantidad;
    private bool $robot;
    /** @var array<int, array> `estado()` ya calculado, por formato */
    private array $estados = [];
    /** @var array<string, ?array> la capa de campaña, por firma de tipos */
    private array $campanas = [];
    private ?array $textos = null;

    private function __construct(Kpycoupons $modulo, int $idProducto)
    {
        $contexto = Context::getContext();
        $this->modulo = $modulo;
        $this->idTienda = (int) $contexto->shop->id;
        $this->idProducto = $idProducto;
        $this->idCart = $contexto->cart instanceof Cart ? (int) $contexto->cart->id : 0;
        $this->kpyRegalo = (int) Tools::getValue('kpy_regalo', 0) === 1 ? 1 : 0;
        $this->cantidad = max(1, min(999, (int) Tools::getValue('quantity_wanted', 1)));
        $this->robot = self::esRobot((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $this->cargar();
    }

    /** La ficha de un producto, una vez por petición. */
    public static function de(Kpycoupons $modulo, int $idProducto): self
    {
        return self::$instancias[$idProducto] ??= new self($modulo, $idProducto);
    }

    /** Tras cambiar algo en la misma petición (pruebas): que se vuelva a calcular. */
    public static function olvidar(): void
    {
        self::$instancias = [];
    }

    // ------------------------------------------------------------------------------------------
    // Lo que preguntan los hooks

    /** ¿Tiene este producto algo que decir en la ficha? */
    public function tienePromocion(): bool
    {
        return $this->promo !== null || $this->regalo !== null || $this->precios !== [] || $this->modulo->elegido() !== '';
    }

    /** El formato de la petición: `group[]` del tema > `id_product_attribute` > el de por defecto. */
    public function ipaDeLaPeticion(): int
    {
        $grupos = Tools::getValue('group');
        if (is_array($grupos)) {
            foreach ($grupos as $idAtributo) {
                $ipa = $this->porAtributo[(int) $idAtributo] ?? null;
                if ($ipa !== null) {
                    return $ipa;
                }
            }
        }
        $ipa = (int) Tools::getValue('id_product_attribute', 0);
        if ($ipa > 0 && isset($this->formatos[$ipa])) {
            return $ipa;
        }
        if ($this->formatos !== [] && !isset($this->formatos[0])) {
            foreach ($this->formatos as $f) {
                if ($f['defecto']) {
                    return (int) $f['ipa'];
                }
            }

            return (int) array_key_first($this->formatos);
        }

        return 0;
    }

    /**
     * El estado de la ficha para un formato: qué bloque se pinta y qué precio se presenta. Ver
     * `modelo()` para las claves.
     */
    public function estado(int $ipa): array
    {
        if (isset($this->estados[$ipa])) {
            return $this->estados[$ipa];
        }
        $f = $this->formatos[$ipa] ?? null;
        if ($f === null) {
            return $this->estados[$ipa] = self::vacio();
        }
        $peticion = [
            'kpy_regalo' => $this->kpyRegalo,
            'cantidad' => $this->cantidad,
            'robot' => $this->robot,
            'hoy' => time(),
        ];
        $m = self::modelo($f, $this->promo, $this->regalo, $this->precios[$ipa] ?? null, $peticion, $this->textos(), $this->euros());
        $m['campana'] = $this->campana($ipa, $m);
        if ($m['campana'] !== null) {
            $m = self::conCampana($m, $m['campana']);
        }
        // Lo que `ficha.js` necesita para montar las tarjetas de regalo en la fila de formatos del tema.
        $m['datos'] = json_encode(['tarjetas' => $this->tarjetas(), 'regalo' => $m['regalo_elegido'], 'ipa' => $ipa], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?: '{}';

        return $this->estados[$ipa] = $m;
    }

    /**
     * Las tarjetas «12 kg + 3 kg gratis» de la ficha: una por formato disparador con regalo
     * disponible. Las monta `ficha.js` en la fila de formatos del tema, con la misma estructura que
     * las tarjetas de Julián (mismo radio del formato, más `kpy_regalo=1`): el tema recorre sus
     * atributos y no deja añadir una tarjeta desde el servidor sin tocar su plantilla.
     *
     * @return array<int, array>
     */
    public function tarjetas(): array
    {
        $salida = [];
        if ($this->regalo === null || empty($this->regalo['por'])) {
            return $salida;
        }
        foreach ($this->regalo['por'] as $ipa => $r) {
            $f = $this->formatos[(int) $ipa] ?? null;
            if ($f === null || !is_array($r) || !empty($r['agotado']) || (array_key_exists('quedan', $r) && $r['quedan'] !== null && (int) $r['quedan'] <= 0)) {
                continue;
            }
            $salida[] = self::tarjeta($f, $r, $this->textos(), $this->euros());
        }

        return $salida;
    }

    /** ¿Este formato tiene tarjeta con regalo? (El chip «+ 3 kg gratis» lo lleva entonces la tarjeta, no el formato.) */
    private function tieneTarjeta(int $ipa): bool
    {
        foreach ($this->tarjetas() as $t) {
            if ((int) $t['ipa'] === $ipa) {
                return true;
            }
        }

        return false;
    }

    /**
     * El chip de la tarjeta de un formato (hook `displayProductAttributeFlags`, que llega con el
     * `id_attribute`): la promoción del formato (`KpcFlags::deFormato`) o el nombre propio de su
     * Special Price («Liquidación», «Outlet», «Bajada de precio»). Null si no hay nada que decir o el
     * atributo cae en varios formatos (productos con dos grupos de atributos).
     *
     * @return ?array{type: string, label: string, background: string, color: string}
     */
    public function chip(int $idAtributo): ?array
    {
        $ipa = $this->porAtributo[$idAtributo] ?? null;
        if ($ipa === null) {
            return null;
        }
        $chip = KpcFlags::deFormato($this->idProducto, $ipa, $this->modulo);
        if ($chip !== null && (string) $chip['type'] === 'kpc-regalo' && $this->tieneTarjeta($ipa)) {
            // El regalo tiene su tarjeta aparte («12 kg + 3 kg gratis»): el formato de siempre se queda con lo suyo.
            $chip = null;
        }
        if ($chip === null) {
            $suyo = $this->precios[$ipa] ?? null;
            if ($suyo !== null && $suyo['clase'] !== 'especial' && $suyo['clase'] !== '') {
                $tipo = 'kpc-' . $suyo['clase'];
                $chip = [
                    'type' => $tipo,
                    'label' => $suyo['etiqueta'],
                    'background' => KpcFlags::COLORES[$tipo] ?? '#3A3A3A',
                    'color' => '#ffffff',
                ];
            }
        }
        if ($chip === null) {
            return null;
        }
        // Durante una campaña, el chip del cupón, de la 2ª unidad o del Special Price lleva su nombre.
        $tipo = self::tipoDeChip((string) $chip['type']);
        $campana = $tipo !== '' ? $this->campana($ipa, null, [$tipo]) : null;
        $e = $campana['tipos'][$tipo] ?? null;
        if (is_array($e) && trim((string) ($e['label'] ?? '')) !== '') {
            $chip['label'] = KpcFlags::corto(trim((string) $e['label']));
            $chip['background'] = self::color($e['background'] ?? '', (string) $chip['background']);
            $chip['color'] = self::color($e['color'] ?? '', (string) $chip['color']);
        }

        return $chip;
    }

    /**
     * El precio presentado del producto de la ficha (hook `actionPresentProduct`), cuando no es el
     * del core: pisa las claves que lee el tema (`offsetSet` sobre un `ProductLazyArray`), nunca
     * `price_amount` ni la fila cruda.
     *
     * @param ArrayAccess $p el producto presentado
     */
    public function presentar($p): void
    {
        $ipa = (int) ($p['id_product_attribute'] ?? 0);
        $e = $this->estado($ipa);
        if ($e['especial_fuera']) {
            self::poner($p, 'kpy_special_price', false);
        }
        $pr = $e['presentar'];
        if ($pr === null) {
            return;
        }
        $precio = (float) $pr['precio'];
        $pvpr = (float) $pr['pvpr'];
        $locale = Context::getContext()->getCurrentLocale();
        $moneda = Context::getContext()->currency ? (string) Context::getContext()->currency->iso_code : 'EUR';
        $dinero = static fn (float $x): string => $locale ? $locale->formatPrice($x, $moneda) : number_format($x, 2, ',', '.') . ' €';
        $numero = static fn (float $x): string => $locale ? $locale->formatNumber($x) : number_format($x, 2, ',', '.');
        $hayRebaja = $pvpr > $precio + 0.005;
        $pct = $hayRebaja ? round((1 - $precio / $pvpr) * 100, 2) : 0.0;
        $valores = [
            'price' => $dinero($precio),
            'regular_price' => $dinero($pvpr),
            'regular_price_amount' => $pvpr,
            'has_discount' => $hayRebaja,
            'discount_type' => 'percentage',
            'discount_percentage' => $numero(-$pct) . '%',
            'discount_percentage_absolute' => $numero($pct) . '%',
            'discount_amount' => $dinero(max(0.0, $pvpr - $precio)),
            'discount_amount_to_display' => '-' . $dinero(max(0.0, $pvpr - $precio)),
            'discount_to_display' => $dinero(max(0.0, $pvpr - $precio)),
            'kpc_precio_motivo' => (string) $pr['motivo'],
        ];
        foreach ($valores as $clave => $valor) {
            self::poner($p, $clave, $valor);
        }
    }

    /**
     * Lo que se publica en `$modules.kpycoupons` para el tema (oferta a Julián; ninguna plantilla
     * nuestra lo lee): por `id_attribute`, el chip de su formato; y el estado del formato elegido.
     */
    public function paraTema(): array
    {
        $formatos = [];
        foreach ($this->porAtributo as $idAtributo => $ipa) {
            if ($ipa === null) {
                continue;
            }
            $chip = $this->chip((int) $idAtributo);
            $suyo = $this->precios[$ipa] ?? null;
            $formatos[(int) $idAtributo] = [
                'tipo' => $chip !== null ? (string) $chip['type'] : '',
                'texto' => $chip !== null ? (string) $chip['label'] : '',
                'fondo' => $chip !== null ? (string) $chip['background'] : '',
                'color' => $chip !== null ? (string) $chip['color'] : '',
                'quedan' => $suyo !== null ? $suyo['quedan'] : null,
            ];
        }
        $ipa = $this->ipaDeLaPeticion();
        $e = $this->estado($ipa);

        return [
            'id_product_attribute' => $ipa,
            'estado' => (string) $e['estado'],
            'formatos' => $formatos,
        ];
    }

    // ------------------------------------------------------------------------------------------
    // La regla, pura

    /**
     * Qué se pinta y qué precio se presenta para un formato. Sin PrestaShop dentro.
     *
     * @param array $f el formato: ipa, nombre, corto, precio (el de hoy, con su Special Price), pvpr,
     *                 habitual (?float: PVPR × (1 − old_discount) si el Special Price rebaja de verdad),
     *                 especial (?array{old_discount: float, expira: string}), gramos, unidades, envase, litros
     * @param ?array $promo el cupón o la 2ª unidad del producto: tipo, codigo, pct, ipas, aplicado,
     *                      auto_web, quedan, quedan_por, url (ver `promoDeCesta`)
     * @param ?array $regalo lo de `KpcRegalo::paraFicha`: ipas, por[ipa], banda…
     * @param ?array $precio el Special Price propio del formato: etiqueta, clase, quedan (null = sin cupo)
     * @param array $peticion kpy_regalo (0|1), cantidad, robot, hoy (timestamp)
     * @param array<string, string> $textos
     * @param callable(float): string $euros
     */
    public static function modelo(array $f, ?array $promo, ?array $regalo, ?array $precio, array $peticion, array $textos, callable $euros): array
    {
        $t = static fn (string $clave, array $r = []): string => str_replace(array_keys($r), array_values($r), (string) ($textos[$clave] ?? self::TEXTOS[$clave] ?? $clave));
        $m = self::vacio();
        $ipa = (int) $f['ipa'];
        $p = round((float) $f['precio'], 2);
        $pvpr = round((float) $f['pvpr'], 2);
        $habitual = isset($f['habitual']) && $f['habitual'] !== null ? round((float) $f['habitual'], 2) : null;
        $especial = $habitual !== null && $habitual > $p + 0.01;
        $kilos = !empty($f['gramos']) ? (float) $f['gramos'] / 1000 : null;
        $litros = !empty($f['litros']);
        $unidades = !empty($f['unidades']) && (int) $f['unidades'] > 1 ? (int) $f['unidades'] : null;
        $envase = (string) ($f['envase'] ?? '');
        $hoy = (int) ($peticion['hoy'] ?? time());
        $robot = !empty($peticion['robot']);
        $cantidad = max(1, (int) ($peticion['cantidad'] ?? 1));
        $etiquetaEspecial = $precio !== null && $precio['etiqueta'] !== '' ? (string) $precio['etiqueta'] : $t('especial');
        $claseEspecial = $precio !== null && $precio['clase'] !== '' ? (string) $precio['clase'] : 'especial';
        $quedanEspecial = $especial && $precio !== null && array_key_exists('quedan', $precio) && $precio['quedan'] !== null ? (int) $precio['quedan'] : null;
        $agotado = $especial && $quedanEspecial === 0;

        // La unidad en la que se compara («4,27 €/kg», «0,95 €/lata»).
        $porUnidad = static function (float $importe) use ($kilos, $litros, $unidades, $envase, $euros): string {
            if ($unidades !== null && $envase !== '') {
                return $euros($importe / $unidades) . '/' . $envase;
            }
            if ($kilos) {
                return $euros($importe / $kilos) . ($litros ? '/l' : '/kg');
            }

            return '';
        };

        // 1. El regalo con la compra: la opción «12 kg + 3 kg gratis», si el formato es disparador.
        $r = is_array($regalo['por'][$ipa] ?? null) ? $regalo['por'][$ipa] : null;
        if ($r !== null && !empty($r['agotado'])) {
            $r = null;
        }
        // Con cupo de regalos y ninguno que quede para esta cesta: la opción no se ofrece y se dice.
        if ($r !== null && array_key_exists('quedan', $r) && $r['quedan'] !== null && (int) $r['quedan'] <= 0) {
            $r = null;
            $m['aviso'] = $t('regalo_agotado');
        }
        $regaloElegido = $r !== null && (int) ($peticion['kpy_regalo'] ?? 0) === 1;
        $m['regalo_elegido'] = $regaloElegido ? 1 : 0;

        // 2. El precio que se presenta, si no es el del core.
        if ($regaloElegido) {
            $n = max(1, (int) ($r['n'] ?? 1));
            $pr = $habitual ?? $p;
            $esKilos = ($r['clase'] ?? '') === 'kilos';
            $kilosCon = $kilos !== null && $esKilos ? $kilos + (float) ($r['peso'] ?? 0) * $n : $kilos;
            $m['presentar'] = [
                'precio' => $pr,
                'pvpr' => round(max($pvpr, $pr) + (float) ($r['pvpr'] ?? $r['precio'] ?? 0) * $n, 2),
                'motivo' => 'regalo',
                'kilos' => $kilosCon,
            ];
            $m['especial_fuera'] = $especial;
            $m['estado'] = 'regalo';
            if ($kilosCon && $esKilos) {
                $m['kg'] = $t('kg_regalo', ['%kg%' => $euros($pr / $kilosCon) . ($litros ? '/l' : '/kg'), '%kilos%' => self::numero($kilosCon)]);
            } elseif ($porUnidad($pr) !== '') {
                $m['kg'] = $t('kg_habitual', ['%ud%' => $porUnidad($pr)]);
            }
        } elseif ($agotado) {
            $m['presentar'] = ['precio' => $habitual, 'pvpr' => $pvpr, 'motivo' => 'agotado', 'kilos' => $kilos];
            $m['especial_fuera'] = true;
            $m['estado'] = 'agotado';
            if ($porUnidad($habitual) !== '') {
                $m['kg'] = $t('kg_habitual', ['%ud%' => $porUnidad($habitual)]);
            }
        }

        // 3. «Quedan N unidades en Liquidación» (sin la opción con regalo elegida: ahí no hay Special Price).
        if ($especial && !$regaloElegido && ($quedanEspecial !== null)) {
            $m['quedan'] = [
                'texto' => self::quedanTexto($quedanEspecial, $agotado, $etiquetaEspecial, $textos),
                'pocas' => $quedanEspecial > 0 && $quedanEspecial <= 5,
                'agotado' => $agotado,
                'etiqueta' => $etiquetaEspecial,
                'clase' => $claseEspecial,
            ];
            $m['tipo'] = 'precio';
        }

        // 4. El regalo: su tarjeta va en la fila de formatos (`tarjeta()`, montada por `ficha.js`); aquí,
        //    con la tarjeta elegida, lo que dice el bloque bajo el precio: la pastilla («Kilos gratis»),
        //    «Incluye 3 kg gratis (17,99 €), a 0 € en la cesta», hasta cuándo y cuántos regalos quedan.
        if ($r !== null) {
            $tarjeta = self::tarjeta($f, $r, $textos, $euros);
            $m['tarjeta'] = $tarjeta;
            if ($regaloElegido) {
                $n = max(1, (int) ($r['n'] ?? 1));
                $esKilos = ($r['clase'] ?? '') === 'kilos';
                $vale = round((float) ($r['precio'] ?? 0) * $n, 2);
                $queRegala = $esKilos
                    ? trim((string) ($r['corto'] ?? '')) ?: trim((string) ($r['formato'] ?? ''))
                    : trim((string) ($r['nombre'] ?? '')) . (trim((string) ($r['formato'] ?? '')) !== '' ? ' · ' . trim((string) $r['formato']) : '');
                $incluye = $t('regalo_incluye', ['%regalo%' => ($n > 1 ? $n . ' × ' : '') . $queRegala, '%precio%' => $euros($vale)]);
                if (($r['por'] ?? 'unidad') === 'pedido') {
                    $incluye .= ' · ' . $t('regalo_pedido');
                }
                $m['regalo'] = [
                    'elegida' => 1,
                    'pill' => $tarjeta['banda'],
                    'clase' => $tarjeta['clase'],
                    'incluye' => $incluye,
                    'fin' => self::fin((string) ($r['hasta'] ?? ''), $hoy, $textos),
                    'quedan' => isset($r['quedan']) && $r['quedan'] !== null ? $t('regalo_quedan', ['%n%' => (string) (int) $r['quedan']]) : '',
                    'imagen' => !$esKilos ? (string) ($r['imagen'] ?? '') : '',
                    'nombre' => trim((string) ($r['nombre'] ?? '')),
                ];
            }
            $m['tipo'] = 'regalo';
        }
        // 5. El cupón o la 2ª unidad.
        if ($promo !== null && !$regaloElegido) {
            $pct = (float) ($promo['pct'] ?? 0);
            $pctTxt = KpcElegibles::pct($pct);
            $entra = in_array($ipa, array_map('intval', (array) ($promo['ipas'] ?? [])), true);
            $para = self::paraTexto((array) ($promo['ipas'] ?? []), $f['hermanos'] ?? []);
            if (($promo['tipo'] ?? 'cupon') === 'segunda_unidad') {
                $quedan = $promo['quedan_por'][$ipa] ?? $promo['quedan'] ?? null;
                if ($entra) {
                    $pares = intdiv($cantidad, 2);
                    $ahorroUd = round($p * $pct / 100, 2);
                    $ahorro = round(max(1, $pares) * $ahorroUd, 2);
                    $conUds = $pares > 0 ? $cantidad : 2;
                    $total = round($p * $conUds - round($pares > 0 ? $pares : 1, 0) * $ahorroUd, 2);
                    // En húmedo, lo que sale cada lata; en pienso, el kg (como en las tarjetas).
                    $sale = '';
                    if ($unidades !== null && $envase !== '') {
                        $sale = $t('segunda_envase', ['%envase%' => $envase, '%precio%' => $euros(round($total / ($unidades * $conUds), 2))]);
                    } elseif ($kilos) {
                        $sale = $t($litros ? 'segunda_litro' : 'segunda_kilo', ['%precio%' => $euros(round($total / ($kilos * $conUds), 2))]);
                    }
                    $texto = $pares > 0
                        ? $t('segunda_puesta', ['%uds%' => (string) $cantidad, '%ahorro%' => $euros($ahorro), '%sale%' => $sale])
                        : $t('segunda_anima', ['%ahorro%' => $euros($ahorroUd), '%sale%' => $sale]);
                    $m['segunda'] = [
                        'pct_txt' => $pctTxt,
                        'titulo' => $t('segunda_titulo', ['%pct%' => $pctTxt]),
                        'texto' => $texto . ($quedan !== null ? $t('segunda_quedan', ['%n%' => (string) (int) $quedan]) : ''),
                        'puesta' => $pares > 0,
                        'pulsable' => $cantidad % 2 === 1 && ($quedan === null || (int) $quedan > 0),
                        'entra' => true,
                        'para' => '',
                    ];
                } elseif ($para !== '') {
                    $m['segunda'] = ['pct_txt' => $pctTxt, 'titulo' => '', 'texto' => '', 'puesta' => false, 'pulsable' => false, 'entra' => false,
                        'para' => $t('segunda_para', ['%pct%' => $pctTxt, '%formatos%' => $para])];
                }
                if ($m['segunda'] !== null) {
                    $m['tipo'] = 'segunda';
                    if ($m['estado'] === '' || $m['estado'] === 'precio') {
                        $m['estado'] = 'segunda';
                    }
                }
            } else {
                $aplicado = !empty($promo['aplicado']) && !$robot;
                $autoWeb = !empty($promo['auto_web']);
                $conCupon = round($p * (1 - $pct / 100), 2);
                $quedan = $promo['quedan_por'][$ipa] ?? $promo['quedan'] ?? null;
                if ($entra) {
                    if ($aplicado && !$autoWeb && !$especial) {
                        // El precio grande pasa al precio con cupón (solo con la cookie puesta por el cliente).
                        $m['presentar'] = ['precio' => $conCupon, 'pvpr' => $pvpr, 'motivo' => 'cupon', 'kilos' => $kilos];
                        $m['estado'] = 'cupon';
                        if ($porUnidad($conCupon) !== '') {
                            $m['kg'] = $t('kg_cupon', ['%ud%' => $porUnidad($conCupon)]);
                        }
                    } elseif ($aplicado) {
                        $m['estado'] = 'cupon-auto';
                    } else {
                        $m['estado'] = $m['estado'] === '' || $m['estado'] === 'precio' ? 'cupon-sin' : $m['estado'];
                    }
                    $sub = $aplicado
                        ? ($autoWeb || $especial
                            ? $t('cupon_auto', ['%precio%' => $euros($conCupon)])
                            : $t('cupon_ahorras', ['%ahorro%' => $euros(round($p - $conCupon, 2))]))
                        : $t('cupon_pagas', ['%precio%' => $euros($conCupon)]);
                    if ($quedan !== null) {
                        $sub .= $t('cupon_quedan', ['%n%' => (string) (int) $quedan]);
                    }
                    $m['cupon'] = [
                        'codigo' => (string) ($promo['codigo'] ?? ''),
                        'pct_txt' => $pctTxt,
                        'aplicado' => $aplicado,
                        'auto' => $autoWeb,
                        'entra' => true,
                        'titulo' => $aplicado ? $t('cupon_aplicado', ['%pct%' => $pctTxt]) : $t('cupon_aplica', ['%pct%' => $pctTxt]),
                        'sub' => $sub,
                        'precio_con' => $conCupon,
                        'url' => (string) ($promo['url'] ?? ''),
                        'para' => '',
                    ];
                } elseif ($para !== '') {
                    $m['cupon'] = ['codigo' => (string) ($promo['codigo'] ?? ''), 'pct_txt' => $pctTxt, 'aplicado' => $aplicado, 'auto' => $autoWeb, 'entra' => false,
                        'titulo' => '', 'sub' => '', 'precio_con' => 0.0, 'url' => '', 'para' => $t('cupon_para', ['%pct%' => $pctTxt, '%formatos%' => $para])];
                }
                if ($m['cupon'] !== null) {
                    $m['tipo'] = 'cupon';
                }
            }
        }

        // El estado del bloque (`data-kpc-estado`): lo que se presenta manda; si no, lo que hay que elegir o pulsar.
        if ($m['estado'] === '' || $m['estado'] === 'precio') {
            $m['estado'] = $m['tarjeta'] !== null ? 'regalo-sin' : ($m['segunda'] !== null ? 'segunda' : ($m['quedan']['texto'] !== '' ? 'precio' : $m['estado']));
        }
        $m['hay'] = $m['cupon'] !== null || $m['segunda'] !== null || $m['regalo'] !== null || $m['quedan']['texto'] !== '' || $m['kg'] !== '' || $m['aviso'] !== '' || $m['tarjeta'] !== null;

        return $m;
    }

    /**
     * La tarjeta «12 kg + 3 kg gratis» de un formato disparador: lo que `ficha.js` pinta en la fila de
     * formatos del tema (nombre corto, banda «Kilos gratis» o «Con regalo», el precio de siempre del
     * formato sin su Special Price, el PVPR de los dos tachado y el €/kg con los kilos del regalo).
     *
     * @param array $f el formato (ver `modelo()`)
     * @param array $r su regalo (`KpcRegalo::paraFicha()['por'][ipa]`)
     * @param callable(float): string $euros
     */
    public static function tarjeta(array $f, array $r, array $textos, callable $euros): array
    {
        $t = static fn (string $clave): string => (string) ($textos[$clave] ?? self::TEXTOS[$clave] ?? $clave);
        $n = max(1, (int) ($r['n'] ?? 1));
        $esKilos = ($r['clase'] ?? '') === 'kilos';
        $p = round((float) $f['precio'], 2);
        $pvpr = round((float) $f['pvpr'], 2);
        $habitual = isset($f['habitual']) && $f['habitual'] !== null ? round((float) $f['habitual'], 2) : null;
        $pr = $habitual !== null && $habitual > $p + 0.01 ? $habitual : $p;
        $pvprCon = round(max($pvpr, $pr) + (float) ($r['pvpr'] ?? $r['precio'] ?? 0) * $n, 2);
        $kilos = !empty($f['gramos']) ? (float) $f['gramos'] / 1000 : null;
        $kilosCon = $kilos !== null && $esKilos ? $kilos + (float) ($r['peso'] ?? 0) * $n : $kilos;
        $litros = !empty($f['litros']);
        $unidades = !empty($f['unidades']) && (int) $f['unidades'] > 1 ? (int) $f['unidades'] : null;
        $envase = (string) ($f['envase'] ?? '');
        $kg = '';
        if ($unidades !== null && $envase !== '') {
            $kg = $euros($pr / $unidades) . '/' . $envase;
        } elseif ($kilosCon) {
            $kg = $euros($pr / $kilosCon) . ($litros ? '/l' : '/kg');
        }
        $corto = trim((string) ($r['corto'] ?? '')) !== '' ? (string) $r['corto'] : $t('regalo_corto');
        $corto = ($n > 1 ? $n . ' x ' : '') . $corto;

        return [
            'ipa' => (int) $f['ipa'],
            'attr' => (int) ($f['id_attribute'] ?? 0),
            'grupo' => (int) ($f['grupo'] ?? 0),
            'nombre' => $f['corto'] . ' + ' . $corto,
            'titulo' => $f['nombre'] . ' + ' . $corto,
            'banda' => $esKilos ? $t('regalo_banda_kilos') : $t('regalo_banda_otro'),
            'clase' => $esKilos ? 'kilos' : 'regalo',
            'precio' => $euros($pr),
            'pvpr' => $pvprCon > $pr + 0.005 ? $euros($pvprCon) : '',
            'kg' => $kg !== '' ? '(' . $kg . ')' : '',
            // La franja violeta de la tarjeta, en línea como el chip del servidor (`ficha-chip.tpl`):
            // el tema pinta la franja de cada tarjeta solo con su estilo en línea.
            'estilo' => 'background-color:' . KpcFlags::COLORES['kpc-regalo'] . ';color:#ffffff',
        ];
    }

    /** El modelo vacío: la ficha es la de Julián tal cual. */
    public static function vacio(): array
    {
        return [
            'tipo' => '',
            'estado' => '',
            'presentar' => null,
            'especial_fuera' => false,
            'kg' => '',
            'quedan' => ['texto' => '', 'pocas' => false, 'agotado' => false, 'etiqueta' => '', 'clase' => ''],
            'regalo' => null,
            'regalo_elegido' => 0,
            'tarjeta' => null,
            'datos' => '{}',
            'aviso' => '',
            'cupon' => null,
            'segunda' => null,
            'campana' => null,
            'hay' => false,
        ];
    }

    /**
     * Lo que dice la ficha de cuántas unidades quedan con la promoción: «Quedan 12 unidades en
     * Liquidación», «¡Últimas 3 unidades…!» (cinco o menos), «¡Última unidad…!» y, con 0, «Se han
     * agotado las unidades en …: a precio habitual». Sin cupo, nada.
     */
    public static function quedanTexto(?int $quedan, bool $sinCupo, string $promo, array $textos): string
    {
        $t = static fn (string $clave): string => (string) ($textos[$clave] ?? self::TEXTOS[$clave] ?? $clave);
        if ($sinCupo) {
            return str_replace('%promo%', $promo, $t('sin_cupo'));
        }
        if ($quedan === null || $quedan <= 0) {
            return '';
        }
        if ($quedan === 1) {
            return str_replace('%promo%', $promo, $t('quedan_una'));
        }

        return str_replace(['%n%', '%promo%'], [(string) $quedan, $promo], $t($quedan <= 5 ? 'quedan_ultimas' : 'quedan'));
    }

    private const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
        'septiembre', 'octubre', 'noviembre', 'diciembre'];

    /**
     * Cuánto queda de promoción: «Termina hoy», «Termina mañana», «Termina en 3 días» la última
     * semana y «Promoción hasta el 30 de septiembre» el resto del tiempo. Vacío sin fecha o pasada.
     */
    public static function fin(string $expira, int $hoy, array $textos): string
    {
        $t = static fn (string $clave): string => (string) ($textos[$clave] ?? self::TEXTOS[$clave] ?? $clave);
        if ($expira === '' || strpos($expira, '0000-00-00') === 0) {
            return '';
        }
        $x = strtotime($expira);
        if ($x === false || $x < $hoy) {
            return '';
        }
        $dias = (int) floor((strtotime(date('Y-m-d', $x)) - strtotime(date('Y-m-d', $hoy))) / 86400);
        if ($dias === 0) {
            return $t('fin_hoy');
        }
        if ($dias === 1) {
            return $t('fin_manana');
        }
        if ($dias <= 7) {
            return str_replace('%d', (string) $dias, $t('fin_dias'));
        }

        return str_replace('%fecha%', (int) date('j', $x) . ' de ' . (self::MESES[(int) date('n', $x) - 1] ?? ''), $t('promo_hasta'));
    }

    /**
     * ¿Es un robot? Ningún cupón se aplica ni se escribe en la cookie para él, y el precio que ve es
     * el del feed (norma de Google Merchant: el robot entra sin cupón).
     */
    public static function esRobot(string $userAgent): bool
    {
        return $userAgent !== '' && (bool) preg_match(
            '/googlebot|storebot-google|adsbot-google|google-inspectiontool|mediapartners-google|google-read-aloud|apis-google|bingbot|bingpreview|yandex|baiduspider|duckduckbot|applebot|petalbot|facebookexternalhit|semrushbot|ahrefsbot|\bbot\b|crawler|spider|slurp/i',
            $userAgent
        );
    }

    /** «3 kg, 12 kg»: los formatos a los que llega una promoción, cortos. */
    public static function paraTexto(array $ipas, array $hermanos): string
    {
        $nombres = [];
        foreach ($ipas as $ipa) {
            $h = $hermanos[(int) $ipa] ?? null;
            if ($h !== null && trim((string) $h) !== '') {
                $nombres[] = trim((string) $h);
            }
        }

        return implode(', ', array_unique($nombres));
    }

    /** «12» o «2,5». */
    public static function numero(float $n): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.'));
    }

    /** El tipo de promoción de un `type` de etiqueta, para la capa de campaña. */
    public static function tipoDeChip(string $type): string
    {
        return ['kpc-cupon' => 'cupon', 'kpc-segunda' => 'segunda_unidad', 'kpc-especial' => 'special_price',
            'kpc-liquidacion' => 'special_price', 'kpc-outlet' => 'special_price', 'kpc-bajada' => 'special_price', 'kpy-special-price' => 'special_price'][$type] ?? '';
    }

    /** Un color `#rrggbb` o, si no lo es, el de respaldo. */
    public static function color($valor, string $siNo): string
    {
        return is_string($valor) && preg_match('/^#[0-9A-Fa-f]{6}$/', $valor) ? strtoupper($valor) : $siNo;
    }

    /**
     * La capa de campaña sobre el modelo: nombres y colores de campaña en el cupón, la 2ª unidad y
     * el Special Price, y las variables CSS que la plantilla pone en el bloque (`--kpc-cup-bg`…).
     *
     * @param array{nombre: string, clave: string, piel: string, tipos: array<string, array{label: string, background: string, color: string}>} $c
     */
    public static function conCampana(array $m, array $c): array
    {
        $tipos = is_array($c['tipos'] ?? null) ? $c['tipos'] : [];
        $vars = [];
        $sp = $tipos['special_price'] ?? null;
        if (is_array($sp) && trim((string) ($sp['label'] ?? '')) !== '') {
            $bg = self::color($sp['background'] ?? '', '#FFEAB7');
            $fg = self::color($sp['color'] ?? '', '#B8322A');
            $vars[] = '--kpc-esp-bg:' . $bg . ';--kpc-esp-fg:' . $fg;
            if ($m['quedan']['texto'] !== '' && $m['quedan']['clase'] === 'especial') {
                $m['quedan']['texto'] = str_replace($m['quedan']['etiqueta'], trim((string) $sp['label']), $m['quedan']['texto']);
                $m['quedan']['etiqueta'] = trim((string) $sp['label']);
            }
        }
        foreach (['cupon' => 'cup', 'segunda_unidad' => 'seg'] as $tipo => $prefijo) {
            $e = $tipos[$tipo] ?? null;
            if (!is_array($e) || trim((string) ($e['label'] ?? '')) === '') {
                continue;
            }
            $label = trim((string) $e['label']);
            $bg = self::color($e['background'] ?? '', '#111111');
            $fg = self::color($e['color'] ?? '', '#F2C965');
            $oscuro = self::esOscuro($bg);
            $vars[] = '--kpc-' . $prefijo . '-bg:' . $bg . ';--kpc-' . $prefijo . '-borde:' . $fg . ';--kpc-' . $prefijo . '-fg:' . $fg
                . ';--kpc-' . $prefijo . '-sub:' . ($oscuro ? '#D5DBDE' : '#4b5a61');
            if ($tipo === 'cupon' && $m['cupon'] !== null && $m['cupon']['entra']) {
                $m['cupon']['titulo'] = $m['cupon']['aplicado'] ? $label . ' · aplicado' : 'Aplica tu ' . $label;
            }
            if ($tipo === 'segunda_unidad' && $m['segunda'] !== null && $m['segunda']['entra']) {
                $m['segunda']['titulo'] = $label;
            }
        }
        $m['campana'] = ['nombre' => (string) ($c['nombre'] ?? ''), 'clave' => (string) ($c['clave'] ?? ''), 'piel' => (string) ($c['piel'] ?? ''), 'estilo' => implode(';', $vars), 'tipos' => $tipos];

        return $m;
    }

    /** ¿Es un color oscuro (para poner texto claro encima)? */
    public static function esOscuro(string $hex): bool
    {
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) < 128;
    }

    /** Los textos de la ficha, con su valor de siempre (los traduce `textos()` con el módulo). */
    public const TEXTOS = [
        'especial' => 'Special Price',
        'cupon_aplica' => 'Aplica tu cupón −%pct% %',
        'cupon_aplicado' => 'Cupón −%pct% % aplicado',
        'cupon_pagas' => 'Aplícalo y págalo a %precio%',
        'cupon_ahorras' => 'Ahorras %ahorro% · se mantiene en la cesta',
        'cupon_auto' => 'En la cesta lo pagas a %precio%',
        'cupon_para' => 'El cupón −%pct% % es para: %formatos%',
        'cupon_quedan' => ' · quedan %n% ud. con cupón',
        'quitar' => 'Quitar',
        'aplicando' => 'Aplicando…',
        'segunda_titulo' => '2ª unidad al −%pct% %',
        'segunda_anima' => 'Añade 1 ud. más: te ahorras %ahorro%%sale%',
        'segunda_puesta' => 'Llevas %uds% ud.: te ahorras %ahorro%%sale%',
        'segunda_kilo' => ' y el kg te sale a %precio%',
        'segunda_litro' => ' y el litro te sale a %precio%',
        'segunda_envase' => ' y cada %envase% te sale a %precio%',
        'segunda_para' => 'La 2ª unidad al −%pct% % es para: %formatos%',
        'segunda_quedan' => ' · quedan %n% ud. rebajadas',
        'habitual_ahorro' => 'Precio habitual %habitual% · ahorras %ahorro% más',
        'promo_hasta' => 'Promoción hasta el %fecha%',
        'fin_hoy' => 'Termina hoy',
        'fin_manana' => 'Termina mañana',
        'fin_dias' => 'Termina en %d días',
        'regalo_incluye' => 'Incluye %regalo% (%precio%), a 0 € en la cesta',
        'regalo_unidad' => 'uno por cada unidad',
        'regalo_pedido' => 'uno por pedido',
        'regalo_corto' => 'Regalo',
        'regalo_banda_kilos' => 'Kilos gratis',
        'regalo_banda_otro' => 'Con regalo',
        'regalo_quedan' => 'quedan %n% regalos',
        'regalo_agotado' => 'Se han agotado los regalos de esta promoción',
        'quedan' => 'Quedan %n% unidades en %promo%',
        'quedan_ultimas' => '¡Últimas %n% unidades en %promo%!',
        'quedan_una' => '¡Última unidad en %promo%!',
        'sin_cupo' => 'Se han agotado las unidades en %promo%: a precio habitual',
        'kg_regalo' => '%kg% con los %kilos% kg',
        'kg_cupon' => '%ud% con el cupón',
        'kg_habitual' => '%ud% a precio habitual',
        'modal_titulo' => 'Lo que acabas de añadir lleva:',
    ];

    // ------------------------------------------------------------------------------------------
    // Los datos (PrestaShop)

    /** Los textos traducidos con el módulo. */
    public function textos(): array
    {
        if ($this->textos === null) {
            $this->textos = [];
            foreach (self::TEXTOS as $clave => $texto) {
                $this->textos[$clave] = $this->modulo->t($texto);
            }
        }

        return $this->textos;
    }

    /** «12,99 €», como lo escribe la tienda. */
    public function euros(): callable
    {
        $contexto = Context::getContext();
        $locale = $contexto->getCurrentLocale();
        $moneda = $contexto->currency ? (string) $contexto->currency->iso_code : 'EUR';

        return static fn (float $x): string => $locale ? $locale->formatPrice($x, $moneda) : number_format($x, 2, ',', '.') . ' €';
    }

    private static function poner($p, string $clave, $valor): void
    {
        if (!is_object($p) || !method_exists($p, 'offsetExists')) {
            return;
        }
        if ($p->offsetExists($clave)) {
            $p[$clave] = $valor;
        } elseif (method_exists($p, 'appendArray')) {
            $p->appendArray([$clave => $valor]);
        }
    }

    /**
     * La capa de campaña de este producto (hook de consulta `actionKpyCouponsCampana`, lo contesta
     * `kpypromolanding` si hay una campaña viva que incluya el producto). Null sin campaña.
     *
     * @param string[] $tipos los tipos de promoción que hay en pantalla; vacío = los del modelo
     */
    private function campana(int $ipa, ?array $m, array $tipos = []): ?array
    {
        if ($tipos === [] && $m !== null) {
            if ($m['cupon'] !== null) {
                $tipos[] = 'cupon';
            }
            if ($m['segunda'] !== null) {
                $tipos[] = 'segunda_unidad';
            }
            if ($m['quedan']['texto'] !== '') {
                $tipos[] = 'special_price';
            }
        }
        if ($tipos === []) {
            return null;
        }
        sort($tipos);
        $firma = implode(',', $tipos);
        if (array_key_exists($firma, $this->campanas)) {
            return $this->campanas[$firma];
        }
        $salida = null;
        try {
            $respuestas = Hook::exec('actionKpyCouponsCampana', [
                'id_product' => $this->idProducto,
                'id_product_attribute' => $ipa,
                'tipos' => $tipos,
            ], null, true);
            foreach ((array) ($respuestas ?: []) as $r) {
                if (is_array($r) && !empty($r['tipos']) && is_array($r['tipos'])) {
                    $salida = $r;
                    break;
                }
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al preguntar por la campaña (actionKpyCouponsCampana): ' . $e->getMessage(), 2, null, 'Product', $this->idProducto, true);
        }

        return $this->campanas[$firma] = $salida;
    }

    /** Lee de la base de datos todo lo que la ficha necesita de este producto, una vez. */
    private function cargar(): void
    {
        $this->formatos = $this->leerFormatos();
        $enFicha = array_values(array_filter(array_keys($this->formatos), static fn ($ipa) => (int) $ipa > 0));
        $enFicha = $enFicha === [] ? [0] : $enFicha;
        try {
            $this->regalo = KpcRegalo::paraFicha($this->idTienda, $this->idProducto, $enFicha);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, regalo en la ficha #' . $this->idProducto . ': ' . $e->getMessage(), 2, null, 'Product', $this->idProducto, true);
        }
        try {
            $this->precios = $this->preciosDeFicha($enFicha);
            $this->promo = $this->promoDeCesta($enFicha);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, promoción en la ficha #' . $this->idProducto . ': ' . $e->getMessage(), 2, null, 'Product', $this->idProducto, true);
        }
    }

    /**
     * Los formatos del producto (o el producto solo, con `ipa` 0), con su nombre corto, su peso, su
     * precio de hoy, su PVPR y su Special Price en vigor (si la tabla de Julián existe).
     */
    private function leerFormatos(): array
    {
        $p = _DB_PREFIX_;
        $idLang = (int) Context::getContext()->language->id;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $apagados = KpcAlcance::hayFormatosApagados()
            ? " AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute k WHERE k.id_product_attribute = pa.id_product_attribute AND k.active = 0)"
            : '';
        $filas = $db->executeS(
            "SELECT pa.id_product_attribute AS ipa, pas.default_on, pac.id_attribute, a.id_attribute_group AS id_grupo, al.name AS nombre, pa.weight
             FROM {$p}product_attribute pa
             INNER JOIN {$p}product_attribute_shop pas ON pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = " . $this->idTienda . "
             INNER JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             INNER JOIN {$p}attribute a ON a.id_attribute = pac.id_attribute
             INNER JOIN {$p}attribute_lang al ON al.id_attribute = a.id_attribute AND al.id_lang = " . $idLang . '
             WHERE pa.id_product = ' . $this->idProducto . $apagados . '
             ORDER BY a.position, pa.id_product_attribute'
        ) ?: [];

        $formatos = [];
        $porAtributo = [];
        if ($filas === []) {
            $peso = (float) $db->getValue('SELECT weight FROM ' . $p . 'product WHERE id_product = ' . $this->idProducto);
            $nombre = (string) $db->getValue('SELECT name FROM ' . $p . 'product_lang WHERE id_product = ' . $this->idProducto . ' AND id_lang = ' . $idLang . ' AND id_shop = ' . $this->idTienda);
            $formatos[0] = ['ipa' => 0, 'id_attribute' => 0, 'grupo' => 0, 'nombre' => $nombre, 'peso' => $peso, 'defecto' => true];
        } else {
            foreach ($filas as $f) {
                $ipa = (int) $f['ipa'];
                $idAtributo = (int) $f['id_attribute'];
                if (!isset($formatos[$ipa])) {
                    $formatos[$ipa] = ['ipa' => $ipa, 'id_attribute' => $idAtributo, 'grupo' => (int) $f['id_grupo'], 'nombre' => (string) $f['nombre'], 'peso' => (float) $f['weight'], 'defecto' => (bool) $f['default_on']];
                }
                // Un atributo que cae en más de un formato (dos grupos de atributos): sin chip.
                if (array_key_exists($idAtributo, $porAtributo) && $porAtributo[$idAtributo] !== $ipa) {
                    $porAtributo[$idAtributo] = null;
                } elseif (!array_key_exists($idAtributo, $porAtributo)) {
                    $porAtributo[$idAtributo] = $ipa;
                }
            }
        }
        $this->porAtributo = $porAtributo;

        $especiales = [];
        if (KpcCupon::hayTablaEspeciales()) {
            foreach ($db->executeS(
                'SELECT id_product_attribute AS ipa, old_discount, expire FROM ' . $p . 'kpy_special_price
                 WHERE id_product = ' . $this->idProducto . ' AND id_shop = ' . $this->idTienda . ' AND NOW() BETWEEN date_from AND expire
                 ORDER BY date_from'
            ) ?: [] as $f) {
                $especiales[(int) $f['ipa']] = ['old_discount' => (float) $f['old_discount'], 'expira' => (string) $f['expire']];
            }
        }
        $hermanos = [];
        foreach ($formatos as $ipa => $f) {
            $leido = KpcEnvase::leer($f['nombre'], $f['peso']);
            $precio = round((float) Product::getPriceStatic($this->idProducto, true, $ipa ?: null, 6), 2);
            $pvpr = round((float) Product::getPriceStatic($this->idProducto, true, $ipa ?: null, 6, null, false, false), 2);
            $especial = $especiales[$ipa] ?? null;
            $habitual = null;
            if ($especial !== null) {
                $h = round($pvpr * (1 - $especial['old_discount']), 2);
                if ($h > $precio + 0.01 && $h <= $pvpr + 0.01) {
                    $habitual = $h;
                }
            }
            $formatos[$ipa] += [
                'corto' => $leido['corto'],
                'gramos' => $leido['gramos'],
                'unidades' => $leido['unidades'],
                'envase' => $leido['envase'],
                'litros' => $leido['litros'],
                'precio' => $precio,
                'pvpr' => $pvpr,
                'especial' => $especial,
                'habitual' => $habitual,
            ];
            $hermanos[$ipa] = $leido['corto'];
        }
        foreach ($formatos as $ipa => $f) {
            $formatos[$ipa]['hermanos'] = $hermanos;
        }

        return $formatos;
    }

    /**
     * El Special Price de cada formato de la ficha cuando es de una promoción de esta pantalla con
     * nombre propio o con cupo: cómo se llama («Special Price», «Liquidación», «Bajada de precio»),
     * su color y, con cupo y si la promoción lo dice, cuántas unidades quedan (sin contar lo que ya
     * tenga reservado la cesta de la visita).
     *
     * @param int[] $enFicha
     *
     * @return array<int, array{etiqueta: string, clase: string, quedan: ?int, id_kpy_coupon: int}>
     */
    private function preciosDeFicha(array $enFicha): array
    {
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT cp.id_product_attribute AS ipa, c.id_kpy_coupon, c.cupo, c.cupo_lista, c.cupo_ficha, c.etiqueta FROM {$p}kpy_coupon_precio cp
             INNER JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = cp.id_kpy_coupon
             WHERE cp.id_product = " . $this->idProducto . " AND (c.cupo > 0 OR c.cupo_lista = 1 OR c.etiqueta <> '')
               AND c.activo = 1 AND c.tipo = 'special_price' AND c.id_shop = " . $this->idTienda . '
               AND (c.fecha_desde IS NULL OR c.fecha_desde = "0000-00-00 00:00:00" OR c.fecha_desde <= NOW())
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND ' . KpcCupo::sqlVivo('c') . '
             ORDER BY c.id_kpy_coupon'
        ) ?: [] as $f) {
            $ipa = (int) $f['ipa'];
            if (isset($salida[$ipa]) || !in_array($ipa, $enFicha, true)) {
                continue;
            }
            $salida[$ipa] = [
                'etiqueta' => $this->modulo->t(KpcCupo::nombreEtiqueta((string) $f['etiqueta'])),
                'clase' => KpcCupo::claseEtiqueta((string) $f['etiqueta']),
                'quedan' => KpcCupo::tieneCupo($f) && (int) $f['cupo_ficha'] ? KpcCupo::quedanPara((int) $f['id_kpy_coupon'], $this->idCart, $ipa) : null,
                'id_kpy_coupon' => (int) $f['id_kpy_coupon'],
            ];
        }

        return $salida;
    }

    /**
     * El cupón o la 2ª unidad del producto, para la ficha: la que manda de las que llegan a alguno
     * de sus formatos. Con un cupón, si está puesto para esta visita (la cookie) o entra solo (por
     * la web o por el canal); si entra solo, se deja guardado para que la cesta lo recoja. Para un
     * robot nunca se aplica ni se escribe nada.
     *
     * «Para todos» (`auto_web`), como lo define Julián (23/10/2026): el cupón lo ve cualquiera en la
     * ficha de **sus** productos, venga de donde venga la visita (aunque «se enseña» esté desmarcado);
     * y nunca en un producto que no entra (de ahí el `$suyos` de arriba). Y un cupón que solo se ve
     * desde un canal (`ver_canal`, 3.1.0) solo lo ve quien llega de él (`KpcElegibles::seVe`); un
     * robot, nunca.
     *
     * @param int[] $enFicha
     */
    private function promoDeCesta(array $enFicha): ?array
    {
        $suyos = KpcReparto::deProducto($this->idTienda, $this->idProducto);
        if ($suyos === []) {
            return null;
        }
        foreach (KpcCupon::enVigor($this->idTienda) as $cupon) {
            if (KpcElegibles::esPrecio($cupon) || KpcElegibles::esRegalo($cupon)) {
                continue;
            }
            $ipas = array_values(array_intersect($enFicha, $suyos[(int) $cupon['id_kpy_coupon']] ?? []));
            // Un cupón por canal que esta visita ya lleva puesto (llegó del canal y luego vino de otro sitio, o
            // entró por el enlace `?kpc=`) se le sigue enseñando: la cesta lo descuenta y la ficha no puede
            // decir otra cosa (revisión del 26/10/2026).
            $puesto = !$this->robot && (string) ($cupon['ver_canal'] ?? '') !== ''
                && mb_strtoupper($this->modulo->elegido()) === mb_strtoupper((string) $cupon['codigo']);
            if ($ipas === [] || (!$puesto && !KpcElegibles::seVe($cupon, $this->robot ? '' : Kpycoupons::canalDeLaVisita()))) {
                continue;
            }
            $conCupo = KpcCupo::tieneCupo($cupon) && (int) ($cupon['cupo_ficha'] ?? 1);
            $quedan = $conCupo ? KpcCupo::quedanPara((int) $cupon['id_kpy_coupon'], $this->idCart) : null;
            $quedanPor = [];
            if ($conCupo && !empty($cupon['cupo_lista'])) {
                foreach ($ipas as $ipa) {
                    $quedanPor[(int) $ipa] = KpcCupo::quedanPara((int) $cupon['id_kpy_coupon'], $this->idCart, (int) $ipa);
                }
            }
            if (($cupon['tipo'] ?? 'cupon') === 'segunda_unidad') {
                return [
                    'tipo' => 'segunda_unidad',
                    'id_kpy_coupon' => (int) $cupon['id_kpy_coupon'],
                    'codigo' => (string) $cupon['codigo'],
                    'pct' => (float) $cupon['porcentaje'],
                    'ipas' => $ipas,
                    'aplicado' => true,
                    'auto_web' => false,
                    'quedan' => $quedan,
                    'quedan_por' => $quedanPor,
                    'url' => '',
                ];
            }
            $aplicado = false;
            if (!$this->robot) {
                $elegido = $this->modulo->elegido();
                $aplicado = $elegido !== ''
                    ? mb_strtoupper($elegido) === mb_strtoupper((string) $cupon['codigo'])
                    : $this->modulo->entraSolo($cupon);
                if ($aplicado && $elegido === '') {
                    $this->modulo->guardar((string) $cupon['codigo']);
                }
            }

            return [
                'tipo' => 'cupon',
                'id_kpy_coupon' => (int) $cupon['id_kpy_coupon'],
                'codigo' => (string) $cupon['codigo'],
                'pct' => (float) $cupon['porcentaje'],
                'ipas' => $ipas,
                'aplicado' => $aplicado,
                'auto_web' => !empty($cupon['auto_web']),
                'quedan' => $quedan,
                'quedan_por' => $quedanPor,
                'url' => Context::getContext()->link->getModuleLink($this->modulo->name, 'aplicar', [], true),
            ];
        }

        return null;
    }
}
