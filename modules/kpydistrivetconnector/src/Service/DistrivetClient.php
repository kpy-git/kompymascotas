<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Service;

use PrestaShop\Module\KpyDistrivetConnector\Config\Config;
use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetOrderDTO;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Logger\DistrivetLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistrivetClient
{
    private string $clientId;

    private string $clientSecret;

    private string $apiDomain;

    private string $apiAuthPath;

    private HttpClientInterface $client;

    public function __construct()
    {
        $this->clientId = \Configuration::get(Config::KPY_DISTRIVET_CLIENT);
        $this->clientSecret = \Configuration::get(Config::KPY_DISTRIVET_SECRET);
        $this->apiAuthPath = \Configuration::get(Config::KPY_DISTRIVET_API_AUTH_PATH);
        $this->apiDomain = \Configuration::get(Config::KPY_DISTRIVET_API_DOMAIN);

        $this->client = HttpClient::create();
    }

    /**
     * @throws KpyDistrivetException
     */
    public function getStock(int $pageSize = 10000): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = $this->client->request('GET', $this->apiDomain . '/v1/stocks', [
                'auth_bearer' => $accessToken,
                'query' => [
                    'page_size' => $pageSize,
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new KpyDistrivetException($response->getContent(), $response->getStatusCode());
            }

            $data = $response->toArray();

            return [
                'total_count' => $data['pagination']['total_count'],
                'stocks' => $data['data'],
            ];

        } catch (RedirectionExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|TransportExceptionInterface|ServerExceptionInterface $e) {
            throw new KpyDistrivetException($e->getMessage() . "\n" . $e->getResponse()->toArray(false)['message'] ?? '', $e->getCode(), $e);
        }

    }

    /**
     * @throws KpyDistrivetException
     */
    private function getAccessToken(): string
    {
        try {
            $response = $this->client->request('POST', $this->apiAuthPath,
                [
                    'auth_basic' => [$this->clientId, $this->clientSecret],
                    'body' => [
                        'grant_type' => 'client_credentials',
                        'scope' => 'api/orders api/stocks api/products api/shipments',
                    ]
                ]
            );

            if ($response->getStatusCode() !== 200) {
                throw new KpyDistrivetException($response->getContent(), $response->getStatusCode());
            }

            return $response->toArray()['access_token'] ?? '';

        } catch (RedirectionExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|TransportExceptionInterface|ServerExceptionInterface $e) {
            throw new KpyDistrivetException($e->getMessage() . "\n" . $e->getResponse()->toArray(false)['message'] ?? '', $e->getCode(), $e);
        }
    }

    /**
     * @throws KpyDistrivetException
     */
    public function createOrder(DistrivetOrderDTO $order): string
    {
        try {
            $response = $this->client->request('POST', $this->apiDomain . '/v1/orders/', [
                'auth_bearer' => $this->getAccessToken(),
                'json' => $order,
            ]);

            $data = $response->toArray();

            if (Config::DEBUG_MODE) {
                DistrivetLogger::logRequest($order);
                DistrivetLogger::logResponse($response);
            }

            return $data['OrderNo'];

        } catch (RedirectionExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|TransportExceptionInterface|ServerExceptionInterface $e) {
            DistrivetLogger::logRequest($order);
            DistrivetLogger::logResponse($e->getResponse());
            throw new KpyDistrivetException($e->getMessage() . "\n" . $e->getResponse()->toArray(false)['message'] ?? '', $e->getCode(), $e);
        }
    }
}