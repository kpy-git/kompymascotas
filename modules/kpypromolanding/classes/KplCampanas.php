<?php
/**
 * Las campañas (Black Friday, Navidad…): una capa por encima de las promociones de siempre, con
 * fecha de inicio y de fin, que no toca nada de lo que hay debajo.
 *
 * Mientras una campaña está viva (entre sus fechas):
 *   - su dirección (`/blackfriday`) enseña la landing con su piel (negra), sus textos y su lema
 *     («stock limitado · hasta agotar existencias»), y `/promociones` manda allí;
 *   - las etiquetas de las promociones de la campaña cambian de nombre y color en toda la tienda
 *     («Special Price» → «Black Friday −15 %», «Código −10 %» → «Cupón Black −10 %»), sin escribir
 *     nada en las tablas de etiquetas: se cambian al pintarlas (`actionProductFlagsModifier`);
 *   - una cinta arriba de todas las páginas lleva a la campaña.
 * Antes de empezar, su dirección dice «empieza el…» (con el boletín y las promociones de ahora);
 * después, «ha terminado» (y las promociones de ahora). Todo se decide por la hora de cada visita:
 * no hay nada que arrancar ni que parar, y al terminar no queda nada que limpiar.
 *
 * Qué promociones entran: las marcadas con la campaña en el paso 6 (`kpy_promo_promo.campanas`);
 * si ninguna lo está, todas las vivas.
 */
class KplCampanas
{
    public const TABLA = 'kpy_promo_campana';
    /** Las claves de campaña (`blackfriday|navidad`) para las rutas, sin consultar la tabla en cada visita. */
    public const CONF_CLAVES = 'KPL_CAMPANAS';
    public const PIELES = ['negra', 'clara'];
    public const TIPOS_ETIQUETA = ['special_price', 'cupon', 'segunda_unidad', 'kilos_gratis'];

    /** @var array<int, array>|null */
    private static ?array $todas = null;

    /** La paleta de una campaña: fondo, acento (etiquetas, píldoras, acentos) y acento secundario (cupón, 2ª unidad). */
    public const COLORES = ['fondo', 'acento', 'acento2'];

    /** La paleta de cada piel cuando la campaña no trae la suya: la de Black Friday y la de la tienda. */
    private const PALETA_PIEL = [
        'negra' => ['fondo' => '#111111', 'acento' => '#F2C965', 'acento2' => '#FF6A5C'],
        'clara' => ['fondo' => '#FFF5D8', 'acento' => '#FF5E4F', 'acento2' => '#8669B8'],
    ];

    /**
     * Las etiquetas por defecto de una campaña (nombre con `{pct}`, fondo y texto), a juego con su
     * paleta: el nombre largo en el descuento directo («Black Friday −15 %») y el corto en las demás
     * («Cupón Black −10 %»). En la piel negra, fondo de la campaña y texto del acento; en la clara,
     * llenas del acento, con el texto claro u oscuro según el color.
     */
    public static function etiquetasPorDefecto(string $nombre = 'Black Friday', string $corto = 'Black', ?array $colores = null, string $piel = 'negra'): array
    {
        $p = self::paletaLimpia($colores ?? [], $piel);
        if ($piel === 'negra') {
            $principal = ['fondo' => $p['fondo'], 'texto' => $p['acento']];
            $secundaria = ['fondo' => $p['fondo'], 'texto' => $p['acento2']];
        } else {
            $principal = ['fondo' => $p['acento'], 'texto' => self::oscuro($p['acento']) ? '#FFFFFF' : '#1F1F1F'];
            $secundaria = ['fondo' => $p['acento2'], 'texto' => self::oscuro($p['acento2']) ? '#FFFFFF' : '#1F1F1F'];
        }

        return [
            'special_price' => ['nombre' => $nombre . ' −{pct} %'] + $principal,
            'cupon' => ['nombre' => 'Cupón ' . $corto . ' −{pct} %'] + $secundaria,
            'segunda_unidad' => ['nombre' => '2ª ud. ' . $corto . ' −{pct} %'] + $secundaria,
            'kilos_gratis' => ['nombre' => 'kg gratis · ' . $corto] + $principal,
        ];
    }

    /**
     * Las campañas predefinidas, por clave. Se crean las que falten (al instalar, al actualizar y
     * desde la pantalla) con sus fechas de este año (o del que viene, si ya pasaron), sus textos y
     * su paleta; después se cambia lo que se quiera. Cada paleta va a juego en toda la tienda
     * (portada, cinta, etiquetas, ficha, cesta) y no se parece a la anterior:
     *   - Black Friday: negro, amarillo Kompy y coral. La de siempre.
     *   - Cyber Monday: azul noche, turquesa eléctrico y magenta. Lo «cyber»: oscuro, pero no negro.
     *   - Reyes Magos: marfil, púrpura real y oro. Fiesta y regalo; clara, para descansar del negro.
     * Si dos coinciden en fecha (el Cyber Monday cae en el último día del Black Friday), manda la
     * que empezó más tarde.
     */
    public static function predefinidas(): array
    {
        $hoy = date('Y-m-d');
        $anio = (int) date('Y');
        // Black Friday: el viernes después del cuarto jueves de noviembre; Cyber Monday: el lunes siguiente.
        $anioBf = $hoy > $anio . '-12-01' ? $anio + 1 : $anio;
        $lunes = date('Y-m-d', strtotime('fourth thursday of november ' . $anioBf . ' +4 days'));
        $martes = date('Y-m-d', strtotime($lunes . ' +1 day'));
        // Reyes cruza el año: del 26 de diciembre a la noche del 5 de enero.
        $anioReyes = $hoy > $anio . '-01-05' ? $anio : $anio - 1;
        $negra = self::PALETA_PIEL['negra'];
        $cyber = ['fondo' => '#0E1430', 'acento' => '#3DE1D2', 'acento2' => '#FF5FB4'];
        $reyes = ['fondo' => '#FFF8EC', 'acento' => '#6A3FB5', 'acento2' => '#D9A82E'];

        return [
            'blackfriday' => [
                'clave' => 'blackfriday', 'nombre' => 'Black Friday', 'activa' => 1,
                'desde' => $anioBf . '-11-01 00:00:00', 'hasta' => $anioBf . '-11-30 23:59:59',
                'piel' => 'negra', 'colores' => $negra,
                'titulo' => 'Black Friday en Kompy',
                'texto' => 'Las mejores marcas al mejor precio del año. Cuando se acaba, se acaba.',
                'lema' => 'Stock limitado',
                'texto_antes' => 'Apúntate al boletín y entérate de todo antes que nadie. Mientras tanto, estas son las promociones de ahora.',
                'texto_despues' => 'Se acabó por este año. Pero las promociones de ahora siguen aquí.',
                'cinta' => 1, 'cinta_texto' => 'Black Friday · ofertas por tiempo limitado', 'redirigir' => 1, 'sin_carrusel' => 1,
                'etiquetas' => self::etiquetasPorDefecto('Black Friday', 'Black', $negra, 'negra'), 'orden_marcas' => '',
            ],
            'cybermonday' => [
                'clave' => 'cybermonday', 'nombre' => 'Cyber Monday', 'activa' => 1,
                'desde' => $lunes . ' 00:00:00', 'hasta' => $martes . ' 23:59:59',
                'piel' => 'negra', 'colores' => $cyber,
                'titulo' => 'Cyber Monday en Kompy',
                'texto' => 'Las ofertas vuelven 48 horas, solo online. Lo que no cogiste en el Black Friday, ahora.',
                'lema' => 'Solo 48 horas',
                'texto_antes' => 'El lunes después del Black Friday, 48 horas de ofertas solo online. Apúntate al boletín y te avisamos. Mientras tanto, estas son las promociones de ahora.',
                'texto_despues' => 'El Cyber Monday ha terminado. Pero las promociones de ahora siguen aquí.',
                'cinta' => 1, 'cinta_texto' => 'Cyber Monday · solo 48 horas', 'redirigir' => 1, 'sin_carrusel' => 1,
                'etiquetas' => self::etiquetasPorDefecto('Cyber Monday', 'Cyber', $cyber, 'negra'), 'orden_marcas' => '',
            ],
            'reyes' => [
                'clave' => 'reyes', 'nombre' => 'Reyes Magos', 'activa' => 1,
                'desde' => $anioReyes . '-12-26 00:00:00', 'hasta' => ($anioReyes + 1) . '-01-05 23:59:59',
                'piel' => 'clara', 'colores' => $reyes,
                'titulo' => 'Regalos de Reyes para tu peludo',
                'texto' => 'Que a tu perro o a tu gato también le traigan algo: las mejores marcas con descuento hasta la noche de Reyes.',
                'lema' => 'Hasta la noche de Reyes',
                'texto_antes' => 'Del 26 de diciembre a la noche de Reyes, regalos con descuento para tu peludo. Apúntate al boletín y te avisamos. Mientras tanto, estas son las promociones de ahora.',
                'texto_despues' => 'Los Reyes ya pasaron. Pero las promociones de ahora siguen aquí.',
                'cinta' => 1, 'cinta_texto' => 'Reyes Magos · regalos para tu peludo hasta el 5 de enero', 'redirigir' => 1, 'sin_carrusel' => 1,
                'etiquetas' => self::etiquetasPorDefecto('Reyes', 'Reyes', $reyes, 'clara'), 'orden_marcas' => '',
            ],
        ];
    }

    /** Crea las campañas predefinidas que falten (por clave). Devuelve cuántas ha creado. */
    public static function crearPredefinidas(): int
    {
        $n = 0;
        foreach (self::predefinidas() as $clave => $d) {
            if (self::porClave($clave) === null && self::guardar($d) > 0) {
                ++$n;
            }
        }

        return $n;
    }

    /** Lo que se hacía al instalar: las predefinidas que falten. */
    public static function crearPorDefecto(): void
    {
        self::crearPredefinidas();
    }

    /** Todas las campañas, por id. @return array<int, array> */
    public static function todas(): array
    {
        if (self::$todas === null) {
            self::$todas = [];
            try {
                foreach (Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . self::TABLA . ' ORDER BY desde DESC, id_kpy_promo_campana') ?: [] as $f) {
                    self::$todas[(int) $f['id_kpy_promo_campana']] = self::conExtras($f);
                }
            } catch (Throwable $e) {
                // sin tabla (módulo a medio instalar): sin campañas
            }
        }

        return self::$todas;
    }

    public static function olvidar(): void
    {
        self::$todas = null;
    }

    public static function porId(int $id): ?array
    {
        return self::todas()[$id] ?? null;
    }

    public static function porClave(string $clave): ?array
    {
        foreach (self::todas() as $c) {
            if ($c['clave'] === $clave) {
                return $c;
            }
        }

        return null;
    }

    /** La campaña viva ahora mismo (activa y entre sus fechas), o null. */
    public static function viva(?int $ahora = null): ?array
    {
        foreach (self::todas() as $c) {
            if ($c['estado'] === 'viva') {
                return $c;
            }
        }

        return null;
    }

    /** El estado de una campaña a una hora: apagada, programada, viva o terminada. */
    public static function estado(array $c, ?int $ahora = null): string
    {
        $ahora ??= time();
        if ((int) $c['activa'] !== 1) {
            return 'apagada';
        }
        $desde = $c['desde'] ? strtotime((string) $c['desde']) : null;
        $hasta = $c['hasta'] ? strtotime((string) $c['hasta']) : null;
        if ($desde !== null && $ahora < $desde) {
            return 'programada';
        }
        if ($hasta !== null && $ahora > $hasta) {
            return 'terminada';
        }

        return 'viva';
    }

    /**
     * Guarda una campaña (nueva o existente, por `id_kpy_promo_campana`). Devuelve el id, o 0 si la
     * clave no vale. `etiquetas` puede venir como array (se guarda en JSON).
     */
    public static function guardar(array $d): int
    {
        $clave = KplClaves::slug((string) ($d['clave'] ?? ''));
        $clave = str_replace('-', '', $clave);
        if ($clave === '' || in_array($clave, KplClaves::RESERVADAS, true) || $clave === 'promociones' || $clave === 'marcas') {
            return 0;
        }
        $piel = in_array($d['piel'] ?? '', self::PIELES, true) ? (string) $d['piel'] : 'negra';
        $colores = self::paletaLimpia(is_array($d['colores'] ?? null) ? $d['colores'] : [], $piel);
        $etiquetas = $d['etiquetas'] ?? self::etiquetasPorDefecto('Black Friday', 'Black', $colores, $piel);
        if (!is_array($etiquetas)) {
            $etiquetas = json_decode((string) $etiquetas, true) ?: self::etiquetasPorDefecto('Black Friday', 'Black', $colores, $piel);
        }
        $fila = [
            'clave' => pSQL($clave),
            'nombre' => pSQL(mb_substr(trim((string) ($d['nombre'] ?? $clave)), 0, 64)),
            'activa' => (int) !empty($d['activa']),
            'desde' => self::fechaSql((string) ($d['desde'] ?? ''), '00:00:00'),
            'hasta' => self::fechaSql((string) ($d['hasta'] ?? ''), '23:59:59'),
            'piel' => pSQL($piel),
            'colores' => pSQL(json_encode($colores)),
            'titulo' => pSQL(mb_substr(trim((string) ($d['titulo'] ?? '')), 0, 120)),
            'texto' => pSQL(mb_substr(trim((string) ($d['texto'] ?? '')), 0, 255)),
            'lema' => pSQL(mb_substr(trim((string) ($d['lema'] ?? '')), 0, 120)),
            'texto_antes' => pSQL(mb_substr(trim((string) ($d['texto_antes'] ?? '')), 0, 255)),
            'texto_despues' => pSQL(mb_substr(trim((string) ($d['texto_despues'] ?? '')), 0, 255)),
            'cinta' => (int) !empty($d['cinta']),
            'cinta_texto' => pSQL(mb_substr(trim((string) ($d['cinta_texto'] ?? '')), 0, 120)),
            'redirigir' => (int) !empty($d['redirigir']),
            // El bloque de campaña en la portada de la tienda sustituye al carrusel mientras dura.
            'sin_carrusel' => (int) !empty($d['sin_carrusel']),
            'etiquetas' => pSQL(json_encode(self::etiquetasLimpias($etiquetas), JSON_UNESCAPED_UNICODE), true),
            'orden_marcas' => pSQL(mb_substr(trim((string) ($d['orden_marcas'] ?? '')), 0, 255)),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        $db = Db::getInstance();
        $id = (int) ($d['id_kpy_promo_campana'] ?? 0);
        if ($id > 0 && self::porId($id) !== null) {
            $db->update(self::TABLA, $fila, 'id_kpy_promo_campana = ' . $id);
        } else {
            $fila['date_add'] = $fila['date_upd'];
            $db->insert(self::TABLA, $fila);
            $id = (int) $db->Insert_ID();
        }
        self::olvidar();
        self::apuntarClaves();

        return $id;
    }

    public static function borrar(int $id): void
    {
        Db::getInstance()->delete(self::TABLA, 'id_kpy_promo_campana = ' . (int) $id);
        self::olvidar();
        self::apuntarClaves();
    }

    /** Las claves de las campañas activas, para las rutas (`blackfriday|navidad`). */
    public static function clavesParaRutas(): string
    {
        return (string) Configuration::getGlobalValue(self::CONF_CLAVES);
    }

    private static function apuntarClaves(): void
    {
        $claves = [];
        foreach (self::todas() as $c) {
            if ((int) $c['activa'] === 1) {
                $claves[] = $c['clave'];
            }
        }
        Configuration::updateGlobalValue(self::CONF_CLAVES, implode('|', $claves));
    }

    /** La dirección de la landing de una campaña. */
    public static function url(array $c): string
    {
        return Context::getContext()->link->getModuleLink('kpypromolanding', 'campana', ['campana' => $c['clave']], true);
    }

    /**
     * La etiqueta de campaña para una promoción de un tipo con un %: nombre, fondo y texto. Null si
     * la campaña no cambia ese tipo.
     *
     * @return array{label: string, background: string, color: string}|null
     */
    public static function etiqueta(array $c, string $tipo, float $pct): ?array
    {
        $e = $c['etiquetas'][$tipo] ?? null;
        if (!$e || trim((string) $e['nombre']) === '') {
            return null;
        }

        return [
            'label' => str_replace('{pct}', KplTextos::pct($pct), (string) $e['nombre']),
            'background' => (string) ($e['fondo'] ?: '#111111'),
            'color' => (string) ($e['texto'] ?: '#F2C965'),
        ];
    }

    /**
     * El nombre de campaña de un tipo sin el % («Black Friday», «Cupón Black»): para las etiquetas
     * de la landing, que ya enseñan el % al lado. Vacío si la campaña no cambia ese tipo.
     */
    public static function etiquetaCorta(array $c, string $tipo): string
    {
        $nombre = (string) ($c['etiquetas'][$tipo]['nombre'] ?? '');
        $nombre = (string) preg_replace('/\s*[−\-–]?\s*\{pct\}\s*%?/u', '', $nombre);

        return trim((string) preg_replace('/\s+/u', ' ', $nombre), " \t·-−–");
    }

    /**
     * Los colores de la etiqueta de un tipo tal como se ven sobre la piel de la campaña: [fondo,
     * texto]. Sobre la piel negra una etiqueta de fondo oscuro no se vería: se da la vuelta (fondo
     * del color del texto, texto del color del fondo), que es como van en la landing.
     *
     * @return array{0: string, 1: string}
     */
    public static function coloresSobrePiel(array $c, string $tipo): array
    {
        $e = $c['etiquetas'][$tipo] ?? [];
        $fondo = (string) (($e['fondo'] ?? '') ?: '#111111');
        $texto = (string) (($e['texto'] ?? '') ?: '#F2C965');
        if (($c['piel'] ?? 'negra') === 'negra' && self::oscuro($fondo)) {
            return [$texto, $fondo];
        }

        return [$fondo, $texto];
    }

    /**
     * Los colores del botón del menú («Ofertas y promociones» pasa a llamarse como la campaña): sobre
     * la piel negra, el fondo de la campaña con el acento encima, como la cinta; sobre la clara, el
     * acento relleno con su texto (el marfil de Reyes no se vería sobre la crema de la cabecera).
     *
     * @return array{0: string, 1: string} fondo y texto
     */
    public static function coloresMenu(array $c): array
    {
        $d = self::derivados($c);
        if (($c['piel'] ?? 'negra') === 'negra') {
            return [$d['fondo'], $d['acento']];
        }

        return [$d['acento'], $d['acento_fg']];
    }

    /** La paleta con sus tres colores válidos (los que falten, los de la piel). */
    public static function paletaLimpia(array $c, string $piel): array
    {
        $base = self::PALETA_PIEL[$piel] ?? self::PALETA_PIEL['negra'];
        $salida = [];
        foreach (self::COLORES as $k) {
            $salida[$k] = self::color((string) ($c[$k] ?? ''), $base[$k]);
        }

        return $salida;
    }

    /** La paleta de una campaña: la suya o, si no la tiene, la de su piel. */
    public static function coloresDe(array $c): array
    {
        return self::paletaLimpia(is_array($c['colores'] ?? null) ? $c['colores'] : [], (string) ($c['piel'] ?? 'negra'));
    }

    /**
     * Todos los colores que salen de la paleta: superficies (las tarjetas), bordes, el brillo de la
     * cabecera, los tintes de las tarjetas de cada tipo, la tinta y el gris de apoyo. Piel negra:
     * superficies un poco más claras que el fondo; clara: superficies blancas.
     */
    public static function derivados(array $c): array
    {
        $p = self::coloresDe($c);
        $p['acento_fg'] = self::oscuro($p['acento']) ? '#FFFFFF' : '#111111';
        $p['acento2_fg'] = self::oscuro($p['acento2']) ? '#FFFFFF' : '#111111';
        // Como texto sobre fondo claro, un acento muy claro (el oro) se oscurece para que se lea.
        $p['acento_texto'] = self::luminancia($p['acento']) > 160 ? self::mezclar($p['acento'], '#000000', .38) : $p['acento'];
        $p['acento2_texto'] = self::luminancia($p['acento2']) > 160 ? self::mezclar($p['acento2'], '#000000', .38) : $p['acento2'];
        if (($c['piel'] ?? 'negra') === 'negra') {
            return $p + [
                'superficie' => self::mezclar($p['fondo'], '#FFFFFF', .07),
                'superficie2' => self::mezclar($p['fondo'], '#FFFFFF', .13),
                'borde' => self::mezclar($p['fondo'], '#FFFFFF', .19),
                'borde_suave' => self::mezclar($p['fondo'], '#FFFFFF', .10),
                'brillo' => self::rgba($p['acento'], .18),
                'tinte' => self::rgba($p['acento'], .16),
                'tinte2' => self::rgba($p['acento2'], .16),
                'tinta' => '#FFFFFF',
                'mute' => '#D5DBDE',
            ];
        }

        return $p + [
            'superficie' => '#FFFFFF',
            'superficie2' => self::mezclar($p['fondo'], '#FFFFFF', .5),
            'borde' => self::mezclar($p['fondo'], '#000000', .12),
            'borde_suave' => self::mezclar($p['fondo'], '#000000', .06),
            'brillo' => self::rgba($p['acento'], .10),
            'tinte' => self::rgba($p['acento'], .10),
            'tinte2' => self::rgba($p['acento2'], .10),
            'tinta' => '#2A5C68',
            'mute' => '#5B6E75',
        ];
    }

    /**
     * Los colores de la campaña como variables CSS para sus bloques de la landing (`style="…"`):
     * la paleta y lo que sale de ella, y el fondo y el texto de cada etiqueta sobre la piel. Con los
     * colores por defecto es lo mismo que trae la hoja de estilos; si en la pantalla se cambia un
     * color, toda la landing lo sigue.
     */
    public static function estilo(array $c): string
    {
        $d = self::derivados($c);
        $vars = [
            '--kpl-fondo:' . $d['fondo'], '--kpl-superficie:' . $d['superficie'], '--kpl-superficie-2:' . $d['superficie2'],
            '--kpl-borde:' . $d['borde'], '--kpl-borde-suave:' . $d['borde_suave'], '--kpl-brillo:' . $d['brillo'],
            '--kpl-tinte:' . $d['tinte'], '--kpl-tinte-2:' . $d['tinte2'],
            '--kpl-acento:' . $d['acento'], '--kpl-acento-fg:' . $d['acento_fg'], '--kpl-acento-texto:' . $d['acento_texto'],
            '--kpl-acento-2:' . $d['acento2'], '--kpl-acento-2-fg:' . $d['acento2_fg'], '--kpl-acento-2-texto:' . $d['acento2_texto'],
            '--kpl-bf-amarillo:' . $d['acento'], '--kpl-bf-rojo:' . $d['acento2'],
        ];
        foreach (['special_price' => 'sp', 'cupon' => 'cup', 'segunda_unidad' => 'seg', 'kilos_gratis' => 'kg'] as $tipo => $corto) {
            [$fondo, $texto] = self::coloresSobrePiel($c, $tipo);
            $vars[] = '--kpl-et-' . $corto . '-bg:' . $fondo . ';--kpl-et-' . $corto . '-fg:' . $texto;
        }

        return implode(';', $vars);
    }

    /** Los colores del bloque de la portada de la tienda (variables `--h-*`), de la misma paleta. */
    public static function estiloPortada(array $c): string
    {
        $d = self::derivados($c);
        [$acc, $accFg] = self::coloresSobrePiel($c, 'special_price');

        return '--h-bg:' . $d['fondo'] . ';--h-bg-2:' . $d['superficie2'] . ';--h-card:' . $d['superficie'] . ';--h-fg:' . $d['tinta'] . ';--h-mute:' . $d['mute']
            . ';--h-acc:' . $acc . ';--h-acc-fg:' . $accFg . ';--h-acc-2:' . $d['acento2'] . ';--h-acc-2-fg:' . $d['acento2_fg']
            . ';--h-borde:' . $d['borde'] . ';--h-brillo:' . $d['brillo'];
    }

    /** Mezcla dos colores `#rrggbb` (p = cuánto del segundo, 0–1). */
    private static function mezclar(string $a, string $b, float $p): string
    {
        $ca = sscanf($a, '#%02x%02x%02x');
        $cb = sscanf($b, '#%02x%02x%02x');
        $salida = '#';
        for ($i = 0; $i < 3; ++$i) {
            $salida .= str_pad(dechex((int) round($ca[$i] + ($cb[$i] - $ca[$i]) * $p)), 2, '0', STR_PAD_LEFT);
        }

        return strtoupper($salida);
    }

    /** `#rrggbb` con transparencia, como `rgba(r,g,b,a)`. */
    private static function rgba(string $hex, float $alfa): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alfa . ')';
    }

    /** ¿Es un color oscuro (`#rrggbb`)? Para saber si el texto va claro encima. */
    public static function oscuro(string $hex): bool
    {
        return self::luminancia($hex) < 128;
    }

    /** La luminancia (0–255) de un color `#rrggbb`; 255 si no es un color. */
    private static function luminancia(string $hex): float
    {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return 255;
        }

        return 0.299 * hexdec(substr($hex, 1, 2)) + 0.587 * hexdec(substr($hex, 3, 2)) + 0.114 * hexdec(substr($hex, 5, 2));
    }

    /**
     * ¿Entra esta promoción (id de kpycoupons) en la campaña? Las marcadas en el paso 6; si no hay
     * ninguna marcada, todas.
     *
     * @param array<int, string> $marcadas id de promoción => campañas marcadas («blackfriday,navidad»)
     */
    public static function entra(array $c, int $idPromo, array $marcadas): bool
    {
        $alguna = false;
        foreach ($marcadas as $lista) {
            if (self::enLista($c['clave'], $lista)) {
                $alguna = true;
                break;
            }
        }
        if (!$alguna) {
            return true;
        }

        return self::enLista($c['clave'], $marcadas[$idPromo] ?? '');
    }

    private static function enLista(string $clave, string $lista): bool
    {
        return in_array($clave, array_map('trim', explode(',', $lista)), true);
    }

    /** El orden de marcas de la campaña, como claves de marca (`royal-canin`), en el orden escrito. @return string[] */
    public static function ordenMarcas(array $c): array
    {
        $salida = [];
        foreach (explode(',', (string) $c['orden_marcas']) as $nombre) {
            $s = KplClaves::slug(trim($nombre));
            if ($s !== '') {
                $salida[] = $s;
            }
        }

        return $salida;
    }

    /** Las claves del resumen reordenadas: primero las marcas en el orden de la campaña, el resto como venía. */
    public static function ordenar(array $claves, array $c): array
    {
        $orden = self::ordenMarcas($c);
        if ($orden === []) {
            return $claves;
        }
        $posicion = array_flip($orden);
        $i = 0;
        $con = [];
        foreach ($claves as $k => $x) {
            $marca = KplClaves::slug((string) ($x['marca'] ?? ''));
            $con[$k] = [$posicion[$marca] ?? 9999, $i++];
        }
        uksort($claves, static fn ($a, $b): int => $con[$a] <=> $con[$b]);

        return $claves;
    }

    /** Los textos de fecha de una campaña: «del 1 al 30 de noviembre», cuántos días quedan… */
    public static function textos(array $c): array
    {
        $ahora = time();
        $desde = $c['desde'] ? strtotime((string) $c['desde']) : null;
        $hasta = $c['hasta'] ? strtotime((string) $c['hasta']) : null;
        $fechas = '';
        if ($desde !== null && $hasta !== null) {
            $fechas = 'Del ' . KplTextos::fecha(date('Y-m-d', $desde)) . ' al ' . KplTextos::fecha(date('Y-m-d', $hasta));
        } elseif ($hasta !== null) {
            $fechas = 'Hasta el ' . KplTextos::fecha(date('Y-m-d', $hasta));
        }
        $faltan = $desde !== null && $desde > $ahora ? (int) ceil(($desde - $ahora) / 86400) : 0;
        $quedan = $hasta !== null && $hasta > $ahora ? (int) ceil(($hasta - $ahora) / 86400) : 0;

        return [
            'fechas' => $fechas,
            'empieza' => $desde !== null ? KplTextos::fecha(date('Y-m-d', $desde)) : '',
            'termina' => $hasta !== null ? KplTextos::fecha(date('Y-m-d', $hasta)) : '',
            'faltan' => $faltan,
            'faltan_texto' => $faltan === 1 ? 'Empieza mañana' : ($faltan > 1 ? 'Empieza en ' . $faltan . ' días' : ''),
            'quedan' => $quedan,
            'quedan_texto' => $quedan === 1 ? 'Último día' : ($quedan > 1 ? 'Quedan ' . $quedan . ' días' : ''),
        ];
    }

    // ---------------------------------------------------------------------------------------

    private static function conExtras(array $f): array
    {
        $f['etiquetas'] = self::etiquetasLimpias(json_decode((string) $f['etiquetas'], true) ?: []);
        $f['colores'] = self::paletaLimpia(json_decode((string) ($f['colores'] ?? ''), true) ?: [], (string) ($f['piel'] ?? 'negra'));
        $f['estado'] = self::estado($f);

        return $f;
    }

    private static function etiquetasLimpias(array $e): array
    {
        $salida = [];
        foreach (self::TIPOS_ETIQUETA as $tipo) {
            $x = is_array($e[$tipo] ?? null) ? $e[$tipo] : [];
            $salida[$tipo] = [
                'nombre' => mb_substr(trim((string) ($x['nombre'] ?? '')), 0, 40),
                'fondo' => self::color((string) ($x['fondo'] ?? ''), '#111111'),
                'texto' => self::color((string) ($x['texto'] ?? ''), '#F2C965'),
            ];
        }

        return $salida;
    }

    private static function color(string $c, string $siNo): string
    {
        $c = trim($c);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $c) ? strtoupper($c) : $siNo;
    }

    /** Una fecha escrita («2026-11-01», «01/11/2026», con o sin hora) a SQL, o null si está vacía. */
    private static function fechaSql(string $f, string $horaSiNo): ?string
    {
        $f = trim($f);
        if ($f === '') {
            return null;
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})(.*)$#', $f, $m)) {
            $f = sprintf('%04d-%02d-%02d%s', $m[3], $m[2], $m[1], $m[4]);
        }
        $t = strtotime($f);
        if ($t === false) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
            return date('Y-m-d', $t) . ' ' . $horaSiNo;
        }

        return date('Y-m-d H:i:s', $t);
    }
}
