<?php
/**
 * Reparte las características del producto (feature_product) en dos bloques:
 *
 *  - «Para»: a quién va dirigido (mascota, edad, raza y tamaño), en pastillas. «Todas las
 *    razas» y «Todos los tamaños» juntos se dicen una vez: «Todas las razas y tamaños».
 *  - Fichas: el resto (tipo, textura, ingrediente principal, opción nutricional…), en el orden de
 *    la tienda.
 *
 * Las características se reconocen por su nombre en la tienda. Una que no se conoce va a las
 * fichas: nunca se pierde un dato. No depende de PrestaShop.
 */
class KppCaracteristicas
{
    private const PARA = ['mascota', 'edad', 'raza', 'tamano'];

    private const PLURAL = ['perro' => 'Perros', 'gato' => 'Gatos', 'cachorro' => 'Cachorros', 'gatito' => 'Gatitos',
        'roedor' => 'Roedores', 'conejo' => 'Conejos', 'pajaro' => 'Pájaros', 'ave' => 'Aves', 'pez' => 'Peces',
        'reptil' => 'Reptiles', 'huron' => 'Hurones', 'caballo' => 'Caballos', 'cobaya' => 'Cobayas', 'hamster' => 'Hámsteres'];

    /**
     * @param array<int, array{nombre: string, valor: string}> $caracteristicas en el orden de la
     *        tienda; una característica con varios valores viene en varias filas
     *
     * @return array{para: array<int, array{texto: string, principal: bool}>, fichas: array<int, array{nombre: string, valor: string}>}
     */
    public static function repartir(array $caracteristicas): array
    {
        // Agrupar valores por característica, conservando el orden.
        $grupos = [];
        foreach ($caracteristicas as $c) {
            $nombre = trim((string) $c['nombre']);
            $valor = trim((string) $c['valor']);
            if ($nombre === '' || $valor === '') {
                continue;
            }
            $grupos[$nombre] ??= [];
            if (!in_array($valor, $grupos[$nombre], true)) {
                $grupos[$nombre][] = $valor;
            }
        }

        $para = [];
        $todasRazas = false;
        $todosTamanos = false;
        $fichas = [];
        foreach ($grupos as $nombre => $valores) {
            $clave = self::normalizar($nombre);
            if (!in_array($clave, self::PARA, true)) {
                $fichas[] = ['nombre' => $nombre, 'valor' => implode(', ', $valores)];
                continue;
            }
            foreach ($valores as $v) {
                $n = self::normalizar($v);
                if ($clave === 'raza' && str_starts_with($n, 'todas')) {
                    $todasRazas = true;
                } elseif ($clave === 'tamano' && str_starts_with($n, 'todos')) {
                    $todosTamanos = true;
                } elseif ($clave === 'mascota') {
                    $para[] = ['texto' => self::PLURAL[$n] ?? $v, 'principal' => true, 'orden' => 0];
                } else {
                    $para[] = ['texto' => $v, 'principal' => false, 'orden' => $clave === 'edad' ? 1 : 2];
                }
            }
        }
        if ($todasRazas && $todosTamanos) {
            $para[] = ['texto' => 'Todas las razas y tamaños', 'principal' => false, 'orden' => 3];
        } elseif ($todasRazas) {
            $para[] = ['texto' => 'Todas las razas', 'principal' => false, 'orden' => 3];
        } elseif ($todosTamanos) {
            $para[] = ['texto' => 'Todos los tamaños', 'principal' => false, 'orden' => 3];
        }
        usort($para, fn ($a, $b) => $a['orden'] <=> $b['orden']);

        return [
            'para' => array_map(fn ($p) => ['texto' => $p['texto'], 'principal' => $p['principal']], $para),
            'fichas' => $fichas,
        ];
    }

    /** Los valores de «Opción nutricional» (Natural, Sin cereales…) se enseñan también como sellos. */
    public static function sellos(array $caracteristicas): array
    {
        $sellos = [];
        foreach ($caracteristicas as $c) {
            if (self::normalizar((string) $c['nombre']) === 'opcion nutricional') {
                foreach (preg_split('/\s*,\s*/u', (string) $c['valor']) as $v) {
                    if ($v !== '' && !in_array($v, $sellos, true)) {
                        $sellos[] = $v;
                    }
                }
            }
        }

        return $sellos;
    }

    private static function normalizar(string $t): string
    {
        $t = mb_strtolower(trim($t));

        return strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
