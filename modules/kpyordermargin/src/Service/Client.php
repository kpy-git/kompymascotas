<?php

namespace PrestaShop\Module\KpyOrderMargin\Service;

use PrestaShop\Module\KpyOrderMargin\Config\KpyOrderMarginConfig;
use PrestaShop\Module\KpyOrderMargin\Exception\KpyOrderMarginException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Client
{
    private HttpClientInterface $client;
    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    /**
     * @throws KpyOrderMarginException
     */
    public function calculateMargin(int $orderId): void
    {
        try {
            $response = $this->client->request('GET', \Configuration::get(KpyOrderMarginConfig::KPY_ORDER_MARGIN_URL) . '/' . $orderId, [
                'auth_basic' => [
                    \Configuration::get(KpyOrderMarginConfig::KPY_ORDER_MARGIN_HTTP_CLIENT),
                    \Configuration::get(KpyOrderMarginConfig::KPY_ORDER_MARGIN_HTTP_PASSWORD)
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new KpyOrderMarginException($response->getContent(), $response->getStatusCode());
            }

        } catch (TransportExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface|ClientExceptionInterface $e) {
            throw new KpyOrderMarginException($e->getResponse()->toArray(false)['message'] ?? $e->getMessage());
        }
    }
}