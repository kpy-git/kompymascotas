<?php

namespace PrestaShop\Module\KpyAquaOrders\Service;

class Mailer
{
    public function sendMailError(int $orderId, string $asunto, string $msg, array $receivers = ["programacion@piensoymascotas.com"], array $attachedFile = []): void
    {
        \Mail::Send(
            (int)\Configuration::get('PS_LANG_DEFAULT'),
            'generic',
            $asunto,
            array(
                '{message}' => $msg,
                '{id_order}' => $orderId,
            ),
            $receivers,
            null,
            null,
            null,
            $attachedFile
        );
    }
}