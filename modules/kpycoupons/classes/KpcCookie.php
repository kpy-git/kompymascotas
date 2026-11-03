<?php
/**
 * La cookie de los cupones del cliente (`kpy_cupon`), sin PrestaShop dentro (se prueba sin tienda
 * en `tests/cookie.php`).
 *
 * Desde la 3.2.0 guarda **varios** cupones a la vez: los que el cliente lleva **puestos** (pulsados
 * en la ficha, por `?kpc=`, escritos en «¿Tienes un cupón?», o que entraron solos y se dejaron
 * apuntados) y los que ha **quitado** (la papelera de la cesta o «Quitar» en la ficha: esos no
 * vuelven a entrar solos mientras navega; una visita nueva desde un canal los perdona). Así un
 * cliente que compra Specific y Advance en el mismo pedido lleva el cupón de cada marca; hasta la
 * 3.1 solo cabía uno, y poner el segundo quitaba el primero.
 *
 * Formato: códigos en mayúsculas separados por comas, los quitados con «!» delante:
 *     SPECIFIC10,ADVANCE15,!BFBOEH10
 * Lo de antes («CODIGO» puesto, «!CODIGO» quitado) se lee igual. Como mucho `MAX` de cada clase (los
 * más antiguos se caen): la cookie de PrestaShop lleva todo lo demás dentro y no puede pasar de 4 KB.
 */
class KpcCookie
{
    /** Cuántos códigos se recuerdan de cada clase (puestos y quitados). */
    public const MAX = 10;

    public const SEPARADOR = ',';

    public const QUITADO = '!';

    /** Un código que vale: letras, números, guion y guion bajo, de 2 a 32 (lo mismo que admite `?kpc=`). */
    private const CODIGO = '/^[A-Z0-9_-]{2,32}$/';

    /**
     * Lo que hay en la cookie: los puestos y los quitados, en el orden en que se apuntaron, sin
     * repetidos y sin lo que no sea un código.
     *
     * @return array{puestos: string[], quitados: string[]}
     */
    public static function leer(string $valor): array
    {
        $puestos = [];
        $quitados = [];
        foreach (explode(self::SEPARADOR, $valor) as $trozo) {
            $trozo = trim($trozo);
            $quitado = $trozo !== '' && $trozo[0] === self::QUITADO;
            $codigo = self::codigo($quitado ? substr($trozo, 1) : $trozo);
            if ($codigo === '') {
                continue;
            }
            if ($quitado) {
                if (!in_array($codigo, $quitados, true)) {
                    $quitados[] = $codigo;
                }
            } elseif (!in_array($codigo, $puestos, true)) {
                $puestos[] = $codigo;
            }
        }
        // Un código no puede estar en las dos listas: manda lo último que se apuntó (va detrás).
        foreach ($puestos as $i => $c) {
            $j = array_search($c, $quitados, true);
            if ($j !== false) {
                if (self::posicion($valor, self::QUITADO . $c) > self::posicion($valor, $c)) {
                    unset($puestos[$i]);
                } else {
                    unset($quitados[$j]);
                }
            }
        }

        return ['puestos' => array_values($puestos), 'quitados' => array_values($quitados)];
    }

    /**
     * La cookie a partir de las dos listas (cada una recortada a `MAX`, quedándose con los últimos).
     *
     * @param string[] $puestos
     * @param string[] $quitados
     */
    public static function escribir(array $puestos, array $quitados): string
    {
        $limpiar = static function (array $lista): array {
            $salida = [];
            foreach ($lista as $c) {
                $c = self::codigo((string) $c);
                if ($c !== '' && !in_array($c, $salida, true)) {
                    $salida[] = $c;
                }
            }

            return array_slice($salida, -self::MAX);
        };
        $puestos = $limpiar($puestos);
        $quitados = array_values(array_diff($limpiar($quitados), $puestos));
        $trozos = $puestos;
        foreach ($quitados as $c) {
            $trozos[] = self::QUITADO . $c;
        }

        return implode(self::SEPARADOR, $trozos);
    }

    /** El cliente pone un cupón: pasa a los puestos (y deja de estar quitado). */
    public static function poner(string $valor, string $codigo): string
    {
        $codigo = self::codigo($codigo);
        if ($codigo === '') {
            return $valor;
        }
        $l = self::leer($valor);
        $puestos = array_values(array_diff($l['puestos'], [$codigo]));
        $puestos[] = $codigo;

        return self::escribir($puestos, array_diff($l['quitados'], [$codigo]));
    }

    /** El cliente quita un cupón: pasa a los quitados (y no vuelve a entrar solo mientras navega). */
    public static function quitar(string $valor, string $codigo): string
    {
        $codigo = self::codigo($codigo);
        if ($codigo === '') {
            return $valor;
        }
        $l = self::leer($valor);
        $quitados = array_values(array_diff($l['quitados'], [$codigo]));
        $quitados[] = $codigo;

        return self::escribir(array_diff($l['puestos'], [$codigo]), $quitados);
    }

    public static function estaPuesto(string $valor, string $codigo): bool
    {
        return in_array(self::codigo($codigo), self::leer($valor)['puestos'], true);
    }

    public static function estaQuitado(string $valor, string $codigo): bool
    {
        return in_array(self::codigo($codigo), self::leer($valor)['quitados'], true);
    }

    /** Una visita nueva desde un canal: lo quitado se perdona (es lo que se le prometió en el anuncio o el email). */
    public static function sinQuitados(string $valor): string
    {
        return self::escribir(self::leer($valor)['puestos'], []);
    }

    /** El código como se guarda (mayúsculas, sin espacios), o vacío si no es un código. */
    public static function codigo(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));

        return preg_match(self::CODIGO, $codigo) ? $codigo : '';
    }

    /** Dónde está un trozo en la cookie (para saber cuál se apuntó después). */
    private static function posicion(string $valor, string $trozo): int
    {
        $i = 0;
        foreach (explode(self::SEPARADOR, strtoupper($valor)) as $t) {
            if (trim($t) === $trozo) {
                return $i;
            }
            ++$i;
        }

        return -1;
    }
}
