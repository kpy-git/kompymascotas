<?php
/**
 * IndexNow: avisar a los buscadores que lo usan (Bing, Yandex, Seznam, Naver…) de las URL que han
 * cambiado. La clave se genera al instalar y se publica en la raíz de la tienda (`/<clave>.txt`, servida
 * por el controlador `indexnow` del módulo: en `modules/` Apache no deja leer ficheros .txt). Las URL se
 * acumulan durante la petición y se mandan al cerrarla, para no retrasar al que guarda en el back office.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoIndexNow
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    /** @var array<string, true> */
    private static array $cola = [];
    private static bool $registrado = false;

    public static function clave(): string
    {
        return KseoAjustes::texto('KPYSEO_INDEXNOW_CLAVE');
    }

    public static function prepararClave(): void
    {
        if (!preg_match('~^[a-f0-9]{32}$~', self::clave())) {
            Configuration::updateValue('KPYSEO_INDEXNOW_CLAVE', bin2hex(random_bytes(16)));
        }
    }

    /** Dónde está publicada la clave: en la raíz, como pide IndexNow por defecto. */
    public static function urlClave(): string
    {
        return Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . self::clave() . '.txt';
    }

    public static function encolar(string $url): void
    {
        if ($url === '' || !KseoAjustes::activo('KPYSEO_INDEXNOW') || self::clave() === '') {
            return;
        }
        self::$cola[$url] = true;
        if (!self::$registrado) {
            self::$registrado = true;
            register_shutdown_function([self::class, 'enviarCola']);
        }
    }

    public static function enviarCola(): void
    {
        if (!self::$cola) {
            return;
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $urls = array_keys(self::$cola);
        self::$cola = [];
        self::enviar($urls);
    }

    /**
     * @param string[] $urls
     *
     * @return array{ok: bool, enviadas: int, codigo: string}
     */
    public static function enviar(array $urls): array
    {
        $urls = array_values(array_unique(array_filter($urls, static fn ($u) => is_string($u) && $u !== '')));
        $clave = self::clave();
        if (!$urls || $clave === '') {
            return ['ok' => false, 'enviadas' => 0, 'codigo' => 'nada que enviar'];
        }
        $host = (string) (parse_url($urls[0], PHP_URL_HOST) ?: Tools::getShopDomainSsl());
        $codigos = [];
        $enviadas = 0;
        foreach (array_chunk($urls, 10000) as $trozo) {
            $cuerpo = json_encode([
                'host' => $host,
                'key' => $clave,
                'keyLocation' => self::urlClave(),
                'urlList' => $trozo,
            ], JSON_UNESCAPED_SLASHES);
            $ch = curl_init(self::ENDPOINT);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
                CURLOPT_POSTFIELDS => $cuerpo,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
            ]);
            curl_exec($ch);
            $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            $codigos[] = $codigo > 0 ? (string) $codigo : 'error: ' . $error;
            if ($codigo >= 200 && $codigo < 300) {
                $enviadas += count($trozo);
            }
        }
        $resumen = date('d/m/Y H:i') . ' · ' . count($urls) . ' URL · respuesta ' . implode(', ', $codigos);
        Configuration::updateValue('KPYSEO_INDEXNOW_ULTIMO', $resumen);

        return ['ok' => $enviadas > 0, 'enviadas' => $enviadas, 'codigo' => implode(', ', $codigos)];
    }
}
