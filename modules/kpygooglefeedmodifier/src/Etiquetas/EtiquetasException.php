<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyGoogleFeedModifier\Etiquetas;

/** Error al recibir o guardar la lista de etiquetas; lleva el código HTTP con el que se contesta. */
class EtiquetasException extends \RuntimeException
{
    private int $codigoHttp;

    public function __construct(string $mensaje, int $codigoHttp = 422)
    {
        parent::__construct($mensaje);
        $this->codigoHttp = $codigoHttp;
    }

    public function getCodigoHttp(): int
    {
        return $this->codigoHttp;
    }
}
