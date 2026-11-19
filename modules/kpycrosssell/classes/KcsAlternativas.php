<?php
/**
 * Las **alternativas** de una línea de la cesta que rompe el envío único: productos del almacén de
 * referencia que sirven para lo mismo. Es el motor del bloque «Cámbialo por…» (cesta de un solo
 * almacén, 1.7.0; criterios en `C:\kompy-web\docs\criterios-cesta-un-solo-almacen.md`, apartado 1.3).
 *
 * Un candidato tiene que cumplir **todo** esto:
 *   - el mismo «Tipo de Producto», exactamente (húmedo por húmedo, snack por snack, dieta por dieta);
 *   - la misma mascota (mirando también el nombre: «Royal Canin» no es un perro);
 *   - la misma patología, exacta: ni una más ni una menos. En las dietas veterinarias cuentan todas las
 *     «Necesidades» salvo las que no son clínicas; en lo demás (comida normal, snacks, complementos),
 *     solo las que son enfermedad de verdad (`DURAS`: renal, hepático, cardiaco, diabetes, leishmania,
 *     recuperación, cerebral); las demás necesidades de un snack o un pienso normal («Problemas
 *     alérgicos» en un snack monoproteico, «Control del peso») son perfil y ordenan como tema. Sin
 *     patología en la ficha de una dieta, no se ofrece nada: mejor nada que mal;
 *   - el **tema** (`KcsPistas::temas`, por el nombre y la ficha) ordena: a un snack dental, otro dental
 *     antes que uno genérico, y uno para adelgazar el último (solo si no hay otra cosa);
 *   - compatible en edad (cachorro con cachorro; «Todas las edades» vale para todos);
 *   - en pienso, el mismo tamaño de perro si la ficha lo dice;
 *   - con stock para la cantidad de la línea, activo y sin apagar en `kpy_product_attribute`;
 *   - de una marca que se pueda sugerir (ni las vetadas de Caprichitos ni las quitadas a mano);
 *   - y, lo que lo hace alternativa de verdad: **del almacén de referencia**, según la regla de Julián
 *     (`KcsAlmacenes`), nunca según una regla propia.
 *
 * El precio no es un muro, es un orden (Rafa, 18/11: «veo pocas alternativas»): primero los formatos de
 * **precio parecido** (entre el 70 % y el 130 % de lo que vale la línea, sin su promoción de hoy, y nunca
 * más caro por kilo que ese 130 %); si no hay, o hay pocos, los demás por cercanía de precio hasta la
 * banda «como mucho» (del 25 % al 400 %, o hasta 10 € más si es más que el 400 %: a un snack de 1,55 €
 * el 400 % son 6 €, demasiado corto; ajustable). Se compara **lo que se paga**, con impuestos y promociones.
 *
 * Orden (Rafa, 18/11: «que más o menos sea lo mismo o sean sustitutivos»): las marcas preferidas para
 * dietas (Farmina) delante; mismo tema antes que genérico; precio parecido antes que lejano; cuantas más
 * categorías de la tienda comparta con la línea (la categoría principal cuenta doble: «Snacks dentales
 * perros», «Pienso natural perros»…); mismo tamaño de perro; la misma opción nutricional, ingrediente y
 * textura de la ficha; precio más cercano; lo más vendido. Lo que se pinta lo decide `KcsCesta`; aquí
 * solo se eligen.
 */
class KcsAlternativas
{
    /** Los tipos a los que se ofrece cambio. Antiparasitarios y medicamentos, nunca: eso lo resuelve el reparto. */
    public const TIPOS = [
        'Comida Seca', 'Comida Húmeda', 'Comida Semi-Húmeda', 'Snacks', 'Complemento Nutricional', 'Accesorios',
        'Dieta Veterinaria Seca', 'Dieta Veterinaria Húmedo',
    ];

    public const DIETAS = ['Dieta Veterinaria Seca', 'Dieta Veterinaria Húmedo'];

    /** Donde el tamaño del perro importa (Mini, Medium, Maxi). */
    public const PIENSOS = ['Comida Seca', 'Dieta Veterinaria Seca'];

    /** «Necesidades» que no son clínicas: en una dieta no cuentan como patología. */
    public const NO_CLINICAS = ['Vitaminas', 'Alta actividad', 'Sabor'];

    /**
     * Las enfermedades de verdad: fuera de las dietas (comida normal, snacks, complementos) son las únicas
     * que tienen que coincidir exactamente. Las demás necesidades (alergias, piel, digestivo, urinario,
     * articulaciones, peso) en un snack o un pienso normal son perfil: ordenan como tema, no filtran.
     */
    public const DURAS = ['Problemas renales', 'Problemas hepáticos e hígado', 'Problemas cardiacos y corazón', 'Diabetes', 'Leismania', 'Recuperación', 'Función cerebral'];

    /** Dos nombres para lo mismo en el catálogo. */
    public const EQUIVALENTES = ['Control del peso' => 'Sobrepeso'];

    /**
     * Etiquetas de síntoma que acompañan a otra patología en la ficha («Problemas de pelo y piel» en una
     * dieta hipoalergénica): en una dieta no cuentan para igualar la patología (z/d «alérgicos + piel» y
     * Piper Allergy «alérgicos» son lo mismo), pero la que coincide del todo va primero. Si la dieta solo
     * lleva etiquetas de síntoma (una dieta de piel), entonces sí son su patología.
     */
    public const SINTOMAS = ['Problemas de pelo y piel'];

    /**
     * Cuántos productos del catálogo se miran, de los más cercanos en precio de tarifa. Se mira el almacén
     * de todos (de la memoria del módulo, que guarda las respuestas de Julián unas horas), así que no
     * cuesta preguntar por muchos: lo de Neftys vende poco y por ventas se quedaba fuera.
     */
    private const CANDIDATOS_A_MIRAR = 150;

    /** A cuántos del almacén de referencia se les calcula el precio de verdad (con impuestos y promociones). */
    private const CON_PRECIO = 40;

    /**
     * Un formato con precio de tarifa por encima de este múltiplo de la banda «como mucho» no se mira: una
     * promoción baja el precio, pero no tanto.
     */
    private const HOLGURA_TARIFA = 1.5;

    /** @var array<string, array> alternativas ya calculadas en esta petición, por línea y almacén */
    private static array $cache = [];

    /**
     * Las alternativas de una línea, ya listas para pintar.
     *
     * @param array{id_product: int, id_product_attribute: int, id_manufacturer: int, cantidad: int, precio: float, precio_lista: float, precio_base: float, peso: float, nombre: string} $linea
     * @param array{minimo: float, maximo: float, lejos_min: float, lejos_max: float, tope: int, marcas_dieta: string[]} $ajustes
     * @param string[] $enCesta claves «producto-formato» que ya están en la cesta
     *
     * @return array<int, array{id_product: int, id_product_attribute: int, id_manufacturer: int, marca: string, marca_url: string,
     *                          nombre: string, formato: string, precio: float, precio_txt: string, diferencia: float, diferencia_txt: string,
     *                          parecido: bool, tema: int, foto: string, url: string}>
     */
    public static function para(array $linea, string $almacen, int $idCesta, array $ajustes, array $enCesta = []): array
    {
        $idProducto = (int) $linea['id_product'];
        $clave = $idCesta . '|' . $idProducto . '-' . (int) $linea['id_product_attribute'] . '|' . $almacen . '|'
            . json_encode([$ajustes['minimo'] ?? 0, $ajustes['maximo'] ?? 0, $ajustes['lejos_min'] ?? 0, $ajustes['lejos_max'] ?? 0, $ajustes['lejos_euros'] ?? 0, $ajustes['tope'] ?? 0, $ajustes['tarjetas'] ?? 0, $ajustes['marcas_dieta'] ?? [], $ajustes['nunca'] ?? []]);
        if (isset(self::$cache[$clave])) {
            return self::$cache[$clave];
        }

        return self::$cache[$clave] = self::calcular($linea, $almacen, $idCesta, $ajustes, $enCesta);
    }

    private static function calcular(array $linea, string $almacen, int $idCesta, array $ajustes, array $enCesta): array
    {
        $idProducto = (int) $linea['id_product'];
        if ($idProducto <= 0 || $almacen === '') {
            return [];
        }
        $rasgos = KcsSugerencias::rasgos($idProducto);
        $tipo = (string) $rasgos['tipo'];
        if (!in_array($tipo, self::TIPOS, true)) {
            return [];
        }
        $perfil = self::perfil($rasgos, (string) ($linea['nombre'] ?? ''));
        if ($perfil === null) {
            return [];
        }

        $ids = KcsSugerencias::ids();
        $valores = $ids['valores'];
        $cuantos = max(1, (int) ($ajustes['tope'] ?? 12));
        $cantidad = max(1, (int) $linea['cantidad']);
        $fuera = array_merge([$idProducto], KcsSugerencias::vetados($idProducto, (int) $linea['id_manufacturer']));
        foreach ($enCesta as $c) {
            $fuera[] = (int) explode('-', (string) $c, 2)[0];
        }

        $minimo = (float) ($ajustes['minimo'] ?? 0.7);
        $maximo = (float) ($ajustes['maximo'] ?? 1.3);
        $lejosMin = min($minimo, (float) ($ajustes['lejos_min'] ?? 0.25));
        $lejosMax = max($maximo, (float) ($ajustes['lejos_max'] ?? 4.0));
        $lejosEuros = max(0.0, (float) ($ajustes['lejos_euros'] ?? 10.0));
        $precioBase = (float) ($linea['precio_base'] ?? 0);
        // Lo que vale la línea (con impuestos, sin su promoción de hoy): la vara de medir de la banda.
        $precioLista = (float) ($linea['precio_lista'] ?? 0) ?: (float) ($linea['precio'] ?? 0);
        // La red por kilo solo tiene sentido en comida: en snacks, complementos y accesorios el peso de la
        // ficha no dice nada del valor (50 g de snack dental a 9,89 € no son «198 €/kg»).
        $peso = in_array($tipo, KcsSugerencias::COMIDA, true) ? (float) ($linea['peso'] ?? 0) : 0.0;

        $afin = self::afinidad($idProducto, $ids);
        // Lo que el reparto de pedidos de Julián no deja salir de este almacén aunque el de la cesta diga que sí
        // (los snacks de Natural Greatness, de Evolution): nunca se ofrece (`KcsAlmacenes::leerVetos`).
        $categoriasFuera = array_values(array_filter(array_map('intval', (array) (($ajustes['nunca'] ?? [])[$almacen] ?? []))));
        $productos = self::buscar($tipo, $perfil, (string) ($linea['nombre'] ?? ''), $fuera, $valores, $ids, $cantidad, $precioBase, $afin, $categoriasFuera);
        if ($productos === []) {
            return [];
        }

        // Los formatos que se venden de cada candidato, de precio de tarifa dentro de lo que podría llegar a
        // la banda «como mucho» (con holgura para las promociones).
        $formatos = self::formatos(array_keys($productos), $cantidad, $precioBase * $lejosMin, max($precioBase * $lejosMax, $precioBase + $lejosEuros) * self::HOLGURA_TARIFA);

        // El almacén de cada formato, de la memoria del módulo (o preguntando, y se apunta). Se recorren
        // los candidatos del más cercano en precio al más lejano y se para al tener bastantes del almacén
        // de referencia.
        $claves = [];
        foreach ($formatos as $id => $fs) {
            foreach ($fs as $f) {
                $claves[] = $id . '-' . $f['ipa'];
            }
        }
        KcsAlmacenes::recordar($claves);
        $conAlmacen = [];
        foreach ($productos as $id => $p) {
            foreach ($formatos[$id] ?? [] as $f) {
                if (KcsAlmacenes::recordado((int) $id, (int) $f['ipa'], (int) $p['id_manufacturer'], $cantidad) === $almacen) {
                    $conAlmacen[$id][] = $f;
                }
            }
            if (count($conAlmacen) >= self::CON_PRECIO) {
                break;
            }
        }
        if ($conAlmacen === []) {
            return [];
        }

        // El precio de verdad de esos formatos y, con él, el formato más parecido al de la línea.
        $preferidas = self::preferidas($tipo, (array) ($ajustes['marcas_dieta'] ?? []));
        $elegidos = [];
        foreach ($conAlmacen as $id => $fs) {
            $p = $productos[$id];
            foreach ($fs as $i => $f) {
                $fs[$i]['precio'] = self::precio((int) $id, (int) $f['ipa']);
            }
            $formato = self::formatoParecido($fs, $precioLista, $peso, $minimo, $maximo, $lejosMin, $lejosMax, $perfil['talla'], $lejosEuros);
            if ($formato === null) {
                continue;
            }
            $orden = array_search((int) $p['id_manufacturer'], $preferidas, true);
            $elegidos[] = [
                'id_product' => (int) $id,
                'id_product_attribute' => (int) $formato['ipa'],
                'id_manufacturer' => (int) $p['id_manufacturer'],
                'marca' => (string) $p['marca'],
                'nombre' => (string) $p['nombre'],
                'link_rewrite' => (string) $p['link_rewrite'],
                'id_image' => (int) $p['id_image'],
                'ventas' => (int) $p['ventas'],
                'preferida' => $orden === false ? PHP_INT_MAX : (int) $orden,
                'parecido' => (bool) $formato['parecido'],
                'tema' => (int) $p['tema'],
                'exacta' => (int) $p['exacta'],
                'categoria' => (int) $p['categoria'],
                'afinidad' => (int) $p['afinidad'],
                'precio' => (float) $formato['precio'],
                'distancia' => (float) $formato['distancia'],
                'mismo_tamano' => self::mismaTalla($perfil['talla'], (string) ($p['tamanos'] ?? '')),
            ];
        }
        // Marca preferida; mismo tema antes que genérico (y otro tema el último); precio parecido antes que
        // lejano; la ficha clínica que coincide del todo; más categorías compartidas; mismo tamaño de perro;
        // menos rasgos de ficha distintos; precio más cercano; más vendido.
        usort($elegidos, static fn (array $a, array $b): int => [$a['preferida'], $a['tema'], $a['parecido'] ? 0 : 1, $a['exacta'], -$a['categoria'], $a['mismo_tamano'], $a['afinidad'], $a['distancia'], -$a['ventas']]
            <=> [$b['preferida'], $b['tema'], $b['parecido'] ? 0 : 1, $b['exacta'], -$b['categoria'], $b['mismo_tamano'], $b['afinidad'], $b['distancia'], -$b['ventas']]);

        $elegidos = self::variar($elegidos, max(1, (int) ($ajustes['tarjetas'] ?? 3)));

        // Lo que se va a enseñar se confirma en vivo con el reparto de Julián: la memoria puede tener horas.
        $salida = [];
        foreach ($elegidos as $e) {
            if (count($salida) >= $cuantos) {
                break;
            }
            if (KcsAlmacenes::deProducto($e['id_product'], $e['id_product_attribute'], $e['id_manufacturer'], $cantidad) !== $almacen) {
                continue;
            }
            $salida[] = $e;
        }
        if ($salida === []) {
            return [];
        }

        return self::vestir($salida, $linea);
    }

    /**
     * Lo que tiene que compartir el candidato con la línea: mascota, patologías (exactas), tema, edad y tamaño.
     *
     * @return array{mascotas: string[], patologias: string[], temas: string[], dieta: bool, edad: string, tamano: string}|null
     */
    public static function perfil(array $rasgos, string $nombre): ?array
    {
        $tipo = (string) $rasgos['tipo'];
        // La mascota: para cambiar solo cuentan perro y gato. Si la ficha dice los dos, manda el nombre; si
        // no lo dice, el candidato tiene que servir a los dos.
        $suyas = array_values(array_intersect(['Perro', 'Gato'], (array) ($rasgos['mascotas'] ?? [])));
        if ($suyas === []) {
            $pista = KcsPistas::de($nombre)['animal'];
            $suyas = $pista === 'ambos' ? ['Perro', 'Gato'] : array_values(array_filter([$pista]));
        } elseif (count($suyas) === 2) {
            $pista = KcsPistas::de($nombre)['animal'];
            if (in_array($pista, ['Perro', 'Gato'], true)) {
                $suyas = [$pista];
            }
        }
        if ($suyas === []) {
            return null;
        }

        $dieta = in_array($tipo, self::DIETAS, true);
        $necesidades = (array) ($rasgos['necesidades'] ?? []);
        $patologias = self::patologiasDe($necesidades, $dieta);
        if ($dieta && $patologias === []) {
            // Una dieta sin patología en la ficha: no se sabe para qué es, y no se arriesga.
            return null;
        }

        return [
            'mascotas' => $suyas,
            'patologias' => $patologias,
            // Todo lo clínico de la ficha, para poner primero al candidato que coincide del todo.
            'todas' => self::clinicas($necesidades, $dieta),
            'temas' => KcsPistas::temas($nombre, $necesidades),
            'dieta' => $dieta,
            'edad' => (string) ($rasgos['edad'] ?? ''),
            // El tamaño de la ficha solo filtra en pienso; la talla (ficha o nombre: «perros grandes») ordena en todo.
            'tamano' => in_array($tipo, self::PIENSOS, true) ? (string) ($rasgos['tamano'] ?? '') : '',
            'talla' => KcsPistas::talla($nombre, (string) ($rasgos['tamano'] ?? '')),
        ];
    }

    /**
     * Si el candidato es para la misma talla de perro que la línea: 0 la misma; 1 todas (o no lo dice, o
     * la línea no lo dice); 2 otra.
     *
     * @param string $tamanos los valores de «Tamaño» del candidato, separados por comas
     */
    private static function mismaTalla(string $talla, string $tamanos): int
    {
        if ($talla === '' || trim($tamanos) === '') {
            return 1;
        }
        $suyas = [];
        foreach (explode(',', $tamanos) as $t) {
            $suyas[] = KcsPistas::talla('', trim($t));
        }
        if (in_array($talla, $suyas, true)) {
            return 0;
        }

        return in_array('', $suyas, true) ? 1 : 2;
    }

    /**
     * Las patologías que cuentan, normalizadas y ordenadas, para comparar «exactamente las mismas».
     * En una dieta, todas las necesidades salvo las no clínicas; en lo demás, solo las enfermedades de
     * verdad (`DURAS`): un snack «para alérgicos» se cambia por otro snack (el tema lo pone delante).
     *
     * @param string[] $necesidades
     *
     * @return string[]
     */
    public static function patologiasDe(array $necesidades, bool $dieta): array
    {
        $todas = self::clinicas($necesidades, $dieta);
        if (!$dieta) {
            return $todas;
        }
        // En una dieta, las etiquetas de síntoma no cuentan para igualar, salvo que sean lo único que lleva.
        $nucleo = array_values(array_diff($todas, self::SINTOMAS));

        return $nucleo !== [] ? $nucleo : $todas;
    }

    /**
     * Las necesidades que dicen algo clínico, normalizadas y ordenadas: en una dieta, todas salvo las no
     * clínicas; en lo demás, solo las enfermedades de verdad. Es lo que se compara para «coincide del todo».
     *
     * @param string[] $necesidades
     *
     * @return string[]
     */
    public static function clinicas(array $necesidades, bool $dieta): array
    {
        $salida = [];
        foreach ($necesidades as $n) {
            if ($dieta) {
                // En una dieta, «Control del peso» es la indicación de obesidad; en un pienso normal, un perfil.
                $n = self::EQUIVALENTES[$n] ?? $n;
            }
            if ($dieta ? !in_array($n, self::NO_CLINICAS, true) : in_array($n, self::DURAS, true)) {
                $salida[] = $n;
            }
        }
        $salida = array_values(array_unique($salida));
        sort($salida);

        return $salida;
    }

    /**
     * Los productos candidatos del catálogo, del más cercano en precio de tarifa al más lejano, con lo que
     * hace falta para filtrarlos y pintarlos. Una consulta; la patología exacta, la mascota y el tema se
     * comprueban en PHP sobre lo que trae.
     *
     * @param array{defecto: int, categorias: int[], rasgos: array<string, int[]>} $afin lo de la línea con lo que se mide el parecido (`afinidad`)
     * @param int[] $categoriasFuera categorías que nunca salen del almacén de referencia (`KCS_CESTA_NUNCA`): fuera
     *
     * @return array<int, array{id_manufacturer: int, marca: string, nombre: string, link_rewrite: string, id_image: int, ventas: int, tamanos: string, tema: int, categoria: int, afinidad: int}>
     */
    private static function buscar(string $tipo, array $perfil, string $nombreLinea, array $fuera, array $valores, array $ids, int $cantidad, float $precioBase, array $afin, array $categoriasFuera = []): array
    {
        $idTipo = (int) ($valores[$tipo] ?? 0);
        $idsMascota = array_values(array_filter(array_map(static fn (string $m): int => (int) ($valores[$m] ?? 0), $perfil['mascotas'])));
        if ($idTipo <= 0 || $idsMascota === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $ctx = Context::getContext();
        $idTienda = (int) $ctx->shop->id;
        $idLang = (int) $ctx->language->id;
        $fuera = array_values(array_unique(array_filter(array_map('intval', $fuera))));
        $vetadas = KcsSugerencias::vetadas();

        $donde = [
            'pr.active = 1',
            'pr.id_product NOT IN (' . implode(',', $fuera ?: [0]) . ')',
        ];
        if ($vetadas !== []) {
            $donde[] = 'pr.id_manufacturer NOT IN (' . implode(',', $vetadas) . ')';
        }
        // Las categorías que nunca salen del almacén de referencia (la regla de su reparto de pedidos).
        if ($categoriasFuera !== []) {
            $donde[] = 'NOT EXISTS (SELECT 1 FROM ' . $p . 'category_product cv
                    WHERE cv.id_product = pr.id_product AND cv.id_category IN (' . implode(',', array_map('intval', $categoriasFuera)) . '))';
        }
        // Coherencia de edad: a un pienso de adulto no se le ofrece uno de cachorro, ni al revés.
        $incompatibles = KcsSugerencias::edadesFuera($perfil['edad']);
        if ($incompatibles !== []) {
            $buenas = array_values(array_filter([(int) ($valores[$perfil['edad']] ?? 0), (int) ($valores['Todas las edades'] ?? 0)]));
            $donde[] = '(NOT EXISTS (SELECT 1 FROM ' . $p . 'feature_product fw
                    WHERE fw.id_product = pr.id_product AND fw.id_feature = ' . (int) $ids['edad'] . '
                      AND fw.id_feature_value IN (' . implode(',', $incompatibles) . '))'
                . ($buenas !== [] ? ' OR EXISTS (SELECT 1 FROM ' . $p . 'feature_product fv2
                    WHERE fv2.id_product = pr.id_product AND fv2.id_feature = ' . (int) $ids['edad'] . '
                      AND fv2.id_feature_value IN (' . implode(',', $buenas) . '))' : '') . ')';
        }
        // El tamaño del perro, en pienso: el mismo, «Todos los tamaños», o sin tamaño en la ficha.
        if ($perfil['tamano'] !== '' && $perfil['tamano'] !== 'Todos los tamaños') {
            $buenos = array_values(array_filter([(int) ($valores[$perfil['tamano']] ?? 0), (int) ($valores['Todos los tamaños'] ?? 0)]));
            if ($buenos !== []) {
                $donde[] = 'NOT EXISTS (SELECT 1 FROM ' . $p . 'feature_product fz
                    WHERE fz.id_product = pr.id_product AND fz.id_feature = ' . (int) $ids['tamano'] . '
                      AND fz.id_feature_value NOT IN (' . implode(',', $buenos) . '))';
            }
        }
        // La distancia en precio de tarifa del formato más cercano que se vende (con stock para la cantidad,
        // activo): es el orden de los candidatos, y sin ningún formato así, el producto no vale.
        $distancia = $precioBase > 0 ? 'ABS(pa.price + pr.price - ' . number_format($precioBase, 6, '.', '') . ')' : '0';

        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pr.id_product, pr.id_manufacturer, MAX(ma.name) AS marca, MAX(pl.name) AS nombre, MAX(pl.link_rewrite) AS link_rewrite,
                    COALESCE(MAX(vs.quantity), 0) AS ventas,
                    (SELECT im.id_image FROM {$p}image im WHERE im.id_product = pr.id_product ORDER BY im.cover DESC, im.position ASC LIMIT 1) AS id_image,
                    (SELECT MIN({$distancia}) FROM {$p}product_attribute pa
                     INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute
                           AND sa.id_shop = {$idTienda} AND sa.quantity >= " . max(1, $cantidad) . "
                     WHERE pa.id_product = pr.id_product
                       AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                                       WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)) AS distancia,
                    MAX(ps.id_category_default) AS categoria,
                    (SELECT GROUP_CONCAT(cp.id_category) FROM {$p}category_product cp WHERE cp.id_product = pr.id_product) AS categorias,
                    (SELECT GROUP_CONCAT(DISTINCT fr.id_feature_value) FROM {$p}feature_product fr WHERE fr.id_product = pr.id_product
                       AND fr.id_feature IN (" . implode(',', array_map('intval', [$ids['opcion'] ?: 0, $ids['sabor'] ?: 0, $ids['textura'] ?: 0])) . ")) AS rasgos,
                    GROUP_CONCAT(DISTINCT fm.id_feature_value) AS mascotas,
                    GROUP_CONCAT(DISTINCT fnl.value SEPARATOR '|') AS necesidades,
                    GROUP_CONCAT(DISTINCT fzl.value) AS tamanos
             FROM {$p}product pr
             INNER JOIN {$p}product_shop ps ON ps.id_product = pr.id_product AND ps.id_shop = {$idTienda} AND ps.active = 1
             INNER JOIN {$p}product_lang pl ON pl.id_product = pr.id_product AND pl.id_shop = {$idTienda} AND pl.id_lang = {$idLang}
             LEFT JOIN {$p}manufacturer ma ON ma.id_manufacturer = pr.id_manufacturer
             LEFT JOIN {$p}product_sale vs ON vs.id_product = pr.id_product
             INNER JOIN {$p}feature_product ft ON ft.id_product = pr.id_product AND ft.id_feature = " . (int) $ids['tipo'] . " AND ft.id_feature_value = {$idTipo}
             INNER JOIN {$p}feature_product fm ON fm.id_product = pr.id_product AND fm.id_feature = " . (int) $ids['mascota'] . '
                   AND fm.id_feature_value IN (' . implode(',', $idsMascota) . ")
             LEFT JOIN {$p}feature_product fn ON fn.id_product = pr.id_product AND fn.id_feature = " . (int) $ids['necesidad'] . "
             LEFT JOIN {$p}feature_value_lang fnl ON fnl.id_feature_value = fn.id_feature_value AND fnl.id_lang = {$idLang}
             LEFT JOIN {$p}feature_product fz ON fz.id_product = pr.id_product AND fz.id_feature = " . (int) $ids['tamano'] . "
             LEFT JOIN {$p}feature_value_lang fzl ON fzl.id_feature_value = fz.id_feature_value AND fzl.id_lang = {$idLang}
             WHERE " . implode(' AND ', $donde) . '
             GROUP BY pr.id_product, pr.id_manufacturer
             HAVING distancia IS NOT NULL
             ORDER BY distancia ASC, ventas DESC, pr.id_product ASC
             LIMIT ' . (self::CANDIDATOS_A_MIRAR * 2)
        ) ?: [];

        $pistasLinea = KcsPistas::de($nombreLinea);
        $salida = [];
        foreach ($filas as $f) {
            // La misma patología, exacta. Y si además coincide todo lo clínico de la ficha, primero.
            $necesidades = array_values(array_filter(explode('|', (string) ($f['necesidades'] ?? ''))));
            if (self::patologiasDe($necesidades, $perfil['dieta']) !== $perfil['patologias']) {
                continue;
            }
            $exacta = self::clinicas($necesidades, $perfil['dieta']) === $perfil['todas'] ? 0 : 1;
            // El tema: para lo mismo primero, para nada en concreto después, para otra cosa el último.
            $tema = KcsPistas::ordenPorTema($perfil['temas'], KcsPistas::temas((string) $f['nombre'], $necesidades));
            // Para los dos animales: que sirva a los dos. Y que el nombre no diga el otro animal.
            $mascotas = array_map('intval', array_filter(explode(',', (string) ($f['mascotas'] ?? ''))));
            if (count($idsMascota) === 2 && count(array_intersect($idsMascota, $mascotas)) < 2) {
                continue;
            }
            $pistas = KcsPistas::de((string) $f['nombre']);
            if (count($perfil['mascotas']) === 1 && in_array($pistas['animal'], ['Perro', 'Gato'], true) && $pistas['animal'] !== $perfil['mascotas'][0]) {
                continue;
            }
            // Lo que dicen los nombres de la edad (un «Puppy» marcado «Todas las edades»).
            if (!KcsPistas::encajan(['animal' => $perfil['mascotas'][0], 'edad' => $pistasLinea['edad'], 'enfermedades' => []], ['animal' => '', 'edad' => $pistas['edad'], 'enfermedades' => []])) {
                continue;
            }
            // Cuántas categorías de la tienda comparte con la línea (la principal, doble), y cuántos rasgos
            // de la ficha (opción nutricional, ingrediente, textura) no coinciden.
            $categorias = array_map('intval', array_filter(explode(',', (string) ($f['categorias'] ?? ''))));
            $categoria = count(array_intersect($afin['categorias'], $categorias))
                + ((int) $f['categoria'] > 0 && (int) $f['categoria'] === $afin['defecto'] ? 2 : 0);
            $rasgosC = array_map('intval', array_filter(explode(',', (string) ($f['rasgos'] ?? ''))));
            $afinidad = 0;
            foreach ($afin['rasgos'] as $deLinea) {
                if ($deLinea !== [] && array_intersect($deLinea, $rasgosC) === []) {
                    ++$afinidad;
                }
            }
            $salida[(int) $f['id_product']] = [
                'id_manufacturer' => (int) $f['id_manufacturer'],
                'marca' => (string) ($f['marca'] ?? ''),
                'nombre' => (string) $f['nombre'],
                'link_rewrite' => (string) $f['link_rewrite'],
                'id_image' => (int) ($f['id_image'] ?? 0),
                'ventas' => (int) $f['ventas'],
                'tamanos' => (string) ($f['tamanos'] ?? ''),
                'tema' => $tema,
                'exacta' => $exacta,
                'categoria' => $categoria,
                'afinidad' => $afinidad,
            ];
            if (count($salida) >= self::CANDIDATOS_A_MIRAR) {
                break;
            }
        }

        return $salida;
    }

    /**
     * Lo de la línea con lo que se mide «que sea más o menos lo mismo»: su categoría principal, sus
     * categorías de la tienda que dicen algo (las de «Comprar por mascota» a partir del cuarto nivel:
     * «Comida perro», «Pienso perro», «Pienso natural perros»…, nunca las de marca) y los valores de la
     * ficha de opción nutricional, ingrediente principal y textura.
     *
     * @return array{defecto: int, categorias: int[], rasgos: array<string, int[]>}
     */
    private static function afinidad(int $idProducto, array $ids): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $idTienda = (int) Context::getContext()->shop->id;
        $defecto = (int) $db->getValue("SELECT id_category_default FROM {$p}product_shop WHERE id_product = {$idProducto} AND id_shop = {$idTienda}");
        $categorias = array_map('intval', array_column($db->executeS("SELECT id_category FROM {$p}category_product WHERE id_product = {$idProducto}") ?: [], 'id_category'));
        $categorias = array_values(array_intersect($categorias, self::funcionales()));

        $rasgos = ['opcion' => [], 'sabor' => [], 'textura' => []];
        $cuales = array_filter(array_map('intval', [$ids['opcion'] ?? 0, $ids['sabor'] ?? 0, $ids['textura'] ?? 0]));
        if ($cuales !== []) {
            $filas = $db->executeS("SELECT id_feature, id_feature_value FROM {$p}feature_product WHERE id_product = {$idProducto} AND id_feature IN (" . implode(',', $cuales) . ')') ?: [];
            foreach ($filas as $f) {
                foreach (['opcion', 'sabor', 'textura'] as $clave) {
                    if ((int) $f['id_feature'] === (int) ($ids[$clave] ?? 0)) {
                        $rasgos[$clave][] = (int) $f['id_feature_value'];
                    }
                }
            }
        }

        return ['defecto' => $defecto, 'categorias' => $categorias, 'rasgos' => $rasgos];
    }

    /**
     * Las categorías que dicen qué es un producto: las de «Comprar por mascota» del cuarto nivel en
     * adelante. Las de «Comprar por marca» no: que dos productos sean de la misma marca no los hace
     * parecidos. Una consulta por petición.
     *
     * @return int[]
     */
    private static function funcionales(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $p = _DB_PREFIX_;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT c.id_category
             FROM {$p}category c
             INNER JOIN {$p}category raiz ON raiz.id_category = (SELECT cl.id_category FROM {$p}category_lang cl WHERE cl.link_rewrite = 'comprar-por-mascota' ORDER BY cl.id_category ASC LIMIT 1)
             WHERE c.nleft > raiz.nleft AND c.nright < raiz.nright AND c.level_depth >= raiz.level_depth + 2"
        ) ?: [];

        return $cache = array_map('intval', array_column($filas, 'id_category'));
    }

    /**
     * Los formatos que se venden de verdad de cada candidato (stock para la cantidad, activos), con su
     * precio de tarifa y su peso, y solo los de precio de tarifa entre `desde` y `hasta` cuando se dan.
     * El precio exacto, con impuestos y promociones, se calcula después y solo para los que hacen falta.
     *
     * @param int[] $ids
     *
     * @return array<int, array<int, array{ipa: int, precio_base: float, peso: float, talla: string}>>
     */
    private static function formatos(array $ids, int $cantidad, float $desde = 0.0, float $hasta = 0.0): array
    {
        if ($ids === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $ctx = Context::getContext();
        $idTienda = (int) $ctx->shop->id;
        $idLang = (int) $ctx->language->id;
        $banda = $hasta > 0
            ? ' AND (pa.price + pr.price) BETWEEN ' . number_format(max(0.0, $desde), 6, '.', '') . ' AND ' . number_format($hasta, 6, '.', '')
            : '';
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pa.id_product, pa.id_product_attribute, (pa.price + pr.price) AS precio_base, (pa.weight + pr.weight) AS peso,
                    (SELECT GROUP_CONCAT(al.name SEPARATOR ' ') FROM {$p}product_attribute_combination pac
                     INNER JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idLang}
                     WHERE pac.id_product_attribute = pa.id_product_attribute) AS nombre
             FROM {$p}product_attribute pa
             INNER JOIN {$p}product pr ON pr.id_product = pa.id_product
             INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute
                   AND sa.id_shop = {$idTienda} AND sa.quantity >= " . max(1, $cantidad) . '
             WHERE pa.id_product IN (' . implode(',', array_map('intval', $ids)) . ")
               AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                               WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0){$banda}
             ORDER BY pa.id_product, pa.weight, pa.price"
        ) ?: [];
        $salida = [];
        foreach ($filas as $f) {
            $salida[(int) $f['id_product']][] = [
                'ipa' => (int) $f['id_product_attribute'],
                'precio_base' => (float) $f['precio_base'],
                'peso' => (float) $f['peso'],
                'talla' => KcsPistas::talla((string) ($f['nombre'] ?? '')),
            ];
        }

        return $salida;
    }

    /** Lo que se paga por un formato: con impuestos y con las promociones de hoy. 0 si no se sabe. */
    private static function precio(int $idProducto, int $ipa): float
    {
        try {
            return round((float) Product::getPriceStatic($idProducto, true, $ipa), 2);
        } catch (Throwable $e) {
            return 0.0;
        }
    }

    /**
     * El formato de un candidato que mejor se parece al de la línea, y si es de precio parecido o lejano.
     *
     * Se compara **lo que se paga** (`precio`, con impuestos y promociones) con lo que vale la línea: el
     * formato de precio más cercano (a un pack de 10 latas de 25 €, un pack de latas de 18–30 €, no una
     * lata suelta de 3 €). Primero se busca uno de **precio parecido**: entre el mínimo y el máximo del
     * precio de la línea y, cuando los dos tienen peso, que no salga más caro **por kilo** que el máximo
     * (más barato por kilo sí vale: es mejor compra, no peor). Si no lo hay, el más cercano dentro de la
     * banda «como mucho», con la misma red por kilo a esa escala: sale marcado como no parecido y va
     * después de los parecidos. Fuera de todo, nada.
     *
     * La banda «como mucho» llega hasta el máximo en porcentaje o hasta `lejosEuros` más, lo que sea mayor:
     * a un snack de 1,55 € el 400 % son 6 €, y con 10 € más llega a 11,55 €.
     *
     * Y la talla: si la línea es para perros grandes y el candidato viene en tallas (S, M, L), los formatos
     * de otra talla van después de los de la suya o sin talla (si solo hay S con stock, S).
     *
     * En comida (con peso en los dos), una última red: el formato que cuesta lo mismo **por kilo** aunque sea
     * de otro tamaño (a una lata suelta de 4,70 €, un pack de seis tarrinas de 26 € al mismo precio por kilo)
     * entra como lejano. Fuera de todo, nada.
     *
     * @param array<int, array{ipa: int, precio: float, peso: float, talla?: string}> $formatos
     * @param string $talla la de la línea ('pequeño', 'mediano', 'grande' o '')
     *
     * @return array{ipa: int, precio: float, distancia: float, parecido: bool}|null
     */
    public static function formatoParecido(array $formatos, float $precioLinea, float $peso, float $minimo, float $maximo, float $lejosMin = 0.0, float $lejosMax = 0.0, string $talla = '', float $lejosEuros = 0.0): ?array
    {
        if ($formatos === [] || $precioLinea <= 0) {
            return null;
        }
        $formatos = array_values(array_filter($formatos, static fn (array $f): bool => (float) ($f['precio'] ?? 0) > 0));
        $otraTalla = static fn (array $f): int => $talla !== '' && (string) ($f['talla'] ?? '') !== '' && $f['talla'] !== $talla ? 1 : 0;
        usort($formatos, static fn (array $a, array $b): int => [$otraTalla($a), abs($a['precio'] - $precioLinea)] <=> [$otraTalla($b), abs($b['precio'] - $precioLinea)]);
        $porKilo = $peso > 0 ? $precioLinea / $peso : 0.0;
        $salida = static fn (array $f, bool $parecido): array => ['ipa' => (int) $f['ipa'], 'precio' => (float) $f['precio'], 'distancia' => round(abs((float) $f['precio'] - $precioLinea) / $precioLinea, 4), 'parecido' => $parecido];

        $bandas = [[$minimo, $maximo, true]];
        $techo = max($maximo, $lejosMax, $lejosEuros > 0 ? ($precioLinea + $lejosEuros) / $precioLinea : 0.0);
        $suelo = min($minimo, $lejosMin > 0 ? $lejosMin : $minimo);
        if ($techo > $maximo || $suelo < $minimo) {
            $bandas[] = [$suelo, $techo, false];
        }
        foreach ($bandas as [$desde, $hasta, $parecido]) {
            foreach ($formatos as $f) {
                $precio = (float) $f['precio'];
                if ($precio < $precioLinea * $desde || $precio > $precioLinea * $hasta) {
                    continue;
                }
                if ($porKilo > 0 && (float) $f['peso'] > 0 && $precio / (float) $f['peso'] > $porKilo * $hasta) {
                    continue;
                }

                return $salida($f, $parecido);
            }
        }
        // El mismo precio por kilo en otro tamaño de formato.
        if ($porKilo > 0) {
            foreach ($formatos as $f) {
                if ((float) $f['peso'] <= 0) {
                    continue;
                }
                $suyo = (float) $f['precio'] / (float) $f['peso'];
                if ($suyo >= $porKilo * $minimo && $suyo <= $porKilo * $maximo) {
                    return $salida($f, false);
                }
            }
        }

        return null;
    }

    /**
     * Variedad en lo que se ve: entre las primeras tarjetas, como mucho dos de la misma marca, si hay otra
     * marca igual de buena (mismo tema y misma banda de precio) más atrás. Siete latas de Farmina de sabores
     * distintos son una sugerencia, no siete. El resto de la lista no cambia de orden.
     *
     * @param array<int, array{id_manufacturer: int, tema: int, parecido: bool}> $elegidos ya ordenados
     */
    public static function variar(array $elegidos, int $tarjetas): array
    {
        $salida = [];
        $porMarca = [];
        $resto = array_values($elegidos);
        while ($resto !== [] && count($salida) < $tarjetas) {
            $e = array_shift($resto);
            $marca = (int) $e['id_manufacturer'];
            if (($porMarca[$marca] ?? 0) >= 2) {
                // Una tercera de la misma marca: si hay otra marca igual de buena más atrás, esa primero.
                foreach ($resto as $i => $otra) {
                    if ((int) $otra['id_manufacturer'] !== $marca && (int) $otra['tema'] === (int) $e['tema'] && (bool) $otra['parecido'] === (bool) $e['parecido']) {
                        unset($resto[$i]);
                        $resto = array_values($resto);
                        array_unshift($resto, $e);
                        $e = $otra;
                        $marca = (int) $e['id_manufacturer'];
                        break;
                    }
                }
            }
            $porMarca[$marca] = ($porMarca[$marca] ?? 0) + 1;
            $salida[] = $e;
        }

        return array_merge($salida, $resto);
    }

    /** Las marcas preferidas (ids) para las dietas, en su orden. Para lo demás, ninguna. @return int[] */
    private static function preferidas(string $tipo, array $nombres): array
    {
        if (!in_array($tipo, self::DIETAS, true) || $nombres === []) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (string $n): int => KcsDistribuidor::porNombre(trim($n)), $nombres)));
    }

    /**
     * Lo que necesita la plantilla: precio (ya exacto), diferencia con lo que paga la línea, formato,
     * foto, enlaces.
     */
    private static function vestir(array $elegidos, array $linea): array
    {
        $ctx = Context::getContext();
        $idLang = (int) $ctx->language->id;
        try {
            $locale = $ctx->getCurrentLocale();
        } catch (Throwable $e) {
            // En consola (las pruebas) no hay contenedor: el importe se escribe a mano.
            $locale = null;
        }
        $moneda = $ctx->currency->iso_code ?? '';
        $euros = static fn (float $v): string => $locale && $moneda !== ''
            ? $locale->formatPrice($v, $moneda)
            : number_format($v, 2, ',', '.') . ' €';
        $cantidad = max(1, (int) $linea['cantidad']);
        $precioLinea = (float) ($linea['precio'] ?? 0);

        $salida = [];
        foreach ($elegidos as $e) {
            $precio = (float) $e['precio'];
            if ($precio <= 0) {
                continue;
            }
            $diferencia = round(($precio - $precioLinea) * $cantidad, 2);
            $salida[] = [
                'id_product' => $e['id_product'],
                'id_product_attribute' => $e['id_product_attribute'],
                'id_manufacturer' => $e['id_manufacturer'],
                'marca' => $e['marca'],
                'marca_url' => self::urlMarca($e['id_manufacturer'], $e['marca']),
                'nombre' => $e['nombre'],
                'formato' => KcsBloque::formato($e['id_product_attribute'], $idLang),
                'precio' => $precio,
                'precio_txt' => $euros($precio),
                'diferencia' => $diferencia,
                'diferencia_txt' => abs($diferencia) < 0.005 ? '' : ($diferencia > 0 ? '+' : '−') . $euros(abs($diferencia)),
                'parecido' => (bool) $e['parecido'],
                'tema' => (int) $e['tema'],
                'categoria' => (int) $e['categoria'],
                'afinidad' => (int) $e['afinidad'],
                'foto' => $e['id_image'] > 0 ? (string) $ctx->link->getImageLink($e['link_rewrite'], $e['id_image'], 'small_default') : '',
                'url' => (string) $ctx->link->getProductLink($e['id_product'], null, null, null, null, null, $e['id_product_attribute']),
            ];
        }

        return $salida;
    }

    /**
     * La página de la marca (`/marcas/<marca>`, de `kpymanufacturer`): el chip lleva ahí. Si la marca no
     * tiene su dirección propia, a la página de marca de PrestaShop.
     */
    public static function urlMarca(int $idMarca, string $nombre): string
    {
        static $cache = [];
        if (isset($cache[$idMarca])) {
            return $cache[$idMarca];
        }
        $link = Context::getContext()->link;
        $rewrite = '';
        try {
            if (method_exists('Manufacturer', 'getLinkRewriteById')) {
                $rewrite = (string) Manufacturer::getLinkRewriteById($idMarca);
            }
        } catch (Throwable $e) {
            $rewrite = '';
        }

        return $cache[$idMarca] = $rewrite !== ''
            ? $link->getBaseLink() . 'marcas/' . $rewrite
            : (string) $link->getManufacturerLink($idMarca, Tools::str2url($nombre));
    }

    /**
     * «Productos de estas marcas no retrasan tu pedido»: las marcas del almacén de referencia, empezando por
     * las de las alternativas que se enseñan, hasta las que se pidan. El resto sale de un mapa marca →
     * almacén que se rehace cada seis horas con la regla de Julián (una pregunta por marca, con su producto
     * más vendido), para no preguntar por cien marcas en cada cesta.
     *
     * @param array<int, array{id_manufacturer: int, marca: string, marca_url: string}> $alternativas
     * @param string[] $mascotas las de la línea («Perro», «Gato»): solo marcas con productos para ellas
     *
     * @return array<int, array{nombre: string, url: string}>
     */
    public static function marcas(string $almacen, int $idCesta, array $alternativas, int $cuantas, array $mascotas = [], bool $comida = true): array
    {
        $salida = [];
        $vistas = [];
        foreach ($alternativas as $a) {
            $id = (int) $a['id_manufacturer'];
            if ($id <= 0 || isset($vistas[$id]) || (string) $a['marca'] === '') {
                continue;
            }
            $vistas[$id] = true;
            $salida[] = ['nombre' => (string) $a['marca'], 'url' => (string) $a['marca_url']];
        }
        foreach (self::mapaMarcas($idCesta) as $id => $m) {
            if (count($salida) >= $cuantas) {
                break;
            }
            if ($m['almacen'] !== $almacen || isset($vistas[(int) $id])) {
                continue;
            }
            if ($mascotas !== [] && array_intersect($mascotas, (array) ($m['mascotas'] ?? [])) === []) {
                continue;
            }
            // A una línea de comida, marcas que tengan comida o snacks: Zotal no retrasa, pero no es la alternativa a una lata.
            if ($comida && array_intersect(array_merge(KcsSugerencias::COMIDA, ['Snacks']), (array) ($m['tipos'] ?? [])) === []) {
                continue;
            }
            $vistas[(int) $id] = true;
            $salida[] = ['nombre' => (string) $m['nombre'], 'url' => self::urlMarca((int) $id, (string) $m['nombre'])];
        }

        return array_slice($salida, 0, max(0, $cuantas));
    }

    /**
     * Marca → almacén, de las marcas con al menos cinco productos con stock, por ventas. Se guarda en la
     * configuración con su hora y se rehace cada seis horas.
     *
     * @return array<int, array{nombre: string, almacen: string, mascotas: string[], tipos: string[]}>
     */
    private static function mapaMarcas(int $idCesta): array
    {
        $guardado = json_decode((string) Configuration::get('KCS_CESTA_MAPA'), true);
        if (is_array($guardado) && (int) ($guardado['hora'] ?? 0) > time() - 6 * 3600 && is_array($guardado['marcas'] ?? null)) {
            return $guardado['marcas'];
        }
        $p = _DB_PREFIX_;
        $ctx = Context::getContext();
        $idTienda = (int) $ctx->shop->id;
        $idLang = (int) $ctx->language->id;
        $ids = KcsSugerencias::ids();
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pr.id_manufacturer, MAX(ma.name) AS nombre, COUNT(DISTINCT pr.id_product) AS n,
                    SUBSTRING_INDEX(GROUP_CONCAT(pr.id_product ORDER BY COALESCE(vs.quantity, 0) DESC), ',', 1) AS mejor,
                    GROUP_CONCAT(DISTINCT ml.value) AS mascotas,
                    GROUP_CONCAT(DISTINCT tl.value SEPARATOR '|') AS tipos
             FROM {$p}product pr
             INNER JOIN {$p}product_shop ps ON ps.id_product = pr.id_product AND ps.id_shop = {$idTienda} AND ps.active = 1
             INNER JOIN {$p}manufacturer ma ON ma.id_manufacturer = pr.id_manufacturer AND ma.active = 1
             LEFT JOIN {$p}product_sale vs ON vs.id_product = pr.id_product
             LEFT JOIN {$p}feature_product fm ON fm.id_product = pr.id_product AND fm.id_feature = " . (int) $ids['mascota'] . "
             LEFT JOIN {$p}feature_value_lang ml ON ml.id_feature_value = fm.id_feature_value AND ml.id_lang = {$idLang}
             LEFT JOIN {$p}feature_product ft ON ft.id_product = pr.id_product AND ft.id_feature = " . (int) $ids['tipo'] . "
             LEFT JOIN {$p}feature_value_lang tl ON tl.id_feature_value = ft.id_feature_value AND tl.id_lang = {$idLang}
             WHERE pr.active = 1 AND pr.id_manufacturer > 0
               AND EXISTS (SELECT 1 FROM {$p}product_attribute pa
                           INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute AND sa.id_shop = {$idTienda} AND sa.quantity > 0
                           WHERE pa.id_product = pr.id_product)
             GROUP BY pr.id_manufacturer
             HAVING n >= 5
             ORDER BY SUM(COALESCE(vs.quantity, 0)) DESC
             LIMIT 40"
        ) ?: [];
        $vetadas = KcsSugerencias::vetadas();
        $marcas = [];
        foreach ($filas as $f) {
            $idMarca = (int) $f['id_manufacturer'];
            if (in_array($idMarca, $vetadas, true)) {
                continue;
            }
            $idProducto = (int) $f['mejor'];
            $ipa = (int) Product::getDefaultAttribute($idProducto);
            $almacen = KcsAlmacenes::deProducto($idProducto, $ipa, $idMarca);
            if ($almacen === null) {
                continue;
            }
            $marcas[$idMarca] = [
                'nombre' => (string) $f['nombre'],
                'almacen' => $almacen,
                'mascotas' => array_values(array_filter(explode(',', (string) ($f['mascotas'] ?? '')))),
                'tipos' => array_values(array_filter(explode('|', (string) ($f['tipos'] ?? '')))),
            ];
        }
        if ($marcas !== []) {
            Configuration::updateValue('KCS_CESTA_MAPA', json_encode(['hora' => time(), 'marcas' => $marcas], JSON_UNESCAPED_UNICODE));
        }

        return $marcas;
    }
}
