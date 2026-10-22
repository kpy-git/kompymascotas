<?php
/**
 * El título del bloque, que cambia según lo que se está ofreciendo y para qué.
 *
 * «Llévalo junto» (el título de la 1.0) no vendía nada: no decía qué gana el perro. En el web
 * antiguo los textos iban por necesidad («completa su alimentación», «dale un capricho», «cuida sus
 * articulaciones»), y eso es lo que se recupera aquí, pero sacándolo del catálogo en vez de a mano.
 * Si no se sabe qué se ofrece, el título es el nombre del bloque: «Caprichitos».
 *
 * Manda la **necesidad específica** cuando el producto la tiene y lo que se le ofrece la comparte:
 * si alguien está mirando un pienso de articulaciones y se le ofrece un complemento de
 * articulaciones, el título lo dice. Si no hay necesidad en común, manda **lo que se ofrece**
 * (húmedo, snacks, complemento…). Y siempre hay un texto de reserva, para no quedarse sin título.
 *
 * Regla que no se salta: **el título solo promete lo que la sugerencia cumple**. Poner «cuida sus
 * articulaciones» sobre un paté cualquiera es mentir, y se nota.
 */
class KcsTitulo
{
    /** Por necesidad del producto, cuando la sugerencia también la tiene. */
    public const POR_NECESIDAD = [
        'Movilidad y articulaciones' => 'Cuida sus articulaciones',
        'Problemas urinarios' => 'Cuida su sistema urinario',
        'Problemas renales' => 'Ayuda a su función renal',
        'Problemas de pelo y piel' => 'Cuida su piel y su pelo',
        'Problemas gastrointestinales' => 'Cuida su digestión',
        'Problemas alérgicos e intolerancias' => 'Sin lo que le sienta mal',
        'Control del peso' => 'Ayúdale a mantener el peso',
        'Sobrepeso' => 'Ayúdale a mantener el peso',
        'Esterilizados/Castrados' => 'Pensado para esterilizados',
        'Problemas orales y dentales' => 'Cuida su boca',
        'Estrés y ansiedad' => 'Ayúdale a estar tranquilo',
        'Problemas hepáticos e hígado' => 'Cuida su hígado',
        'Problemas cardiacos y corazón' => 'Cuida su corazón',
        'Diabetes' => 'Ayuda a controlar su glucosa',
        'Bolas de pelo' => 'Ayuda con las bolas de pelo',
        'Alta actividad' => 'A la altura de su ritmo',
        'Recuperación' => 'Ayúdale a recuperarse',
        'Vitaminas' => 'Un refuerzo para su día a día',
        'Función cerebral' => 'Un refuerzo para su cabeza',
    ];

    /** Por lo que se ofrece, cuando no hay una necesidad en común. */
    public const POR_TIPO = [
        'Comida Húmeda' => 'Completa su alimentación',
        'Dieta Veterinaria Húmedo' => 'Completa su dieta veterinaria',
        'Comida Semi-Húmeda' => 'Completa su alimentación',
        'Leche' => 'Completa su alimentación',
        'Snacks' => 'Dale un capricho',
        'Educación y Adiestramiento' => 'Para premiar sus avances',
        'Complemento Nutricional' => 'Refuerza su alimentación',
        'Higiene' => 'Para su cuidado diario',
        'Accesorios' => 'Lo que le hace falta',
        'Antiparasitario' => 'Protégele también por fuera',
        'Arenas y Suministros para Gatos' => 'Para su día a día',
        'Comida Seca' => 'Completa su alimentación',
        'Dieta Veterinaria Seca' => 'Completa su dieta veterinaria',
    ];

    /** Cuando lo que se ofrece es húmedo y el pienso es natural, se dice así. */
    private const NATURAL = 'Completa su pienso con alimentación natural';

    /**
     * El título y la frase de debajo.
     *
     * @param array{tipo: string, necesidad: string} $rasgos    del producto que se está viendo
     * @param array $articulos                                  lo que se le va a ofrecer
     *
     * @return array{titulo: string, sub: string}
     */
    public static function para(array $rasgos, array $articulos, Kpycrosssell $modulo): array
    {
        $necesidad = (string) ($rasgos['necesidad'] ?? '');
        $tipos = array_values(array_filter(array_map(static fn (array $a): string => (string) ($a['tipo'] ?? ''), $articulos)));
        $necesidades = array_values(array_filter(array_map(static fn (array $a): string => (string) ($a['necesidad'] ?? ''), $articulos)));

        // 0. Antiparasitario con antiparasitarios: collar, pastilla y pipeta se complementan.
        if ((string) ($rasgos['tipo'] ?? '') === 'Antiparasitario' && ($tipos[0] ?? '') === 'Antiparasitario') {
            return [
                'titulo' => $modulo->t('Protección completa contra parásitos'),
                'sub' => $modulo->t('Collar, pastilla y pipeta se complementan. Se añade de un toque.'),
            ];
        }

        // 1. La necesidad, si la comparten. Es lo que más convence y lo que el cliente ya tiene en
        //    la cabeza: ha entrado buscando eso.
        if ($necesidad !== '' && in_array($necesidad, $necesidades, true) && isset(self::POR_NECESIDAD[$necesidad])) {
            return [
                'titulo' => $modulo->t(self::POR_NECESIDAD[$necesidad]),
                'sub' => $modulo->t('De la misma línea que su pienso. Se añade de un toque.'),
            ];
        }

        // 2. Lo que se ofrece. Si es húmedo natural sobre un pienso natural, se dice entero.
        $primero = $tipos[0] ?? '';
        // «Natural» solo si lo son **las dos** cosas: decírselo a quien compra una dieta
        // veterinaria porque el paté esté etiquetado natural no se sostiene.
        if (in_array($primero, ['Comida Húmeda', 'Comida Semi-Húmeda'], true)
            && (string) ($rasgos['opcion'] ?? '') === 'Natural'
            && self::esNatural($articulos)) {
            return [
                'titulo' => $modulo->t(self::NATURAL),
                'sub' => $modulo->t('Se añade de un toque, sin salir de aquí.'),
            ];
        }
        if (isset(self::POR_TIPO[$primero])) {
            return [
                'titulo' => $modulo->t(self::POR_TIPO[$primero]),
                'sub' => $modulo->t('Lo que mejor le va a este producto. Se añade de un toque.'),
            ];
        }

        // 3. Y si no se sabe qué es, algo que siempre vale.
        return [
            'titulo' => $modulo->t('Caprichitos'),
            'sub' => $modulo->t('Lo que mejor le va a este producto. Se añade de un toque.'),
        ];
    }

    private static function esNatural(array $articulos): bool
    {
        foreach ($articulos as $a) {
            if (!empty($a['natural'])) {
                return true;
            }
        }

        return false;
    }
}
