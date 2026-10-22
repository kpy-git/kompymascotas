<?php
/**
 * Qué se le ofrece a quien está mirando un producto, y **en qué orden**.
 *
 * Kompy no tenía una tabla de productos relacionados (la de PrestaShop está vacía y la del web
 * antiguo se quedó vieja), pero el catálogo sí sabe de cada producto su marca, su mascota, su tipo,
 * su edad, su tamaño, su **patología** y su **sabor**. Con eso se arman los tres huecos:
 *
 *   HUECO 1  Lo que de verdad acompaña al pienso:
 *            a) lo que esté puesto a mano para ese producto, categoría o marca;
 *            b) el **húmedo clínico de Boske** si comparte patología (es nuestra marca y va primero
 *               en todas las demás, siempre que tenga stock; además se salta el tope de precio);
 *            c) el húmedo de su propia marca, de la misma patología y, a ser posible, del mismo sabor;
 *            d) el húmedo de las marcas de reserva, por orden (distinto en perro y en gato).
 *   HUECO 2  Otra variante: otro sabor de la misma marca, u otra marca si va por patología. Si en el
 *            1 entró Boske, aquí entra el húmedo de su propia marca.
 *   HUECO 3  Por este orden: el **suplemento** de esa patología (articulaciones, renal, urinario…),
 *            el **snack funcional** de esa misma patología, y solo entonces un snack **natural**.
 *
 * Y las reglas que no se saltan, que son las que hacen que el bloque no moleste: **con stock**,
 * **nunca del mismo tipo** que el principal (a un pienso no se le ofrece otro pienso), **barato**
 * (con la excepción de Boske, que manda), sin cruzar enfermedades ni edades, y **del mismo
 * distribuidor**, porque mezclarlos parte el pedido en dos envíos (ver `KcsDistribuidor`).
 */
class KcsSugerencias
{
    /**
     * Qué tipo de producto se le ofrece a cada tipo. Por nombre, que es lo que no cambia.
     *
     * A quien compra comida **no se le ofrece higiene**: un limpiador de ojos al lado de un pienso
     * no vende y además queda raro. La higiene solo acompaña a la higiene, a los accesorios y a la
     * arena, que es donde tiene sentido.
     */
    public const COMPLEMENTOS = [
        'Comida Seca' => ['Comida Húmeda', 'Snacks', 'Complemento Nutricional', 'Leche'],
        'Dieta Veterinaria Seca' => ['Dieta Veterinaria Húmedo', 'Comida Húmeda', 'Complemento Nutricional'],
        'Comida Húmeda' => ['Snacks', 'Complemento Nutricional'],
        'Dieta Veterinaria Húmedo' => ['Dieta Veterinaria Seca', 'Complemento Nutricional'],
        'Comida Semi-Húmeda' => ['Snacks', 'Complemento Nutricional'],
        'Snacks' => ['Snacks', 'Educación y Adiestramiento', 'Complemento Nutricional'],
        'Accesorios' => ['Higiene', 'Snacks'],
        'Higiene' => ['Higiene', 'Snacks'],
        'Antiparasitario' => ['Higiene', 'Complemento Nutricional'],
        'Complemento Nutricional' => ['Snacks', 'Comida Húmeda'],
        'Arenas y Suministros para Gatos' => ['Higiene', 'Snacks'],
    ];

    /** Los tipos que son comida: a estos no se les ofrece higiene en el tercer hueco. */
    public const COMIDA = [
        'Comida Seca', 'Comida Húmeda', 'Comida Semi-Húmeda', 'Leche',
        'Dieta Veterinaria Seca', 'Dieta Veterinaria Húmedo',
    ];

    /**
     * Las «Necesidades específicas» que son de verdad una **patología**.
     *
     * Es la regla que más falta hacía: a un gato sano no se le ofrece comida para diabéticos, y a
     * uno con problemas renales no se le ofrece una de hígado. Las demás necesidades de la lista
     * (esterilizado, control de peso, alta actividad, vitaminas, sabor, bolas de pelo, estrés)
     * son de perfil, no de enfermedad, y esas sí pueden cruzarse.
     *
     * Lo dental no está: un snack dental es prevención y vale para cualquiera (Rafa lo quiere en el
     * tercer hueco). Las dietas dentales siguen sin cruzarse, porque una dieta veterinaria solo se
     * ofrece si comparte necesidad (ver `encaja()`).
     */
    public const PATOLOGIAS = [
        'Problemas alérgicos e intolerancias',
        'Problemas gastrointestinales',
        'Problemas urinarios',
        'Problemas renales',
        'Problemas hepáticos e hígado',
        'Problemas cardiacos y corazón',
        'Problemas de pelo y piel',
        'Movilidad y articulaciones',
        'Diabetes',
        'Leismania',
        'Recuperación',
        'Función cerebral',
        'Sobrepeso',
    ];

    /**
     * Las edades, por etapa de la vida. A un pienso de adulto no se le ofrece húmedo de cachorro,
     * y al revés tampoco. «Todas las edades» vale para cualquiera.
     */
    public const ETAPAS = [
        'joven' => ['Cachorro', 'Gatito', 'Junior'],
        'adulto' => ['Adulto'],
        'mayor' => ['Senior', 'Mature'],
    ];

    /** Los tipos que cuentan como «comida húmeda» para el hueco 1 y el 2. */
    public const HUMEDOS = ['Comida Húmeda', 'Dieta Veterinaria Húmedo', 'Comida Semi-Húmeda'];

    /** El hueco 3: primero el complemento de su patología, y si no, capricho o cuidado. */
    public const REFUERZOS = ['Complemento Nutricional'];
    public const CUIDADOS = ['Snacks', 'Higiene', 'Educación y Adiestramiento'];

    /**
     * Marcas que **no se sugieren nunca**, en ningún hueco: genéricas o poco naturales, que no son la
     * tienda que queremos enseñar. Trixie, por genérica; Edgard & Cooper, porque no es una marca que
     * queramos recomendar. Siguen pudiendo salir si alguien las pone a mano en el back office.
     */
    public const NUNCA = ['Trixie', 'Edgard & Cooper'];

    /**
     * Las marcas de reserva cuando la del producto no tiene húmedo, por orden de importancia.
     * En gato manda Schesir, que es la que más variedad tiene (121 referencias frente a 23 y 12).
     */
    /**
     * Las marcas que se pueden ofrecer con el producto que se está mirando: las de su mismo
     * distribuidor. Se fija al empezar `para()` y la usan todas las búsquedas.
     *
     * @var int[]
     */
    private static array $deSuDistribuidor = [];

    /**
     * Lo que dicen el nombre y la categoría del producto que se mira (animal, edad, enfermedad), y
     * las enfermedades de su ficha. Se fijan al empezar `para()`.
     *
     * @var array{animal: string, edad: string, enfermedades: string[]}
     */
    private static array $pistas = ['animal' => '', 'edad' => '', 'enfermedades' => []];

    /** @var string[] */
    private static array $enfermedadesFicha = [];

    /** 'Perro', 'Gato', 'ambos' (de los dos y el nombre no dice cuál) o '' (uno solo, por ficha). */
    private static string $animal = '';

    /**
     * Los ids de **todas** las necesidades del producto que se mira. Un z/d es de alergia y de piel:
     * con solo la primera, se descartaban los demás z/d por llevar también «piel».
     *
     * @var int[]
     */
    private static array $necesidadesSuyas = [];

    /**
     * Las enfermedades del producto que se mira (de su ficha, solo las que son patología, y de su
     * nombre), en las claves de `KcsPistas`.
     *
     * @var string[]
     */
    private static array $enfermedadesSuyas = [];

    private const CARACTERISTICAS = [
        'tipo' => 'Tipo de Producto',
        'mascota' => 'Mascota',
        'edad' => 'Edad',
        'tamano' => 'Tamaño',
        'necesidad' => 'Necesidades específicas',
        'opcion' => 'Opción nutricional',
        'sabor' => 'Ingrediente principal',
    ];

    /**
     * Las tres sugerencias de un producto, en su orden.
     *
     * @param array{id_product: int, id_manufacturer: int, precio: float} $producto
     * @param array{limite?: int, parte?: float, tope?: float} $ajustes
     *
     * @return array<int, array{id_product: int, origen: string}>
     */
    public static function para(array $producto, array $ajustes = []): array
    {
        $idProducto = (int) ($producto['id_product'] ?? 0);
        if ($idProducto <= 0) {
            return [];
        }
        $cuantos = max(1, (int) ($ajustes['limite'] ?? 3));
        $techo = self::techo((float) ($producto['precio'] ?? 0), $ajustes);
        $rasgos = self::rasgos($idProducto);
        $marca = (int) ($producto['id_manufacturer'] ?? 0);

        // De dónde puede salir lo que se ofrece: del mismo distribuidor que el producto que se mira,
        // porque mezclarlos parte el pedido en dos envíos. Vale para todas las búsquedas de abajo.
        self::$deSuDistribuidor = KcsDistribuidor::compatibles($marca);

        // Lo que dicen su nombre y su categoría, para la segunda red de `encaja()`.
        $p = _DB_PREFIX_;
        $idLang = (int) Context::getContext()->language->id;
        $texto = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            "SELECT pl.name, cl.name AS categoria
             FROM {$p}product pr
             INNER JOIN {$p}product_lang pl ON pl.id_product = pr.id_product AND pl.id_lang = {$idLang}
             LEFT JOIN {$p}category_lang cl ON cl.id_category = pr.id_category_default AND cl.id_lang = {$idLang}
             WHERE pr.id_product = {$idProducto}"
        ) ?: ['name' => '', 'categoria' => ''];
        self::$pistas = KcsPistas::de((string) $texto['name'], (string) ($texto['categoria'] ?? ''));
        self::$enfermedadesFicha = KcsPistas::desdeFicha($rasgos['necesidades']);
        $valores = self::ids()['valores'];
        self::$necesidadesSuyas = array_values(array_filter(array_map(
            static fn (string $n): int => (int) ($valores[$n] ?? 0),
            $rasgos['necesidades']
        )));
        self::$enfermedadesSuyas = array_values(array_unique(array_merge(
            self::$pistas['enfermedades'],
            KcsPistas::desdeFicha(array_values(array_intersect($rasgos['necesidades'], self::PATOLOGIAS)))
        )));

        // Los regalos de promoción no son una ficha que se venda: nada que ofrecer.
        if ($rasgos['tipo'] === 'Regalo' || stripos((string) $texto['name'], 'Regalo') === 0) {
            return [];
        }
        // Sin «Tipo de Producto» en la ficha, el que digan el nombre o la categoría.
        if ($rasgos['tipo'] === '') {
            $rasgos['tipo'] = KcsPistas::tipo((string) $texto['name'], (string) ($texto['categoria'] ?? ''));
        }

        // La mascota. Para cruzar solo cuentan perro y gato: un champú de perro y caballo es de perro,
        // diga lo que diga su categoría. Nunca algo de gato para quien puede tener perro.
        //   - de los dos: si el nombre dice cuál, de ese; si no, solo lo que sirva a los dos;
        //   - sin mascota en la ficha: la que digan el nombre o la categoría.
        self::$animal = '';
        $suyas = array_values(array_intersect(['Perro', 'Gato'], $rasgos['mascotas']));
        if ($rasgos['mascotas'] === []) {
            $suyas = self::$pistas['animal'] === 'ambos' ? ['Perro', 'Gato'] : array_values(array_filter([self::$pistas['animal']]));
        }
        if (count($suyas) === 2) {
            // Solo el **nombre** puede elegir uno. La categoría no: un acondicionador de perro y gato
            // colgado en «Peluquería gatos» sigue siendo también de perro.
            $porNombre = KcsPistas::de((string) $texto['name'])['animal'];
            self::$animal = in_array($porNombre, ['Perro', 'Gato'], true) ? $porNombre : 'ambos';
            $rasgos['mascota'] = self::$animal === 'ambos' ? 'Perro' : self::$animal;
        } elseif (count($suyas) === 1) {
            $rasgos['mascota'] = $suyas[0];
            // De perro y de otro animal que no es gato (caballo, roedor): se busca para el perro.
            self::$animal = count(array_unique($rasgos['mascotas'])) > 1 ? $suyas[0] : '';
        }

        $huecos = [];
        // Lo vetado no sale: ni del catálogo, ni del relleno, ni aunque sea de Boske.
        $fuera = array_merge([$idProducto], self::vetados($idProducto, $marca));

        // Lo puesto a mano manda, y manda **en su posición**: si alguien quiere una crema de
        // almohadillas la primera, baja a las demás.
        $aMano = self::aMano($idProducto, $marca);

        // Los antiparasitarios, entre ellos: al collar, pastilla y pipeta, y al revés, con pesos que
        // encajen y del distribuidor que sea (ver `KcsAntiparasitarios`). Lo que falte, como siempre.
        if ($rasgos['tipo'] === 'Antiparasitario' && $aMano === [] && self::$animal !== 'ambos'
            && in_array($rasgos['mascota'], ['Perro', 'Gato'], true)) {
            // Con las mismas redes que todo lo demás (animal, patología, edad, vetadas), pero sin
            // distribuidor ni techo de precio: la protección completa se compra junta.
            $propio = self::$deSuDistribuidor;
            self::$deSuDistribuidor = [];
            $candidatos = self::buscar([
                'marcas' => [], 'tipos' => ['Antiparasitario'], 'mascota' => $rasgos['mascota'],
                'necesidad' => '', 'edad' => $rasgos['edad'], 'tamano' => '', 'obligatoria' => false, 'sabor' => '',
                'fuera' => $fuera, 'techo' => 0.0, 'cuantos' => 40,
            ]);
            self::$deSuDistribuidor = $propio;
            foreach (KcsAntiparasitarios::ordenar($idProducto, (string) $texto['name'], $rasgos['mascota'], $candidatos, $cuantos) as $id) {
                $huecos[] = ['id_product' => $id, 'origen' => 'antiparasitario'];
                $fuera[] = $id;
            }
        }

        for ($posicion = count($huecos) + 1; $posicion <= $cuantos; ++$posicion) {
            if (isset($aMano[$posicion])) {
                foreach ($aMano[$posicion] as $id) {
                    if (!in_array($id, $fuera, true) && self::sirve($id, $techo, true)) {
                        $huecos[] = ['id_product' => $id, 'origen' => 'mano'];
                        $fuera[] = $id;
                        continue 2;
                    }
                }
            }
            $elegido = self::delHueco($posicion, $rasgos, $marca, $techo, $fuera);
            if ($elegido !== null) {
                $huecos[] = $elegido;
                $fuera[] = $elegido['id_product'];
            }
        }

        // Si algún hueco se ha quedado vacío, se rellena antes que dejarlo cojo: **siempre tres**.
        // Por escalones, de lo más afín a lo menos, y sin saltarse nunca mascota, edad, patología
        // ni las marcas vetadas:
        //   1. lo de su distribuidor que complemente al producto;
        //   2. lo de su distribuidor aunque pase del precio máximo (hasta el tope en euros);
        //   3. Boske, que sale de Neftys y también de la tienda (no con Evolution);
        //   4. la tienda, como último recurso: tarda un poco más en salir, pero es mejor que un
        //      hueco vacío.
        $escalones = [
            ['techo' => $techo, 'distribuidor' => true],
            ['techo' => (float) ($ajustes['tope'] ?? 40.0), 'distribuidor' => true],
            ['techo' => 0.0, 'distribuidor' => true, 'solo' => [self::marcaPropia()]],
            ['techo' => (float) ($ajustes['tope'] ?? 40.0), 'distribuidor' => false],
        ];
        $propio = self::$deSuDistribuidor;
        foreach ($escalones as $n => $escalon) {
            if (count($huecos) >= $cuantos) {
                break;
            }
            // Evolution de momento solo se cruza consigo mismo: ni Boske ni la tienda.
            if ((isset($escalon['solo']) || !$escalon['distribuidor']) && KcsDistribuidor::soloLoSuyo($marca)) {
                continue;
            }
            if (isset($escalon['solo']) && self::marcaPropia() <= 0) {
                continue;
            }
            // El último escalón es la **tienda** (almacén propio, con Boske), no cualquier distribuidor.
            self::$deSuDistribuidor = $escalon['distribuidor'] ? $propio : KcsDistribuidor::deTienda();
            // El húmedo de cualquier marca, solo cuando ya no hay techo: antes, otro sabor de su marca
            // aunque cueste algo más (a un Gourmet, otro Gourmet antes que un Whiskas).
            $ids = self::relleno($rasgos, isset($escalon['solo']) ? $escalon['solo'][0] : $marca, $escalon['techo'], $fuera, $cuantos - count($huecos), isset($escalon['solo']), $n > 0);
            foreach ($ids as $id) {
                $huecos[] = ['id_product' => $id, 'origen' => $n === 3 ? 'tienda' : 'capricho'];
                $fuera[] = $id;
                if (count($huecos) >= $cuantos) {
                    break;
                }
            }
        }
        self::$deSuDistribuidor = $propio;

        return array_slice($huecos, 0, $cuantos);
    }

    /**
     * Lo que le toca a cada hueco.
     *
     * @param array{tipo: string, mascota: string, necesidad: string, sabor: string} $rasgos
     * @param int[] $fuera
     *
     * @return array{id_product: int, origen: string}|null
     */
    private static function delHueco(int $posicion, array $rasgos, int $marca, float $techo, array $fuera): ?array
    {
        $humedos = array_values(array_intersect(self::COMPLEMENTOS[$rasgos['tipo']] ?? [], self::HUMEDOS));
        // Al húmedo, otro húmedo (otro sabor). A lo que no es comida, nunca: un champú con latas no pega.
        $comida = $rasgos['tipo'] === '' || in_array($rasgos['tipo'], self::COMIDA, true);
        if ($humedos === [] && $posicion < 3 && $comida) {
            $humedos = self::HUMEDOS;
        }

        if ($posicion === 1) {
            // a) El húmedo clínico de Boske, si comparte patología. Se salta el tope: es la marca de
            //    la casa y la que interesa mover.
            $propia = self::marcaPropia();
            if ($propia > 0 && $rasgos['necesidad'] !== '' && $propia !== $marca) {
                $suyo = self::buscar([
                    'marcas' => [$propia], 'tipos' => self::HUMEDOS, 'mascota' => $rasgos['mascota'],
                    'necesidad' => $rasgos['necesidad'], 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'], 'obligatoria' => true, 'sabor' => '',
                    'fuera' => $fuera, 'techo' => 0.0, 'cuantos' => 1,
                ]);
                if ($suyo !== []) {
                    return ['id_product' => $suyo[0], 'origen' => 'propia'];
                }
            }
            // b) El húmedo de su propia marca, misma patología y a ser posible mismo sabor.
            if ($marca > 0) {
                $suyo = self::buscar([
                    'marcas' => [$marca], 'tipos' => $humedos, 'mascota' => $rasgos['mascota'],
                    'necesidad' => $rasgos['necesidad'], 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'], 'obligatoria' => false, 'sabor' => $rasgos['sabor'],
                    'fuera' => $fuera, 'techo' => $techo, 'cuantos' => 1,
                ]);
                if ($suyo !== []) {
                    return ['id_product' => $suyo[0], 'origen' => 'marca'];
                }
            }
            // c) Las marcas de reserva, por orden.
            $reserva = self::buscar([
                'marcas' => KcsDistribuidor::reserva($marca, $rasgos['mascota']), 'tipos' => $humedos, 'mascota' => $rasgos['mascota'],
                'necesidad' => $rasgos['necesidad'], 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'], 'obligatoria' => false, 'sabor' => $rasgos['sabor'],
                'fuera' => $fuera, 'techo' => $techo, 'cuantos' => 1,
            ]);

            return $reserva === [] ? null : ['id_product' => $reserva[0], 'origen' => 'reserva'];
        }

        if ($posicion === 2) {
            // Otra variante: otro sabor de su marca; si no, otra marca de la lista.
            if ($marca > 0) {
                $otra = self::buscar([
                    'marcas' => [$marca], 'tipos' => $humedos, 'mascota' => $rasgos['mascota'],
                    'necesidad' => $rasgos['necesidad'], 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'], 'obligatoria' => false, 'sabor' => '',
                    'fuera' => $fuera, 'techo' => $techo, 'cuantos' => 1,
                ]);
                if ($otra !== []) {
                    return ['id_product' => $otra[0], 'origen' => 'marca'];
                }
            }
            $otra = self::buscar([
                'marcas' => KcsDistribuidor::reserva($marca, $rasgos['mascota']), 'tipos' => $humedos, 'mascota' => $rasgos['mascota'],
                'necesidad' => $rasgos['necesidad'], 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'], 'obligatoria' => false, 'sabor' => '',
                'fuera' => $fuera, 'techo' => $techo, 'cuantos' => 1,
            ]);

            return $otra === [] ? null : ['id_product' => $otra[0], 'origen' => 'reserva'];
        }

        // Hueco 3, por este orden: el suplemento de su patología, el snack funcional de esa misma
        // necesidad, y solo entonces un snack natural o dental. Nunca un snack de bazar. Se prueban
        // **todas** sus necesidades, las de enfermedad primero: un z/d es de alergia y de piel.
        // El suplemento, solo a lo que lo admite: a un suplemento no se le ofrece otro de la competencia.
        $patologicas = array_values(array_intersect($rasgos['necesidades'], self::PATOLOGIAS));
        $conRefuerzo = array_intersect(self::COMPLEMENTOS[$rasgos['tipo']] ?? [], self::REFUERZOS) !== [];
        $pasos = [
            ['tipos' => self::REFUERZOS, 'necesidades' => $conRefuerzo ? $patologicas : [], 'origen' => 'refuerzo'],
            // El snack que va a esa necesidad: dental para la boca, articular para la movilidad…
            ['tipos' => ['Snacks'], 'necesidades' => array_values(array_unique(array_merge($patologicas, $rasgos['necesidades']))), 'origen' => 'funcional'],
        ];
        // El suplemento de su patología pesa más que el distribuidor (lo pidió Rafa con el Renal de
        // Royal Canin): la tienda casi no tiene suplementos, que son de farmacia y salen de Neftys y
        // Distrivet. Primero del suyo; si no, de Neftys, que es el socio principal; y si no, de
        // cualquiera. Evolution, que solo se cruza consigo mismo, no sale de lo suyo.
        $propio = self::$deSuDistribuidor;
        foreach ($pasos as $paso) {
            $alcances = [$propio];
            if ($paso['origen'] === 'refuerzo' && !KcsDistribuidor::soloLoSuyo($marca)) {
                $alcances[] = KcsDistribuidor::marcasDe('neftys');
                $alcances[] = [];
            }
            foreach ($alcances as $alcance) {
                self::$deSuDistribuidor = $alcance;
                foreach ($paso['necesidades'] as $necesidad) {
                    $hallado = self::buscar([
                        'marcas' => [], 'tipos' => $paso['tipos'], 'mascota' => $rasgos['mascota'],
                        'necesidad' => $necesidad, 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'], 'obligatoria' => true, 'sabor' => '',
                        'fuera' => $fuera, 'techo' => $techo, 'cuantos' => 1,
                    ]);
                    if ($hallado !== []) {
                        self::$deSuDistribuidor = $propio;

                        return ['id_product' => $hallado[0], 'origen' => $paso['origen']];
                    }
                }
            }
        }
        self::$deSuDistribuidor = $propio;

        // A un producto de un distribuidor que solo se cruza consigo mismo no se le ofrece un
        // capricho de fuera: mejor nada que un snack que llega en otro paquete.
        if (KcsDistribuidor::soloLoSuyo($marca)) {
            return null;
        }

        // A quien compra comida y no tiene patología, un **snack**; nunca un suplemento. Los
        // suplementos tratan algo (articulaciones, riñón, tumores…) y muchos no lo llevan marcado en
        // el catálogo: así se colaba un oncológico al lado de un pienso de adulto sano. Suplemento
        // solo cuando comparte la patología, que es el refuerzo de arriba. La higiene, en su terreno:
        // lo que diga `COMPLEMENTOS` de su tipo (a un suplemento, snacks; a un champú, higiene).
        $cuidados = self::cuidados($rasgos['tipo']);
        $comun = [
            'marcas' => [], 'tipos' => $cuidados, 'mascota' => $rasgos['mascota'],
            'necesidad' => '', 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'], 'obligatoria' => false, 'sabor' => '',
            'fuera' => $fuera, 'techo' => $techo, 'cuantos' => 8, 'porVentas' => true,
        ];
        // Primero naturales; si no hay ninguno, lo que haya antes que dejar el hueco vacío.
        $cuidado = self::buscar($comun + ['natural' => true]);
        if ($cuidado === []) {
            $cuidado = self::buscar($comun);
        }
        if ($cuidado === []) {
            return null;
        }
        // Se rota según el producto: si no, la tienda entera enseñaría el mismo snack.
        $cual = $cuidado[((int) ($rasgos['id_product'] ?? 0)) % count($cuidado)];

        return ['id_product' => $cual, 'origen' => 'cuidado'];
    }

    /**
     * Lo que sea, cuando los huecos no se han llenado. Se **suman** búsquedas hasta tener las que
     * faltan, de lo más afín a lo menos: lo de su marca (otro sabor, si es húmedo), el húmedo de las
     * marcas de reserva y, al final, el capricho de su tipo. Antes se paraba en la primera búsqueda
     * que diera algo, aunque diera uno solo, y la ficha se quedaba con dos.
     *
     * Los suplementos nunca entran de relleno: solo por el refuerzo de su patología.
     *
     * @return int[]
     */
    private static function relleno(array $rasgos, int $marca, float $techo, array $fuera, int $cuantos, bool $soloEsaMarca = false, bool $cualquierMarca = false): array
    {
        $comida = in_array($rasgos['tipo'], self::COMIDA, true);
        $cuidados = self::cuidados($rasgos['tipo']);

        if ($soloEsaMarca) {
            // Solo de una marca (Boske): su húmedo o sus snacks para la comida (al húmedo, snacks: el
            // otro sabor ya es de su marca); a lo demás, su capricho.
            $pasos = [['marcas' => [$marca], 'tipos' => $comida && !in_array($rasgos['tipo'], self::HUMEDOS, true)
                ? array_merge(self::HUMEDOS, ['Snacks'])
                : $cuidados, 'necesidad' => $rasgos['necesidad']]];
        } else {
            $tipos = array_values(array_diff(self::COMPLEMENTOS[$rasgos['tipo']] ?? ['Snacks'], self::REFUERZOS));
            // Al húmedo le va otro sabor del mismo tipo: el húmedo se compra variado.
            if (in_array($rasgos['tipo'], self::HUMEDOS, true)) {
                $tipos[] = $rasgos['tipo'];
            }
            $humedos = array_values(array_intersect($tipos, self::HUMEDOS));
            $pasos = [
                ['marcas' => $marca > 0 ? [$marca] : [], 'tipos' => $tipos, 'necesidad' => $rasgos['necesidad']],
                ['marcas' => KcsDistribuidor::reserva($marca, $rasgos['mascota']), 'tipos' => $humedos, 'necesidad' => $rasgos['necesidad']],
                // El húmedo de cualquier otra marca que se pueda ofrecer: en la tienda, la marca y las
                // de reserva del producto no están, y sin esto una dieta de Distrivet no llegaba al
                // Recovery de Royal Canin.
                ['marcas' => [], 'tipos' => $cualquierMarca ? $humedos : [], 'necesidad' => $rasgos['necesidad']],
                ['marcas' => [], 'tipos' => $cuidados, 'necesidad' => ''],
            ];
        }

        $salida = [];
        foreach ($pasos as $paso) {
            if ($paso['tipos'] === [] || count($salida) >= $cuantos) {
                continue;
            }
            $salida = array_merge($salida, self::buscar($paso + [
                'mascota' => $rasgos['mascota'], 'edad' => $rasgos['edad'], 'tamano' => $rasgos['tamano'],
                'obligatoria' => false, 'sabor' => '', 'techo' => $techo, 'porVentas' => true,
                'fuera' => array_merge($fuera, $salida), 'cuantos' => $cuantos - count($salida),
            ]));
        }

        return $salida;
    }

    /**
     * El capricho que va con cada tipo, sin suplementos: a la comida, snacks; a un champú, higiene
     * o snacks; a un suplemento, snacks. Lo que diga `COMPLEMENTOS`, quedándose con los cuidados.
     *
     * @return string[]
     */
    private static function cuidados(string $tipo): array
    {
        return array_values(array_intersect(self::COMPLEMENTOS[$tipo] ?? ['Snacks'], self::CUIDADOS)) ?: ['Snacks'];
    }

    /**
     * Hasta cuánto puede costar una sugerencia.
     *
     * Es la regla que hace que el bloque no moleste: lo que se ofrece de un toque tiene que ser
     * claramente más barato que lo que se está comprando. Boske es la excepción y no pasa por aquí.
     */
    public static function techo(float $precio, array $ajustes = []): float
    {
        $parte = (float) ($ajustes['parte'] ?? 0.6);
        $tope = (float) ($ajustes['tope'] ?? 40.0);
        $porParte = $precio > 0 ? $precio * $parte : $tope;

        return round(min($porParte, $tope), 2);
    }

    /** La marca de la casa, la que manda en el primer hueco. */
    public static function marcaPropia(): int
    {
        return (int) Configuration::get('KCS_MARCA_PROPIA');
    }

    /** Los ids de las necesidades que son patología, para no cruzar enfermedades. @return int[] */
    public static function patologias(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $valores = self::ids()['valores'];
        $cache = [];
        foreach (self::PATOLOGIAS as $nombre) {
            if (isset($valores[$nombre])) {
                $cache[] = (int) $valores[$nombre];
            }
        }

        return $cache;
    }

    /**
     * Las edades que **no** puede tener lo que se ofrece, según la edad del producto que se mira.
     *
     * @return int[]
     */
    public static function edadesFuera(string $edad): array
    {
        if ($edad === '' || $edad === 'Todas las edades') {
            return [];
        }
        $valores = self::ids()['valores'];
        $suya = '';
        foreach (self::ETAPAS as $etapa => $nombres) {
            if (in_array($edad, $nombres, true)) {
                $suya = $etapa;
                break;
            }
        }
        if ($suya === '') {
            return [];
        }
        // El cachorro y el mayor son los extremos: entre ellos no se cruzan, y el adulto no recibe
        // comida de cachorro. Adulto y mayor sí se llevan bien.
        $fuera = $suya === 'joven'
            ? array_merge(self::ETAPAS['adulto'], self::ETAPAS['mayor'])
            : self::ETAPAS['joven'];

        $ids = [];
        foreach ($fuera as $nombre) {
            if (isset($valores[$nombre])) {
                $ids[] = (int) $valores[$nombre];
            }
        }

        return $ids;
    }

    /**
     * Las parejas apuntadas a mano, por su posición. Valen para un producto, para una categoría
     * entera o para una marca entera, y la del producto manda sobre las otras.
     *
     * @return array<int, int[]> posición => productos
     */
    private static function aMano(int $idProducto, int $marca): array
    {
        $p = _DB_PREFIX_;
        $categorias = array_map('intval', Product::getProductCategories($idProducto)) ?: [0];
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT cs.id_sugerido, cs.posicion, cs.ambito
             FROM {$p}kpy_cross_sell cs
             INNER JOIN {$p}product s ON s.id_product = cs.id_sugerido AND s.active = 1
             WHERE cs.activo = 1 AND cs.veto = 0 AND (
                     (cs.ambito = 'producto' AND cs.id_ref = " . (int) $idProducto . ")
                  OR (cs.ambito = 'marca' AND cs.id_ref = " . (int) $marca . ")
                  OR (cs.ambito = 'categoria' AND cs.id_ref IN (" . implode(',', $categorias) . "))
                 )
             ORDER BY FIELD(cs.ambito, 'producto', 'categoria', 'marca'), cs.posicion, cs.id_cross"
        ) ?: [];

        $salida = [];
        foreach ($filas as $f) {
            $salida[max(1, (int) $f['posicion'])][] = (int) $f['id_sugerido'];
        }

        return $salida;
    }

    /**
     * Lo que se ha quitado a mano para ese producto: no vuelve a salir.
     *
     * Vale para el producto, para su marca entera o para cualquiera de sus categorías, igual que
     * las parejas. Es el botón de la papelera de la pantalla.
     *
     * @return int[]
     */
    public static function vetados(int $idProducto, int $marca): array
    {
        $p = _DB_PREFIX_;
        $categorias = array_map('intval', Product::getProductCategories($idProducto)) ?: [0];
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT cs.id_sugerido
             FROM {$p}kpy_cross_sell cs
             WHERE cs.veto = 1 AND (
                     (cs.ambito = 'producto' AND cs.id_ref = " . (int) $idProducto . ")
                  OR (cs.ambito = 'marca' AND cs.id_ref = " . (int) $marca . ")
                  OR (cs.ambito = 'categoria' AND cs.id_ref IN (" . implode(',', $categorias) . '))
                 )'
        ) ?: [];

        return array_map('intval', array_column($filas, 'id_sugerido'));
    }

    /**
     * ¿Se puede ofrecer? Activo, con algún formato que se venda de verdad (con stock y sin apagar
     * en `kpy_product_attribute`) y, si se pide, dentro del precio.
     */
    private static function sirve(int $idProducto, float $techo, bool $saltaTecho = false): bool
    {
        $p = _DB_PREFIX_;
        $idTienda = (int) Context::getContext()->shop->id;
        $desde = (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT MIN(pa.price + pr.price)
             FROM {$p}product pr
             INNER JOIN {$p}product_shop ps ON ps.id_product = pr.id_product AND ps.id_shop = {$idTienda} AND ps.active = 1
             INNER JOIN {$p}product_attribute pa ON pa.id_product = pr.id_product
             INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute
                   AND sa.id_shop = {$idTienda} AND sa.quantity > 0
             WHERE pr.id_product = " . (int) $idProducto . "
               AND pr.active = 1
               AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                               WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)"
        );

        return $desde > 0 && ($saltaTecho || $desde <= $techo);
    }

    /** Los ids de las marcas que no se sugieren nunca. @return int[] */
    public static function vetadas(): array
    {
        static $ids = null;

        return $ids ??= array_values(array_filter(array_map(
            static fn (string $n): int => KcsDistribuidor::porNombre($n),
            self::NUNCA
        )));
    }

    /**
     * La consulta que hace el trabajo. Una sola, con el precio y el stock ya filtrados.
     *
     * @return int[]
     */
    private static function buscar(array $q): array
    {
        $ids = self::ids();
        $tipos = array_values(array_filter(array_map(
            static fn (string $nombre): int => $ids['valores'][$nombre] ?? 0,
            (array) $q['tipos']
        )));
        $mascota = (string) ($q['mascota'] ?? '');
        if ($tipos === [] || $mascota === '' || !isset($ids['valores'][$mascota])) {
            return [];
        }

        $p = _DB_PREFIX_;
        $idTienda = (int) Context::getContext()->shop->id;
        $idMascota = (int) $ids['valores'][$mascota];
        $idNecesidad = (int) ($ids['valores'][(string) ($q['necesidad'] ?? '')] ?? 0);
        $idSabor = (int) ($ids['valores'][(string) ($q['sabor'] ?? '')] ?? 0);
        $idEdad = (int) ($ids['valores'][(string) ($q['edad'] ?? '')] ?? 0);
        $idTamano = (int) ($ids['valores'][(string) ($q['tamano'] ?? '')] ?? 0);
        $idTodasEdades = (int) ($ids['valores']['Todas las edades'] ?? 0);
        $marcas = array_values(array_filter(array_map('intval', (array) ($q['marcas'] ?? []))));
        $fuera = array_values(array_filter(array_map('intval', (array) ($q['fuera'] ?? []))));

        $donde = [
            'pr.active = 1',
            'ft.id_feature_value IN (' . implode(',', $tipos) . ')',
            'fm.id_feature_value = ' . $idMascota,
        ];
        if ($fuera !== []) {
            $donde[] = 'pr.id_product NOT IN (' . implode(',', $fuera) . ')';
        }
        if ($marcas !== []) {
            $donde[] = 'pr.id_manufacturer IN (' . implode(',', $marcas) . ')';
        }
        // Del mismo distribuidor, siempre: un pienso de Neftys con un húmedo de Distrivet son dos
        // envíos, y eso no se le puede ofrecer a nadie de un botón.
        if (self::$deSuDistribuidor !== []) {
            $donde[] = 'pr.id_manufacturer IN (' . implode(',', self::$deSuDistribuidor) . ')';
        }
        // Las marcas que no se sugieren nunca, en ningún hueco.
        $vetadas = self::vetadas();
        if ($vetadas !== []) {
            $donde[] = 'pr.id_manufacturer NOT IN (' . implode(',', $vetadas) . ')';
        }
        // Los caprichos, naturales: ni snacks de supermercado ni marcas de bazar.
        if (!empty($q['natural'])) {
            $naturales = array_values(array_filter([
                (int) ($ids['valores']['Natural'] ?? 0),
                (int) ($ids['valores']['Sin cereales'] ?? 0),
            ]));
            if ($naturales !== []) {
                $donde[] = 'EXISTS (SELECT 1 FROM ' . $p . 'feature_product fnat
                    WHERE fnat.id_product = pr.id_product AND fnat.id_feature = ' . (int) $ids['opcion'] . '
                      AND fnat.id_feature_value IN (' . implode(',', $naturales) . '))';
            }
        }
        // Un suplemento no se cuela por otra etiqueta (Calmex también es «Educación»): si no se piden
        // suplementos, fuera los que lo son, salvo los snacks que el catálogo marca también así.
        $idSuplemento = (int) ($ids['valores']['Complemento Nutricional'] ?? 0);
        $idSnack = (int) ($ids['valores']['Snacks'] ?? 0);
        if ($idSuplemento > 0 && !in_array($idSuplemento, $tipos, true)) {
            $donde[] = '(NOT EXISTS (SELECT 1 FROM ' . $p . 'feature_product fsu
                    WHERE fsu.id_product = pr.id_product AND fsu.id_feature = ' . (int) $ids['tipo'] . ' AND fsu.id_feature_value = ' . $idSuplemento . ')
                OR EXISTS (SELECT 1 FROM ' . $p . 'feature_product fsn
                    WHERE fsn.id_product = pr.id_product AND fsn.id_feature = ' . (int) $ids['tipo'] . ' AND fsn.id_feature_value = ' . $idSnack . '))';
        }
        if (!empty($q['obligatoria']) && $idNecesidad > 0) {
            $donde[] = 'fn.id_feature_value = ' . $idNecesidad;
        }

        // Coherencia de patología: nunca una enfermedad que no sea la suya. A un gato sano no se le
        // ofrece comida de diabéticos, y a uno renal no se le ofrece una de hígado.
        // Cuando se busca **por** una patología (el suplemento renal, el snack renal, el húmedo clínico
        // de Boske), basta con que la comparta: un suplemento «cardiovascular y renal» vale para un
        // perro renal. Solo si es patología: por «esterilizado» no se cuela nada de riñón.
        $patologias = self::patologias();
        $porPatologia = !empty($q['obligatoria']) && in_array($idNecesidad, $patologias, true);
        if ($patologias !== [] && !$porPatologia) {
            $otras = array_values(array_diff($patologias, array_merge([$idNecesidad], self::$necesidadesSuyas)));
            if ($otras !== []) {
                $donde[] = 'NOT EXISTS (SELECT 1 FROM ' . $p . 'feature_product fx
                    WHERE fx.id_product = pr.id_product AND fx.id_feature = ' . (int) $ids['necesidad'] . '
                      AND fx.id_feature_value IN (' . implode(',', $otras) . '))';
            }
        }

        // Coherencia de edad: a un pienso de adulto, nada de cachorro. Vale «Todas las edades» y
        // valen los productos con varias edades si una de ellas es la suya.
        $incompatibles = self::edadesFuera((string) ($q['edad'] ?? ''));
        if ($incompatibles !== []) {
            $buenas = array_values(array_filter([$idEdad, $idTodasEdades]));
            $donde[] = '(NOT EXISTS (SELECT 1 FROM ' . $p . 'feature_product fw
                    WHERE fw.id_product = pr.id_product AND fw.id_feature = ' . (int) $ids['edad'] . '
                      AND fw.id_feature_value IN (' . implode(',', $incompatibles) . '))'
                . ($buenas !== [] ? ' OR EXISTS (SELECT 1 FROM ' . $p . 'feature_product fv2
                    WHERE fv2.id_product = pr.id_product AND fv2.id_feature = ' . (int) $ids['edad'] . '
                      AND fv2.id_feature_value IN (' . implode(',', $buenas) . '))' : '') . ')';
        }

        // Y lo que el catálogo no siempre dice: muchos productos están marcados «Perro y Gato» pero
        // se llaman «… para perros». En la ficha de un gato eso canta, así que fuera.
        $otraMascota = $mascota === 'Gato' ? 'perro' : ($mascota === 'Perro' ? 'gato' : '');
        if ($otraMascota !== '') {
            $suya = $mascota === 'Gato' ? 'gato' : 'perro';
            $donde[] = "NOT (pl.name LIKE '%" . pSQL($otraMascota) . "%' AND pl.name NOT LIKE '%" . pSQL($suya) . "%')";
        }

        // La afinidad: primero la patología, después el sabor y después la edad. Sin la edad, a un
        // pienso de adulto se le ofrecía húmedo de cachorro.
        $afinidad = '(' . ($idNecesidad ? "IF(fn.id_feature_value = {$idNecesidad}, 6, 0)" : '0')
            . ' + ' . ($idSabor ? "IF(fs.id_feature_value = {$idSabor}, 3, 0)" : '0')
            . ' + ' . ($idEdad ? "IF(fe.id_feature_value = {$idEdad}, 4, 0)" : '0')
            . ' + ' . ($idTodasEdades ? "IF(fe.id_feature_value = {$idTodasEdades}, 2, 0)" : '0')
            . ' + ' . ($idTamano ? "IF(fz.id_feature_value = {$idTamano}, 1, 0)" : '0') . ')';

        // Cuando hay varias marcas de reserva, mandan en el orden que ha pedido Rafa.
        $ordenMarca = count($marcas) > 1
            ? 'FIELD(pr.id_manufacturer, ' . implode(',', array_reverse($marcas)) . ') DESC, '
            : '';
        $orden = !empty($q['porVentas'])
            ? 'ventas DESC, desde ASC'
            : $ordenMarca . 'afinidad DESC, desde ASC';

        $techo = (float) ($q['techo'] ?? 0);
        $limite = $techo > 0 ? ' AND desde <= ' . $techo : '';

        $idLang = (int) Context::getContext()->language->id;
        $dietas = array_values(array_filter([
            (int) ($ids['valores']['Dieta Veterinaria Seca'] ?? 0),
            (int) ($ids['valores']['Dieta Veterinaria Húmedo'] ?? 0),
        ]));
        $esDieta = $dietas !== [] ? 'MAX(ft.id_feature_value IN (' . implode(',', $dietas) . '))' : '0';
        $comidas = array_values(array_filter(array_map(static fn (string $n): int => (int) ($ids['valores'][$n] ?? 0), self::COMIDA)));
        $esComida = $comidas !== [] ? 'MAX(ft.id_feature_value IN (' . implode(',', $comidas) . '))' : '0';
        $cuantos = max(1, (int) ($q['cuantos'] ?? 3));

        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pr.id_product, MAX({$afinidad}) AS afinidad, MIN(pa.price + pr.price) AS desde,
                    COALESCE(MAX(vs.quantity), 0) AS ventas,
                    MAX(pl.name) AS nombre, MAX(cl.name) AS categoria, {$esDieta} AS es_dieta, {$esComida} AS es_comida,
                    GROUP_CONCAT(DISTINCT fn.id_feature_value) AS necesidades,
                    GROUP_CONCAT(DISTINCT fm2.id_feature_value) AS mascotas
             FROM {$p}product pr
             INNER JOIN {$p}product_shop ps ON ps.id_product = pr.id_product AND ps.id_shop = {$idTienda} AND ps.active = 1
             INNER JOIN {$p}product_lang pl ON pl.id_product = pr.id_product AND pl.id_shop = {$idTienda}
                   AND pl.id_lang = {$idLang}
             LEFT JOIN {$p}category_lang cl ON cl.id_category = pr.id_category_default AND cl.id_shop = {$idTienda}
                   AND cl.id_lang = {$idLang}
             LEFT JOIN {$p}feature_product fm2 ON fm2.id_product = pr.id_product AND fm2.id_feature = " . (int) $ids['mascota'] . "
             LEFT JOIN {$p}product_sale vs ON vs.id_product = pr.id_product
             INNER JOIN {$p}feature_product ft ON ft.id_product = pr.id_product AND ft.id_feature = " . (int) $ids['tipo'] . "
             INNER JOIN {$p}feature_product fm ON fm.id_product = pr.id_product AND fm.id_feature = " . (int) $ids['mascota'] . "
             LEFT JOIN {$p}feature_product fn ON fn.id_product = pr.id_product AND fn.id_feature = " . (int) $ids['necesidad'] . "
             LEFT JOIN {$p}feature_product fs ON fs.id_product = pr.id_product AND fs.id_feature = " . (int) $ids['sabor'] . "
             LEFT JOIN {$p}feature_product fe ON fe.id_product = pr.id_product AND fe.id_feature = " . (int) $ids['edad'] . "
             LEFT JOIN {$p}feature_product fz ON fz.id_product = pr.id_product AND fz.id_feature = " . (int) $ids['tamano'] . "
             INNER JOIN {$p}product_attribute pa ON pa.id_product = pr.id_product
             INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute AND sa.id_shop = {$idTienda} AND sa.quantity > 0
             WHERE " . implode(' AND ', $donde) . "
               AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                               WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)"
            . "
             GROUP BY pr.id_product
             HAVING desde > 0{$limite}
             ORDER BY {$orden}
             LIMIT " . ($cuantos * 6 + 10)
        ) ?: [];

        // Segunda red: lo que dicen el nombre y la categoría, que el catálogo no siempre tiene en la
        // ficha. Se piden de más y se quedan los que encajan.
        $salida = [];
        foreach ($filas as $f) {
            if (!self::encaja($f, $idNecesidad, !empty($q['obligatoria']))) {
                continue;
            }
            $salida[] = (int) $f['id_product'];
            if (count($salida) >= $cuantos) {
                break;
            }
        }

        return $salida;
    }

    /**
     * ¿Encaja este candidato con el producto que se mira, más allá de la ficha?
     *
     *   - una **dieta veterinaria** solo si comparte la patología: una dieta de riñón no se ofrece
     *     junto a una de obesidad, ni a un pienso sano;
     *   - y al revés: a un producto para una enfermedad, solo **comida** de esa enfermedad;
     *   - si el producto es para perro y gato, el candidato también tiene que serlo, o ser del animal
     *     que diga el nombre del producto: si no, a quien tiene perro le salía algo de gato;
     *   - y lo que dicen los nombres y las categorías (animal, edad, enfermedad), vía `KcsPistas`.
     *
     * @param array{nombre: string, categoria: ?string, es_dieta: mixed, necesidades: ?string, mascotas: ?string} $f
     */
    private static function encaja(array $f, int $idNecesidad, bool $obligatoria): bool
    {
        $necesidades = array_map('intval', array_filter(explode(',', (string) ($f['necesidades'] ?? ''))));
        $suyas = array_values(array_filter(array_merge([$idNecesidad], self::$necesidadesSuyas)));
        if ((int) $f['es_dieta'] === 1 && array_intersect($suyas, $necesidades) === []) {
            return false;
        }

        $valores = self::ids()['valores'];
        $sugerido = KcsPistas::de((string) $f['nombre'], (string) ($f['categoria'] ?? ''));

        // A un producto para una enfermedad solo se le ofrece **comida** para esa enfermedad: a un
        // gato renal, una lata normal le sobra fósforo. Los snacks y los suplementos van por su cuenta.
        if ((int) ($f['es_comida'] ?? 0) === 1 && self::$enfermedadesSuyas !== []) {
            static $nombres = null;
            $nombres ??= array_flip($valores);
            $delSugerido = array_merge($sugerido['enfermedades'], KcsPistas::desdeFicha(array_values(array_filter(array_map(
                static fn (int $id): ?string => $nombres[$id] ?? null,
                $necesidades
            )))));
            if (array_intersect(self::$enfermedadesSuyas, $delSugerido) === []) {
                return false;
            }
        }

        $perro = (int) ($valores['Perro'] ?? 0);
        $gato = (int) ($valores['Gato'] ?? 0);
        $mascotas = array_map('intval', array_filter(explode(',', (string) ($f['mascotas'] ?? ''))));

        // El producto es de los dos animales y su nombre no dice cuál: solo vale lo que sirva a los dos.
        // Y que ni su nombre ni su categoría digan un solo animal («Champús para perros»).
        if (self::$animal === 'ambos') {
            $deLosDos = in_array($perro, $mascotas, true) && in_array($gato, $mascotas, true);
            if ((!$deLosDos && $sugerido['animal'] !== 'ambos') || in_array($sugerido['animal'], ['Perro', 'Gato'], true)) {
                return false;
            }
        } elseif (self::$animal !== '') {
            // El nombre del producto dice el animal: el candidato no puede ser solo del otro.
            $soloOtro = self::$animal === 'Perro'
                ? in_array($gato, $mascotas, true) && !in_array($perro, $mascotas, true)
                : in_array($perro, $mascotas, true) && !in_array($gato, $mascotas, true);
            if ($soloOtro) {
                return false;
            }
        }

        return KcsPistas::encajan(self::$pistas, $sugerido, self::$enfermedadesFicha,
            $obligatoria && in_array($idNecesidad, self::patologias(), true));
    }

    /**
     * Los rasgos del producto: qué es, para quién, de qué edad, tamaño, patología y sabor.
     *
     * @return array{tipo: string, mascota: string, edad: string, tamano: string, necesidad: string, opcion: string, sabor: string, id_product: int}
     */
    public static function rasgos(int $idProducto): array
    {
        static $cache = [];
        if (isset($cache[$idProducto])) {
            return $cache[$idProducto];
        }
        $ids = self::ids();
        $p = _DB_PREFIX_;
        $cuales = array_map('intval', [$ids['tipo'], $ids['mascota'], $ids['edad'], $ids['tamano'], $ids['necesidad'], $ids['opcion'], $ids['sabor']]);
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT fp.id_feature, fvl.value
             FROM {$p}feature_product fp
             INNER JOIN {$p}feature_value_lang fvl ON fvl.id_feature_value = fp.id_feature_value
                   AND fvl.id_lang = " . (int) Context::getContext()->language->id . '
             WHERE fp.id_product = ' . (int) $idProducto . '
               AND fp.id_feature IN (' . implode(',', $cuales) . ')'
        ) ?: [];

        $rasgos = ['tipo' => '', 'mascota' => '', 'edad' => '', 'tamano' => '', 'necesidad' => '', 'opcion' => '', 'sabor' => '', 'id_product' => $idProducto,
            'mascotas' => [], 'necesidades' => []];
        foreach ($filas as $f) {
            foreach (['tipo', 'mascota', 'edad', 'tamano', 'necesidad', 'opcion', 'sabor'] as $cual) {
                if ((int) $f['id_feature'] === (int) $ids[$cual] && $rasgos[$cual] === '') {
                    $rasgos[$cual] = (string) $f['value'];
                }
            }
            // Todas, no solo la primera: hay productos de los dos animales y con varias necesidades.
            if ((int) $f['id_feature'] === (int) $ids['mascota']) {
                $rasgos['mascotas'][] = (string) $f['value'];
            }
            if ((int) $f['id_feature'] === (int) $ids['necesidad']) {
                $rasgos['necesidades'][] = (string) $f['value'];
            }
        }

        return $cache[$idProducto] = $rasgos;
    }

    /**
     * Los identificadores del catálogo, buscados por nombre y guardados.
     *
     * Se buscan por nombre a propósito: los números cambian de una tienda a otra (pruebas y
     * producción no tienen por qué coincidir) y los nombres son los del catálogo de Kompy.
     *
     * @return array{tipo: int, mascota: int, edad: int, tamano: int, necesidad: int, opcion: int, sabor: int, valores: array<string, int>}
     */
    public static function ids(): array
    {
        static $ids = null;
        if ($ids !== null) {
            return $ids;
        }
        $p = _DB_PREFIX_;
        $idLang = (int) Context::getContext()->language->id;
        $ids = ['tipo' => 0, 'mascota' => 0, 'edad' => 0, 'tamano' => 0, 'necesidad' => 0, 'opcion' => 0, 'sabor' => 0, 'valores' => []];

        foreach (self::CARACTERISTICAS as $clave => $nombre) {
            $ids[$clave] = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                "SELECT id_feature FROM {$p}feature_lang WHERE id_lang = {$idLang} AND name = '" . pSQL($nombre) . "'"
            );
        }

        $cuales = array_map('intval', [$ids['tipo'], $ids['mascota'], $ids['edad'], $ids['tamano'], $ids['necesidad'], $ids['opcion'], $ids['sabor']]);
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT fvl.id_feature_value, fvl.value
             FROM {$p}feature_value_lang fvl
             INNER JOIN {$p}feature_value fv ON fv.id_feature_value = fvl.id_feature_value
             WHERE fvl.id_lang = {$idLang} AND fv.id_feature IN (" . implode(',', $cuales) . ')'
        ) ?: [];
        foreach ($filas as $f) {
            $ids['valores'][(string) $f['value']] = (int) $f['id_feature_value'];
        }

        return $ids;
    }
}
