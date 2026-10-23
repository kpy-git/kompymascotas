<?php
/**
 * Quita el fondo blanco de una foto de producto o de un logo y deja un WebP con transparencia.
 *
 * v3 (14/10/2026 por la noche). Lo que hace, por pasos:
 *
 *  1. Mide el blanco del fondo en el borde de la foto. Si el borde no es blanco pero unos píxeles
 *     más adentro sí (un logo con marco), quita el marco. Si no hay fondo blanco, no recorta.
 *  2. Marca como «fondo posible» solo lo que es prácticamente igual al fondo (a `tolerancia`
 *     niveles del blanco del borde y sin color; 6 para un producto, 20 para un logo) e inunda desde
 *     los cuatro bordes: fondo = lo casi blanco **conectado con el borde**. Aquí está la diferencia
 *     con la v2: un saco blanco (Royal Canin, Advance) tiene un panel blanco que por dentro es del
 *     mismo blanco que el fondo, y lo único que lo separa es la línea del contorno del saco, de 10
 *     a 25 niveles más oscura. Con 20 de tolerancia esa línea «era fondo» y la inundación entraba
 *     por ella y se llevaba el panel; con 6 la línea es una pared.
 *  3. Sella las entradas: un brillo o una esquina de pocos píxeles donde la línea se pierde. Se
 *     «abre» el fondo (erosión y dilatación de `radio` píxeles): los huecos de menos de 2·radio+1
 *     píxeles se cierran y el panel deja de estar conectado con el exterior. Lo fino (muescas del
 *     borde dentado, el hueco entre dos sacos) sigue siendo fondo, salvo si es la entrada al panel.
 *  4. Contorno: alrededor del fondo, hasta `suave` píxeles, lo que es gris claro se deja
 *     semitransparente según lo claro que sea; es el halo del JPEG y el principio de una sombra. Pero
 *     **no** la línea del contorno de un saco blanco: un píxel con contraste alrededor y sin nada
 *     oscuro cerca es un borde de producto y se queda opaco (lo que le da forma al saco sobre un
 *     fondo de color).
 *     Y la sombra de la foto (lo gris claro y liso que queda debajo del producto) se difumina.
 *  5. Cuenta los objetos: las manchas sueltas minúsculas (polvo del JPEG) se quitan y se cuentan los
 *     trozos de tamaño parecido al mayor. Una foto «de transición» (saco viejo, flecha, saco nuevo)
 *     tiene 3; la foto de un saco, 1. Con eso la biblioteca sabe qué foto sirve de héroe.
 *  6. Recorta al producto con un poco de aire, reduce al ancho máximo y guarda. Por dentro, los
 *     píxeles transparentes son blancos (al reducir no sale borde oscuro).
 *
 * Si la imagen ya viene con transparencia (un PNG recortado a mano o de remove.bg), se respeta: solo
 * se quita el aire, se escala y se cuentan los objetos.
 *
 * Sin PrestaShop dentro: `bin/recortar.php` la usa desde la consola con cualquier foto.
 */
class KplRecorte
{
    /** Ancho máximo del resultado, en píxeles. */
    public int $maxAncho = 900;
    /** Aire alrededor del producto, en % de su caja. */
    public float $margen = 3;
    /** Radio del sellado; 0 = proporcional a la foto (el lado menor entre 50, entre 3 y 24). */
    public int $radio = 0;
    /** Hasta cuántos píxeles del fondo se suaviza el contorno; 0 = proporcional (el lado menor entre 90, entre 4 y 12). */
    public int $suave = 0;
    /** Cuántos niveles por debajo del blanco del borde sigue siendo «fondo posible». 6 para fotos, 20 para logos. */
    public int $tolerancia = 6;
    /** Calidad del WebP. */
    public int $calidad = 90;
    /** Si se pone una ruta .png, deja ahí la foto con el fondo en magenta y el contorno suavizado en amarillo. */
    public string $debug = '';

    /**
     * Recorta `$entrada` y deja el resultado en `$salida` (WebP o PNG según la extensión).
     *
     * @return array{ok: bool, nota: string, ancho: int, alto: int, bytes: int, objetos: int}
     *         `ok` false si no se ha podido (formato raro, sin fondo blanco…), con el porqué en `nota`.
     *         `objetos`: cuántos trozos grandes tiene el producto (1 = un saco; 2 o más = varios sacos, una flecha…).
     */
    public function recortar(string $entrada, string $salida): array
    {
        $fallo = static fn (string $nota): array => ['ok' => false, 'nota' => $nota, 'ancho' => 0, 'alto' => 0, 'bytes' => 0, 'objetos' => 0];
        if (!is_file($entrada)) {
            return $fallo('no existe el fichero');
        }
        [$src, $tipo] = $this->cargar($entrada);
        if ($src === null) {
            return $fallo('formato no admitido (vale JPG, PNG o WebP)');
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 40 || $h < 40) {
            self::liberar($src);

            return $fallo('la imagen es demasiado pequeña');
        }

        // ¿Ya viene recortada (con transparencia)? Entonces solo se quita el aire, se escala y se cuentan los objetos.
        if ($tipo !== IMAGETYPE_JPEG && $this->tieneTransparencia($src, $w, $h)) {
            $opaco = str_repeat("\0", $w * $h);
            for ($y = 0; $y < $h; ++$y) {
                $base = $y * $w;
                for ($x = 0; $x < $w; ++$x) {
                    if (((imagecolorat($src, $x, $y) >> 24) & 0x7F) < 100) {
                        $opaco[$base + $x] = "\1";
                    }
                }
            }
            [$caja, $objetos] = $this->objetos($opaco, $w, $h);
            if ($caja === null) {
                self::liberar($src);

                return $fallo('la imagen es toda transparente');
            }

            return $this->guardar($src, $caja, $w, $h, $salida, 'ya venía recortada', $objetos);
        }

        // Las imágenes grandes se reducen antes (un logo de 1600 px no necesita más de 1100 para recortarlo).
        if ($w * $h > 1200000) {
            $k = sqrt(1200000 / ($w * $h));
            $src = $this->aplanar($src, $w, $h, (int) round($w * $k), (int) round($h * $k));
            $w = imagesx($src);
            $h = imagesy($src);
        } elseif ($tipo !== IMAGETYPE_JPEG) {
            // PNG sin transparencia de verdad: se aplana sobre blanco por si trae un canal alfa vacío.
            $src = $this->aplanar($src, $w, $h, $w, $h);
        }

        // 1. El blanco del fondo se mide en el borde; si hay un marco, se quita.
        $medianaBorde = 0;
        $marco = 0;
        foreach ([0, 2, 4, 8, 12, 16, 24] as $inset) {
            if ($inset * 2 >= min($w, $h) - 20) {
                break;
            }
            $medianaBorde = $this->medianaBorde($src, $w, $h, $inset);
            if ($medianaBorde >= 235) {
                $marco = $inset;
                break;
            }
        }
        if ($medianaBorde < 235) {
            self::liberar($src);

            return $fallo('la foto no tiene fondo blanco (borde a ' . $medianaBorde . '): súbela ya recortada');
        }
        if ($marco > 0) {
            $tmp = imagecrop($src, ['x' => $marco, 'y' => $marco, 'width' => $w - 2 * $marco, 'height' => $h - 2 * $marco]);
            self::liberar($src);
            $src = $tmp;
            imagealphablending($src, false);
            $w = imagesx($src);
            $h = imagesy($src);
        }
        $n = $w * $h;
        $radio = $this->radio > 0 ? $this->radio : max(3, min(24, (int) round(min($w, $h) / 50)));
        $suave = $this->suave > 0 ? $this->suave : max(4, min(12, (int) round(min($w, $h) / 90)));
        $umbral = max(0, $medianaBorde - $this->tolerancia);  // «fondo posible»

        // 2 y 3. El fondo (lo casi blanco conectado con el borde, sellado) y los mapas de brillo y color.
        [$fondo, $mn, $sat, $recuperado] = $this->fondo($src, $w, $h, $umbral, $radio);

        // 4. Contorno semitransparente: gris claro a ≤ «suave» píxeles del fondo, que no sea la línea de un producto blanco.
        $alfa = str_repeat("\0", $n);
        $cola = [];
        $dist = str_repeat("\xFF", $n);
        for ($p = 0; $p < $n; ++$p) {
            if ($fondo[$p] === "\1") {
                $dist[$p] = "\0";
                $cola[] = $p;
            }
        }
        $i = 0;
        $base0 = 190;
        $rango = max(1, $umbral - $base0);
        $contorno = 0;
        while ($i < count($cola)) {
            $p = $cola[$i++];
            $d = ord($dist[$p]);
            if ($d >= $suave) {
                continue;
            }
            $x = $p % $w;
            $y = intdiv($p, $w);
            foreach ([[$x > 0, $p - 1], [$x < $w - 1, $p + 1], [$y > 0, $p - $w], [$y < $h - 1, $p + $w]] as [$ok, $q]) {
                if (!$ok || $dist[$q] !== "\xFF" || $fondo[$q] === "\1") {
                    continue;
                }
                $m = ord($mn[$q]);
                if ($m >= $umbral || $m < $base0 || ord($sat[$q]) > 24) {
                    $dist[$q] = "\xFE"; // blanco de producto, oscuro o de color: opaco y no se pasa
                    continue;
                }
                // ¿Es la línea del contorno de un producto blanco? Contraste alrededor y nada oscuro cerca.
                [$lmin, $lmax] = $this->entorno($mn, $w, $h, $q % $w, intdiv($q, $w), 4);
                if ($lmax - $lmin > 10 && $lmin >= 180) {
                    $dist[$q] = "\xFE";
                    continue;
                }
                $dist[$q] = chr($d + 1);
                $alfa[$q] = chr((int) min(127, round(127 * ($m - $base0) / $rango)));
                $cola[] = $q;
                ++$contorno;
            }
        }
        unset($cola, $dist);

        // 4b. La sombra: lo gris claro y liso que queda **debajo** del producto (bajo el último borde
        // de cada columna, hasta un 12 % de su alto) es la sombra de la foto. Se difumina según lo clara
        // que sea; la línea del contorno (un borde) la para, y lo oscuro o de color también.
        $yMin = $h; $yMax = -1;
        for ($p = 0; $p < $n; ++$p) {
            if ($fondo[$p] === "\0" && ord($alfa[$p]) < 100) {
                $y = intdiv($p, $w);
                if ($y < $yMin) $yMin = $y;
                if ($y > $yMax) $yMax = $y;
            }
        }
        $sombra = 0;
        if ($yMax > $yMin) {
            $tope = max(6, (int) round(($yMax - $yMin) * 0.12));
            $rangoSombra = max(1, $umbral - 150);
            for ($x = 0; $x < $w; ++$x) {
                $y = $yMax;
                while ($y >= 0 && $fondo[$y * $w + $x] === "\1") {
                    --$y;
                }
                $limite = max(0, $y - $tope);
                for (; $y >= $limite; --$y) {
                    $p = $y * $w + $x;
                    if ($fondo[$p] === "\1") {
                        continue;
                    }
                    $m = ord($mn[$p]);
                    if ($m < 150 || ord($sat[$p]) > 24) {
                        break;
                    }
                    [$lmin, $lmax] = $this->entorno($mn, $w, $h, $x, $y, 2);
                    if ($lmax - $lmin > 12) {
                        break;
                    }
                    $a = (int) min(127, round(127 * sqrt(($m - 150) / $rangoSombra)));
                    if ($a > ord($alfa[$p])) {
                        $alfa[$p] = chr($a);
                        ++$sombra;
                    }
                }
            }
        }
        unset($sat);

        // 5. Objetos: lo opaco (alfa < 100) por componentes; el polvo fuera, los trozos grandes contados.
        $opaco = str_repeat("\0", $n);
        for ($p = 0; $p < $n; ++$p) {
            if ($fondo[$p] === "\0" && ord($alfa[$p]) < 100) {
                $opaco[$p] = "\1";
            }
        }
        [$caja, $objetos, $polvo] = $this->objetos($opaco, $w, $h);
        if ($caja === null) {
            self::liberar($src);

            return $fallo('todo era fondo');
        }

        if ($this->debug !== '') {
            $this->depurar($src, $w, $h, $fondo, $alfa, $opaco);
        }

        // Salida con alfa.
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
        $nProd = 0;
        for ($y = 0; $y < $h; ++$y) {
            $base = $y * $w;
            for ($x = 0; $x < $w; ++$x) {
                $p = $base + $x;
                if ($fondo[$p] === "\1" || $polvo[$p] === "\1") {
                    continue;
                }
                $a = ord($alfa[$p]);
                if ($a >= 127) {
                    continue;
                }
                $c = imagecolorat($src, $x, $y);
                imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, ($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF, $a));
                ++$nProd;
            }
        }
        self::liberar($src);
        $nota = sprintf('producto %d %%', round(100 * $nProd / $n));
        if ($recuperado > 0) {
            $nota .= sprintf(', sellado %d px', $recuperado);
        }
        if ($contorno > 0) {
            $nota .= sprintf(', contorno %d px', $contorno);
        }
        if ($sombra > 0) {
            $nota .= sprintf(', sombra %d px', $sombra);
        }

        return $this->guardar($out, $caja, $w, $h, $salida, $nota, $objetos);
    }

    /**
     * Mide una foto de producto deprisa, sin recortarla: cuántos objetos tiene y la proporción de su
     * caja, en una copia pequeña (`$lado` píxeles de lado como mucho). Es lo que decide qué foto de
     * la ficha sale en las tarjetas de producto de la landing: una foto de transición (saco viejo,
     * flecha, saco nuevo) tiene 2 o 3 objetos o es más ancha que alta; la de un saco, 1 objeto y de
     * pie. Mismos pasos 1 a 3 y 5 que `recortar()`, sin el contorno ni la salida.
     *
     * @return ?array{objetos: int, ancho: int, alto: int, apta: bool} null si no se puede leer o no tiene fondo blanco
     */
    public static function medir(string $ruta, int $lado = 420): ?array
    {
        $r = new self();
        [$src, $tipo] = $r->cargar($ruta);
        if ($src === null) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 40 || $h < 40) {
            self::liberar($src);

            return null;
        }
        // Ya recortada (con transparencia): se cuenta lo opaco y listo.
        if ($tipo !== IMAGETYPE_JPEG && $r->tieneTransparencia($src, $w, $h)) {
            $opaco = str_repeat("\0", $w * $h);
            for ($y = 0; $y < $h; ++$y) {
                $base = $y * $w;
                for ($x = 0; $x < $w; ++$x) {
                    if (((imagecolorat($src, $x, $y) >> 24) & 0x7F) < 100) {
                        $opaco[$base + $x] = "\1";
                    }
                }
            }
            self::liberar($src);
            [$caja, $objetos] = $r->objetos($opaco, $w, $h);

            return self::medida($caja, $objetos);
        }
        if (max($w, $h) > $lado) {
            $k = $lado / max($w, $h);
            $src = $r->aplanar($src, $w, $h, max(1, (int) round($w * $k)), max(1, (int) round($h * $k)));
            $w = imagesx($src);
            $h = imagesy($src);
        } elseif ($tipo !== IMAGETYPE_JPEG) {
            $src = $r->aplanar($src, $w, $h, $w, $h);
        }
        $mediana = 0;
        foreach ([0, 2, 4] as $inset) {
            $mediana = $r->medianaBorde($src, $w, $h, $inset);
            if ($mediana >= 235) {
                break;
            }
        }
        if ($mediana < 235) {
            self::liberar($src);

            return null; // sin fondo blanco (una foto de ambiente): no se sabe, se deja la portada
        }
        $radio = max(2, min(12, (int) round(min($w, $h) / 50)));
        [$fondo] = $r->fondo($src, $w, $h, max(0, $mediana - $r->tolerancia), $radio);
        self::liberar($src);
        $n = $w * $h;
        $opaco = str_repeat("\0", $n);
        for ($p = 0; $p < $n; ++$p) {
            if ($fondo[$p] === "\0") {
                $opaco[$p] = "\1";
            }
        }
        [$caja, $objetos] = $r->objetos($opaco, $w, $h);

        return self::medida($caja, $objetos);
    }

    /** @param ?array{0:int,1:int,2:int,3:int} $caja */
    private static function medida(?array $caja, int $objetos): ?array
    {
        if ($caja === null) {
            return null;
        }
        $ancho = $caja[2] - $caja[0] + 1;
        $alto = $caja[3] - $caja[1] + 1;

        return ['objetos' => $objetos, 'ancho' => $ancho, 'alto' => $alto, 'apta' => $objetos === 1 && $alto >= 1.15 * $ancho];
    }

    // ---------------------------------------------------------------------------------------

    /** Libera una imagen de GD en PHP 7 (en PHP 8 se liberan solas y la función está desaconsejada desde la 8.5). */
    private static function liberar($img): void
    {
        if (PHP_VERSION_ID < 80000 && $img) {
            imagedestroy($img);
        }
    }

    /** Abre un JPG, PNG o WebP. @return array{0: ?resource, 1: int} la imagen (o null) y su tipo */
    private function cargar(string $ruta): array
    {
        if (!is_file($ruta)) {
            return [null, 0];
        }
        $info = @getimagesize($ruta);
        $tipo = $info[2] ?? 0;
        $src = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
            default => null,
        };
        if (!$src) {
            return [null, $tipo];
        }
        imagepalettetotruecolor($src);
        imagealphablending($src, false);
        imagesavealpha($src, true);

        return [$src, $tipo];
    }

    /**
     * Los pasos 2 y 3: qué píxeles son fondo (lo casi blanco **conectado con el borde**, con las
     * entradas selladas) y los mapas de brillo mínimo y de color de cada píxel, que el contorno
     * necesita después.
     *
     * @return array{0: string, 1: string, 2: string, 3: int} fondo, brillo, color, píxeles recuperados por el sellado
     */
    private function fondo($src, int $w, int $h, int $umbral, int $radio): array
    {
        $n = $w * $h;
        // 2. Brillo mínimo (el canal más bajo) y color (diferencia entre canales) de cada píxel.
        $mn = str_repeat("\0", $n);
        $sat = str_repeat("\0", $n);
        $casi = str_repeat("\0", $n);
        for ($y = 0; $y < $h; ++$y) {
            $base = $y * $w;
            for ($x = 0; $x < $w; ++$x) {
                $c = imagecolorat($src, $x, $y);
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                $min = min($r, $g, $b);
                $s = max($r, $g, $b) - $min;
                $mn[$base + $x] = chr($min);
                $sat[$base + $x] = chr(min(255, $s));
                if ($min >= $umbral && $s <= 22) {
                    $casi[$base + $x] = "\1";
                }
            }
        }

        // Inundación desde el borde.
        $semillas = [];
        for ($x = 0; $x < $w; ++$x) {
            $semillas[] = $x;
            $semillas[] = ($h - 1) * $w + $x;
        }
        for ($y = 1; $y < $h - 1; ++$y) {
            $semillas[] = $y * $w;
            $semillas[] = $y * $w + ($w - 1);
        }
        $M = $this->inundar($casi, $w, $h, $semillas);

        // 3. Sellado: apertura morfológica del fondo y segunda inundación.
        $O = $this->dilatar($this->erosionar($M, $w, $h, $radio), $w, $h, $radio);
        $Bo = $this->inundar($O, $w, $h, $semillas);
        $fondo = $Bo;
        $recuperado = 0;
        $gordoPerdido = str_repeat("\0", $n);
        for ($p = 0; $p < $n; ++$p) {
            if ($O[$p] === "\1" && $Bo[$p] === "\0") {
                $gordoPerdido[$p] = "\1";
                ++$recuperado;
            }
        }
        // Lo fino (M y no O) se decide por componentes: si toca un trozo gordo desconectado, es producto.
        $visto = str_repeat("\0", $n);
        for ($p0 = 0; $p0 < $n; ++$p0) {
            if ($M[$p0] !== "\1" || $O[$p0] === "\1" || $visto[$p0] === "\1") {
                continue;
            }
            $comp = [$p0];
            $visto[$p0] = "\1";
            $i = 0;
            $tocaGordo = false;
            while ($i < count($comp)) {
                $p = $comp[$i++];
                $x = $p % $w;
                $y = intdiv($p, $w);
                foreach ([[$x > 0, $p - 1], [$x < $w - 1, $p + 1], [$y > 0, $p - $w], [$y < $h - 1, $p + $w]] as [$ok, $q]) {
                    if (!$ok) {
                        continue;
                    }
                    if ($gordoPerdido[$q] === "\1") {
                        $tocaGordo = true;
                    }
                    if ($M[$q] === "\1" && $O[$q] === "\0" && $visto[$q] === "\0") {
                        $visto[$q] = "\1";
                        $comp[] = $q;
                    }
                }
            }
            if (!$tocaGordo) {
                foreach ($comp as $p) {
                    $fondo[$p] = "\1";
                }
            } else {
                $recuperado += count($comp);
            }
        }

        return [$fondo, $mn, $sat, $recuperado];
    }

    /** ¿Tiene píxeles transparentes de verdad? (Se muestrea una rejilla de 80 × 80.) */
    private function tieneTransparencia($img, int $w, int $h): bool
    {
        $muestras = 0;
        $conAlfa = 0;
        $px = max(1, intdiv($w, 80));
        $py = max(1, intdiv($h, 80));
        for ($y = 0; $y < $h; $y += $py) {
            for ($x = 0; $x < $w; $x += $px) {
                ++$muestras;
                if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 8) {
                    ++$conAlfa;
                }
            }
        }

        return $conAlfa > $muestras * 0.02;
    }

    /**
     * Los componentes conexos de lo opaco: devuelve la caja de los que valen, cuántos son «grandes»
     * (al menos el 15 % del mayor) y una máscara con el polvo (menos del 0,02 % del mayor: motas del
     * JPEG; la «i» de un rótulo ya es más), que se tira.
     *
     * @return array{0: ?array{0:int,1:int,2:int,3:int}, 1: int, 2: string}
     */
    private function objetos(string $opaco, int $w, int $h): array
    {
        $n = $w * $h;
        $visto = str_repeat("\0", $n);
        $comps = [];
        for ($p0 = 0; $p0 < $n; ++$p0) {
            if ($opaco[$p0] !== "\1" || $visto[$p0] === "\1") {
                continue;
            }
            $cola = [$p0];
            $visto[$p0] = "\1";
            $i = 0;
            $x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;
            while ($i < count($cola)) {
                $p = $cola[$i++];
                $x = $p % $w;
                $y = intdiv($p, $w);
                if ($x < $x0) $x0 = $x;
                if ($x > $x1) $x1 = $x;
                if ($y < $y0) $y0 = $y;
                if ($y > $y1) $y1 = $y;
                if ($x > 0 && $opaco[$p - 1] === "\1" && $visto[$p - 1] === "\0") { $visto[$p - 1] = "\1"; $cola[] = $p - 1; }
                if ($x < $w - 1 && $opaco[$p + 1] === "\1" && $visto[$p + 1] === "\0") { $visto[$p + 1] = "\1"; $cola[] = $p + 1; }
                if ($y > 0 && $opaco[$p - $w] === "\1" && $visto[$p - $w] === "\0") { $visto[$p - $w] = "\1"; $cola[] = $p - $w; }
                if ($y < $h - 1 && $opaco[$p + $w] === "\1" && $visto[$p + $w] === "\0") { $visto[$p + $w] = "\1"; $cola[] = $p + $w; }
            }
            $comps[] = ['area' => count($cola), 'caja' => [$x0, $y0, $x1, $y1], 'pixeles' => count($cola) < 4000 ? $cola : null];
        }
        unset($visto);
        $polvo = str_repeat("\0", $n);
        if ($comps === []) {
            return [null, 0, $polvo];
        }
        usort($comps, static fn (array $a, array $b): int => $b['area'] <=> $a['area']);
        $mayor = $comps[0]['area'];
        $objetos = 0;
        $caja = null;
        foreach ($comps as $c) {
            if ($c['area'] < max(12, $mayor * 0.0002)) {
                if ($c['pixeles'] !== null) {
                    foreach ($c['pixeles'] as $p) {
                        $polvo[$p] = "\1";
                    }
                }
                continue;
            }
            if ($c['area'] >= $mayor * 0.15) {
                ++$objetos;
            }
            $caja = $caja === null ? $c['caja'] : [min($caja[0], $c['caja'][0]), min($caja[1], $c['caja'][1]), max($caja[2], $c['caja'][2]), max($caja[3], $c['caja'][3])];
        }

        return [$caja, $objetos, $polvo];
    }

    /** Mínimo y máximo del brillo en el cuadrado de radio r alrededor de (x, y). @return array{0:int,1:int} */
    private function entorno(string $mn, int $w, int $h, int $x, int $y, int $r): array
    {
        $min = 255;
        $max = 0;
        $y0 = max(0, $y - $r); $y1 = min($h - 1, $y + $r);
        $x0 = max(0, $x - $r); $x1 = min($w - 1, $x + $r);
        for ($yy = $y0; $yy <= $y1; ++$yy) {
            $base = $yy * $w;
            for ($xx = $x0; $xx <= $x1; ++$xx) {
                $v = ord($mn[$base + $xx]);
                if ($v < $min) $min = $v;
                if ($v > $max) $max = $v;
            }
        }

        return [$min, $max];
    }

    /** Deja en `$this->debug` la foto con el fondo en magenta, el contorno suavizado en amarillo y el polvo en cian. */
    private function depurar($src, int $w, int $h, string $fondo, string $alfa, string $opaco): void
    {
        $d = imagecreatetruecolor($w, $h);
        imagecopy($d, $src, 0, 0, 0, 0, $w, $h);
        $magenta = imagecolorallocate($d, 255, 0, 255);
        $amarillo = imagecolorallocate($d, 255, 220, 0);
        for ($y = 0; $y < $h; ++$y) {
            $base = $y * $w;
            for ($x = 0; $x < $w; ++$x) {
                $p = $base + $x;
                if ($fondo[$p] === "\1") {
                    imagesetpixel($d, $x, $y, $magenta);
                } elseif (ord($alfa[$p]) > 0 && (($x + $y) & 1)) {
                    imagesetpixel($d, $x, $y, $amarillo);
                }
            }
        }
        @imagepng($d, $this->debug);
        self::liberar($d);
    }

    /** Copia (y escala) sobre blanco, sin transparencia. */
    private function aplanar($src, int $w, int $h, int $nw, int $nh)
    {
        $tmp = imagecreatetruecolor($nw, $nh);
        imagefill($tmp, 0, 0, imagecolorallocate($tmp, 255, 255, 255));
        imagealphablending($tmp, true);
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        self::liberar($src);
        imagealphablending($tmp, false);

        return $tmp;
    }

    /** Mediana del canal más bajo en el anillo de píxeles a «inset» del borde. */
    private function medianaBorde($img, int $w, int $h, int $inset): int
    {
        $hist = array_fill(0, 256, 0);
        $total = 0;
        $x0 = $inset; $y0 = $inset; $x1 = $w - 1 - $inset; $y1 = $h - 1 - $inset;
        for ($x = $x0; $x <= $x1; ++$x) {
            foreach ([$y0, $y1] as $y) {
                $c = imagecolorat($img, $x, $y);
                ++$hist[min(($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF)];
                ++$total;
            }
        }
        for ($y = $y0 + 1; $y < $y1; ++$y) {
            foreach ([$x0, $x1] as $x) {
                $c = imagecolorat($img, $x, $y);
                ++$hist[min(($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF)];
                ++$total;
            }
        }
        $acum = 0;
        for ($v = 0; $v < 256; ++$v) {
            $acum += $hist[$v];
            if ($acum >= $total / 2) {
                return $v;
            }
        }

        return 255;
    }

    /** Inundación (4 vecinos) por los píxeles permitidos a partir de las semillas. */
    private function inundar(string $permitido, int $w, int $h, array $semillas): string
    {
        $m = str_repeat("\0", $w * $h);
        $cola = $semillas;
        $i = 0;
        while ($i < count($cola)) {
            $p = $cola[$i++];
            if ($m[$p] === "\1" || $permitido[$p] !== "\1") {
                continue;
            }
            $m[$p] = "\1";
            $x = $p % $w;
            $y = intdiv($p, $w);
            if ($x > 0) $cola[] = $p - 1;
            if ($x < $w - 1) $cola[] = $p + 1;
            if ($y > 0) $cola[] = $p - $w;
            if ($y < $h - 1) $cola[] = $p + $w;
        }

        return $m;
    }

    /** Erosión con un cuadrado de lado 2r+1 (lo de fuera de la imagen cuenta como dentro). */
    private function erosionar(string $m, int $w, int $h, int $r): string
    {
        $n = $w * $h;
        $t = str_repeat("\0", $n);
        for ($y = 0; $y < $h; ++$y) {
            $base = $y * $w;
            $run = 0;
            $izq = [];
            for ($x = 0; $x < $w; ++$x) {
                $run = $m[$base + $x] === "\1" ? $run + 1 : 0;
                $izq[$x] = $run;
            }
            $run = 0;
            for ($x = $w - 1; $x >= 0; --$x) {
                $run = $m[$base + $x] === "\1" ? $run + 1 : 0;
                if ($run > 0 && $izq[$x] >= min($r + 1, $x + 1) && $run >= min($r + 1, $w - $x)) {
                    $t[$base + $x] = "\1";
                }
            }
        }
        $o = str_repeat("\0", $n);
        for ($x = 0; $x < $w; ++$x) {
            $run = 0;
            $arr = [];
            for ($y = 0; $y < $h; ++$y) {
                $run = $t[$y * $w + $x] === "\1" ? $run + 1 : 0;
                $arr[$y] = $run;
            }
            $run = 0;
            for ($y = $h - 1; $y >= 0; --$y) {
                $run = $t[$y * $w + $x] === "\1" ? $run + 1 : 0;
                if ($run > 0 && $arr[$y] >= min($r + 1, $y + 1) && $run >= min($r + 1, $h - $y)) {
                    $o[$y * $w + $x] = "\1";
                }
            }
        }

        return $o;
    }

    /** Dilatación con un cuadrado de lado 2r+1. */
    private function dilatar(string $m, int $w, int $h, int $r): string
    {
        $n = $w * $h;
        $t = str_repeat("\0", $n);
        for ($y = 0; $y < $h; ++$y) {
            $base = $y * $w;
            $ult = -1000000;
            for ($x = 0; $x < $w; ++$x) {
                if ($m[$base + $x] === "\1") $ult = $x;
                if ($x - $ult <= $r) $t[$base + $x] = "\1";
            }
            $ult = 1000000;
            for ($x = $w - 1; $x >= 0; --$x) {
                if ($m[$base + $x] === "\1") $ult = $x;
                if ($ult - $x <= $r) $t[$base + $x] = "\1";
            }
        }
        $o = str_repeat("\0", $n);
        for ($x = 0; $x < $w; ++$x) {
            $ult = -1000000;
            for ($y = 0; $y < $h; ++$y) {
                if ($t[$y * $w + $x] === "\1") $ult = $y;
                if ($y - $ult <= $r) $o[$y * $w + $x] = "\1";
            }
            $ult = 1000000;
            for ($y = $h - 1; $y >= 0; --$y) {
                if ($t[$y * $w + $x] === "\1") $ult = $y;
                if ($ult - $y <= $r) $o[$y * $w + $x] = "\1";
            }
        }

        return $o;
    }

    /**
     * Recorta la caja con margen, escala al ancho máximo y guarda (WebP o PNG según la extensión).
     *
     * @param array{0:int,1:int,2:int,3:int} $caja x0, y0, x1, y1
     */
    private function guardar($img, array $caja, int $w, int $h, string $salida, string $nota, int $objetos): array
    {
        [$x0, $y0, $x1, $y1] = $caja;
        $bw = $x1 - $x0 + 1;
        $bh = $y1 - $y0 + 1;
        $mx = (int) round($bw * $this->margen / 100);
        $my = (int) round($bh * $this->margen / 100);
        $cx = max(0, $x0 - $mx);
        $cy = max(0, $y0 - $my);
        $cw = min($w - $cx, $bw + 2 * $mx);
        $ch = min($h - $cy, $bh + 2 * $my);
        $escala = min(1, $this->maxAncho / max(1, $cw));
        $dw = max(1, (int) round($cw * $escala));
        $dh = max(1, (int) round($ch * $escala));
        $fin = imagecreatetruecolor($dw, $dh);
        imagealphablending($fin, false);
        imagesavealpha($fin, true);
        imagefill($fin, 0, 0, imagecolorallocatealpha($fin, 255, 255, 255, 127));
        imagecopyresampled($fin, $img, 0, 0, $cx, $cy, $dw, $dh, $cw, $ch);
        self::liberar($img);
        $dir = dirname($salida);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $ok = preg_match('/\.webp$/i', $salida) ? @imagewebp($fin, $salida, $this->calidad) : @imagepng($fin, $salida, 6);
        self::liberar($fin);
        if (!$ok || !is_file($salida)) {
            return ['ok' => false, 'nota' => 'no se ha podido escribir ' . basename($salida), 'ancho' => 0, 'alto' => 0, 'bytes' => 0, 'objetos' => 0];
        }
        clearstatcache(true, $salida);
        $nota .= $objetos === 1 ? ', 1 objeto' : ', ' . $objetos . ' objetos';

        return ['ok' => true, 'nota' => $nota, 'ancho' => $dw, 'alto' => $dh, 'bytes' => (int) filesize($salida), 'objetos' => $objetos];
    }
}
