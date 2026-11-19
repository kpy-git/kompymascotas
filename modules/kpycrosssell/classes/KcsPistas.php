<?php
/**
 * Lo que dicen el **nombre** y la **categoría** de un producto sobre el animal, la edad y la
 * enfermedad para la que es.
 *
 * Es la segunda red del motor. La primera son las características de la ficha (Mascota, Edad,
 * Necesidades específicas), pero el catálogo no siempre las tiene bien: hay snacks «Puppy» marcados
 * «Todas las edades», dietas veterinarias de riñón sin la patología puesta y productos «para
 * perros» marcados «Perro y Gato». Con solo la ficha, a un pienso de adulto le salía un snack de
 * cachorro y a una dieta urinaria una de obesidad.
 *
 * Aquí no se decide nada: solo se lee. Quien decide es `KcsSugerencias`.
 */
class KcsPistas
{
    /** Palabras que delatan una dieta para una enfermedad, por enfermedad. */
    private const ENFERMEDADES = [
        'renal' => '/\b(renal|kidney|k\/d|k-d)\b/u',
        'urinario' => '/\b(urinar\w*|urinary|struvite|c\/d|u\/d|s\/o|so\b)/u',
        // m/d y w/d son de diabetes y de peso a la vez: se ofrecen con las dos.
        'diabetes' => '/\b(diabet\w*|glucose|glycobalance|m\/d|w\/d)/u',
        'hepático' => '/\b(hepat\w*|l\/d)\b/u',
        'digestivo' => '/\b(gastro\w*|intestinal|digest\w*|i\/d|low fat)\b/u',
        'alergia' => '/\b(hipoaler\w*|hypoaller\w*|anallergenic|z\/d|d\/d|derm\w*|sensitivity control)\b/u',
        'peso' => '/\b(obesity|obesidad|satiety|r\/d|w\/d|m\/d|metabolic|weight management)\b/u',
        'articular' => '/\b(mobility|movilidad|articul\w*|joint|j\/d)\b/u',
        'oncológico' => '/\b(onco\w*)/u',
        'cardiaco' => '/\b(cardi\w*|heart)\b/u',
        'recuperación' => '/\b(a\/d|convalesc\w*|recovery|recuperaci\w*)\b/u',
    ];

    /**
     * Para qué es un producto, por el nombre: un snack **dental**, un complemento para la **piel**, unas
     * gotas para la **calma**… Es lo que distingue un snack de otro cuando la ficha no lo dice (un
     * «Dentalife» sin «Problemas orales y dentales» en Necesidades: pasa). Se usa para que a un snack
     * dental se le ofrezca antes otro snack dental, y nunca uno para adelgazar.
     */
    private const TEMAS = [
        'dental' => '/\b(dental\w*|dentalife|dentastix|dentastick\w*|veggiedent|orozyme|oral\w*|bucal\w*|dientes|teeth|tooth|placa|sarro|aliento|breath|tartar|plaque)\b/u',
        'piel' => '/\b(piel|skin|coat|derma\w*|pelaje)\b/u',
        'articular' => '/\b(mobility|movilidad|articul\w*|joint\w*|condro\w*|chondro\w*|glucosamin\w*|artro\w*|arthro\w*)\b/u',
        'calma' => '/\b(calm\w*|relax\w*|estr[ée]s|ansiedad|anxiety|anxious|zylkene|adaptil|feliway|tranquil\w*|serenit\w*)\b/u',
        'peso' => '/\b(light|lite|low ?fat|baj[oa]s? en grasa|obesity|obesidad|weight|satiety|metabolic|slim)\b/u',
        'esterilizado' => '/\b(steril\w*|esteril\w*|castrad\w*|neutered|spayed)\b/u',
        'digestivo' => '/\b(digest\w*|gastro\w*|intestinal|probi[oó]tic\w*|prebi[oó]tic\w*)\b/u',
        'urinario' => '/\b(urinar\w*|urinary)\b/u',
        'bolas' => '/\b(bolas de pelo|hairball\w*|malt|malta|malt paste)\b/u',
        'alergia' => '/\b(hipoaler\w*|hypoaller\w*|anallergenic|sensitiv\w*|sensible)\b/u',
    ];

    /** Lo mismo, cuando la ficha sí lo dice («Necesidades específicas» → tema). */
    private const TEMAS_FICHA = [
        'Problemas orales y dentales' => 'dental',
        'Estrés y ansiedad' => 'calma',
        'Esterilizados/Castrados' => 'esterilizado',
        'Bolas de pelo' => 'bolas',
        'Control del peso' => 'peso',
        'Sobrepeso' => 'peso',
        'Problemas de pelo y piel' => 'piel',
        'Movilidad y articulaciones' => 'articular',
        'Problemas gastrointestinales' => 'digestivo',
        'Problemas urinarios' => 'urinario',
        'Problemas alérgicos e intolerancias' => 'alergia',
    ];

    /**
     * Los temas de un producto: por el nombre y por la ficha, ordenados, sin repetir. Vacío = genérico.
     *
     * @param string[] $necesidades nombres de «Necesidades específicas» de la ficha
     *
     * @return string[]
     */
    public static function temas(string $nombre, array $necesidades = []): array
    {
        $n = mb_strtolower($nombre);
        $salida = [];
        foreach (self::TEMAS as $tema => $patron) {
            if (preg_match($patron, $n)) {
                $salida[] = $tema;
            }
        }
        foreach ($necesidades as $necesidad) {
            if (isset(self::TEMAS_FICHA[$necesidad])) {
                $salida[] = self::TEMAS_FICHA[$necesidad];
            }
        }
        $salida = array_values(array_unique($salida));
        sort($salida);

        return $salida;
    }

    /** Lo que dice de la talla del perro un nombre o un formato («perros grandes», «L - Más de 30 kg», «Mini»). */
    private const TALLAS = [
        // Las letras sueltas (S, M, L) solo si no van detrás de un número («2 l» es una botella, no una
        // talla) ni de un apóstrofo («Hill's» no es una talla S).
        'pequeño' => '/(?<!\d)(?<!\d )(?<![\'’])\b(xs|s)\b|\b(small|mini|toy|pequeñ\w*|peque\w*)\b/u',
        'mediano' => '/(?<!\d)(?<!\d )(?<![\'’])\b(m)\b|\b(medium|median\w*|medio\w*)\b/u',
        'grande' => '/(?<!\d)(?<!\d )(?<![\'’])\b(l|xl|xxl)\b|\b(large|maxi|grand\w*|giant|gigante\w*)\b/u',
    ];

    /** La talla que corresponde a cada valor de «Tamaño» de la ficha. «Todos los tamaños» no dice nada. */
    private const TALLAS_FICHA = ['Mini' => 'pequeño', 'Pequeño' => 'pequeño', 'Mediano' => 'mediano', 'Medium' => 'mediano', 'Grande' => 'grande', 'Maxi' => 'grande', 'Gigante' => 'grande'];

    /**
     * La talla del perro que dice un texto: 'pequeño', 'mediano', 'grande' o '' (no lo dice o dice varias).
     * Primero la ficha («Tamaño»), si se da; si no, el nombre o el formato.
     */
    public static function talla(string $texto, string $tamanoFicha = ''): string
    {
        if ($tamanoFicha !== '' && isset(self::TALLAS_FICHA[$tamanoFicha])) {
            return self::TALLAS_FICHA[$tamanoFicha];
        }
        $t = mb_strtolower($texto);
        $salida = [];
        foreach (self::TALLAS as $talla => $patron) {
            if (preg_match($patron, $t)) {
                $salida[] = $talla;
            }
        }

        return count($salida) === 1 ? $salida[0] : '';
    }

    /**
     * En qué orden va un candidato respecto a la línea, por tema.
     *
     * Si comparten tema (dental con dental), primero. Si el candidato no es para nada en concreto, después.
     * Si es para otra cosa (a un dental, uno para adelgazar), el último: solo se verá si no hay nada mejor
     * (Rafa, 18/11: mejor una alternativa que ninguna). Si la línea no es para nada en concreto, primero los
     * genéricos y después los especializados.
     *
     * @param string[] $deLinea
     * @param string[] $deCandidato
     *
     * @return int 0 = el que mejor encaja, 1 = vale pero después, 2 = solo si no hay otra cosa
     */
    public static function ordenPorTema(array $deLinea, array $deCandidato): int
    {
        if ($deLinea === []) {
            return $deCandidato === [] ? 0 : 1;
        }
        if ($deCandidato === []) {
            return 1;
        }

        return array_intersect($deLinea, $deCandidato) !== [] ? 0 : 2;
    }

    /**
     * @return array{animal: string, edad: string, enfermedades: string[]}
     *               `animal`: 'Perro', 'Gato', 'ambos' o '' (no lo dice)
     *               `edad`: 'joven', 'adulto', 'mayor' o '' (no lo dice)
     */
    public static function de(string $nombre, string $categoria = ''): array
    {
        $n = mb_strtolower($nombre);
        $c = mb_strtolower($categoria);

        $animal = self::animal($n);
        if ($animal === '') {
            $animal = self::animal($c);
        }

        return [
            'animal' => $animal,
            'edad' => self::edad($n),
            'enfermedades' => array_values(array_unique(array_merge(self::enfermedades($n), self::enfermedades($c)))),
        ];
    }

    /**
     * Qué tipo de producto es, por el nombre y si no por la categoría. Solo para los productos a los
     * que la ficha no se lo pone («Tipo de Producto» vacío): sin tipo, el motor no sabe qué ofrecer.
     *
     * @return string el nombre del valor de «Tipo de Producto», o '' si no se deduce
     */
    public static function tipo(string $nombre, string $categoria = ''): string
    {
        foreach ([mb_strtolower($nombre), mb_strtolower($categoria)] as $t) {
            foreach (self::TIPOS as $tipo => $patron) {
                if (preg_match($patron, $t)) {
                    return $tipo;
                }
            }
        }

        return '';
    }

    /** Palabras que delatan el tipo de producto, en orden: «snack para gatos» es snack, no comida. */
    private const TIPOS = [
        'Snacks' => '/\b(snacks?|golosinas?|premios?|galletas?|stix|snax|chuches?|masticables?)\b/u',
        'Complemento Nutricional' => '/\b(suplementos?|complementos?|aceite de salm\w*)\b/u',
        'Antiparasitario' => '/\b(antiparasit\w*|pipetas?)\b/u',
        'Arenas y Suministros para Gatos' => '/\b(arenas?|lecho)\b/u',
        'Higiene' => '/\b(champ\w*|higiene|peluquer\w*|cepill\w*|toallitas?|guantes?|mascarillas?)\b/u',
        'Accesorios' => '/\b(comederos?|bebederos?|juguetes?|ropa|capas?|camas?|transport\w*|correas?|collar\w*|biber\w*|accesorios?)\b/u',
        'Comida Húmeda' => '/\b(h[uú]med[oa]s?|latas?|pouch\w*|sobres?|terrinas?|tarrinas?)\b/u',
        'Comida Seca' => '/\b(piensos?)\b/u',
    ];

    /**
     * ¿Encaja lo que dice el sugerido con lo que dice el producto que se mira?
     *
     * @param array{animal: string, edad: string, enfermedades: string[]} $producto
     * @param array{animal: string, edad: string, enfermedades: string[]} $sugerido
     * @param string[] $patologiasProducto las de la ficha del producto, en las mismas claves
     * @param bool $bastaUna se busca **por** una enfermedad (el suplemento renal de un pienso renal):
     *                       basta con que la comparta, aunque diga otra más («cardiovascular y renal»)
     */
    public static function encajan(array $producto, array $sugerido, array $patologiasProducto = [], bool $bastaUna = false): bool
    {
        // El animal: si los dos lo dicen y no coincide, fuera. «Para perros y gatos» vale para ambos.
        if ($producto['animal'] !== '' && $sugerido['animal'] !== ''
            && $producto['animal'] !== 'ambos' && $sugerido['animal'] !== 'ambos'
            && $producto['animal'] !== $sugerido['animal']) {
            return false;
        }

        // La edad: cachorro con adulto o mayor no se cruzan, en ningún sentido.
        if ($sugerido['edad'] !== '' && $producto['edad'] !== $sugerido['edad']
            && ($sugerido['edad'] === 'joven' || $producto['edad'] === 'joven')) {
            return false;
        }

        // La enfermedad: si el nombre del sugerido la dice, el producto la tiene que tener.
        $delProducto = array_merge($producto['enfermedades'], $patologiasProducto);
        if ($bastaUna && $sugerido['enfermedades'] !== []) {
            return array_intersect($sugerido['enfermedades'], $delProducto) !== [];
        }
        foreach ($sugerido['enfermedades'] as $e) {
            if (!in_array($e, $delProducto, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * La enfermedad que corresponde a cada patología de la ficha, para comparar con el nombre.
     *
     * @param string[] $patologias nombres de «Necesidades específicas»
     *
     * @return string[]
     */
    public static function desdeFicha(array $patologias): array
    {
        $mapa = [
            'Problemas renales' => 'renal', 'Problemas urinarios' => 'urinario', 'Diabetes' => 'diabetes',
            'Problemas hepáticos e hígado' => 'hepático', 'Problemas gastrointestinales' => 'digestivo',
            'Problemas alérgicos e intolerancias' => 'alergia', 'Problemas de pelo y piel' => 'alergia',
            'Sobrepeso' => 'peso', 'Control del peso' => 'peso', 'Movilidad y articulaciones' => 'articular',
            'Problemas cardiacos y corazón' => 'cardiaco', 'Recuperación' => 'recuperación',
        ];
        $salida = [];
        foreach ($patologias as $p) {
            if (isset($mapa[$p])) {
                $salida[] = $mapa[$p];
            }
        }

        return array_values(array_unique($salida));
    }

    private static function animal(string $t): string
    {
        // «Royal Canin» es una marca, no un perro: sin quitarla, todo lo suyo de gato era «de los dos».
        $t = str_replace('royal canin', '', $t);
        $perro = (bool) preg_match('/\b(perr\w*|puppy|puppies|dog\w*|canin\w*|cachorr\w*)\b/u', $t);
        $gato = (bool) preg_match('/\b(gat\w*|kitten\w*|cat|cats|felin\w*)\b/u', $t);
        if ($perro && $gato) {
            return 'ambos';
        }

        return $perro ? 'Perro' : ($gato ? 'Gato' : '');
    }

    private static function edad(string $t): string
    {
        if (preg_match('/\b(cachorr\w*|puppy|puppies|kitten\w*|gatit\w*|junior|starter|baby)\b/u', $t)) {
            return 'joven';
        }
        if (preg_match('/\b(senior|mature|mayores|ageing|aging)\b|7\+|\+7|11\+/u', $t)) {
            return 'mayor';
        }
        if (preg_match('/\b(adult\w*)\b/u', $t)) {
            return 'adulto';
        }

        return '';
    }

    /** @return string[] */
    private static function enfermedades(string $t): array
    {
        $salida = [];
        foreach (self::ENFERMEDADES as $nombre => $patron) {
            if (preg_match($patron, $t)) {
                $salida[] = $nombre;
            }
        }

        return $salida;
    }
}
