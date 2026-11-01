<?php

namespace App\Services\Billing;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Проверка подписанных данных App Store (JWS) по цепочке x5c до Apple Root CA G3. */
class AppleJws
{
    // SHA-256 отпечаток https://www.apple.com/certificateauthority/AppleRootCA-G3.cer
    private const ROOT_FINGERPRINT = '63343abfb89a6a03ebb57e9b3f5fa7be7c4f5c756f3017b3a8c488c3653e9179';
    // Метки Apple на сертификатах: промежуточный WWDR и лист подписи App Store.
    private const INTERMEDIATE_OID = '1.2.840.113635.100.6.2.1';
    private const LEAF_OID = '1.2.840.113635.100.6.11.1';

    /** Payload проверенного JWS; null — не JWS или подпись не от Apple. */
    public function decode($jws): ?array
    {
        if (!is_string($jws) || substr_count($jws, '.') !== 2) {
            return null;
        }

        try {
            $header = json_decode(JWT::urlsafeB64Decode(explode('.', $jws)[0]), true);
            $leaf = $this->verifiedLeaf($header['x5c'] ?? null);
            return $leaf ? (array) JWT::decode($jws, new Key($leaf, 'ES256')) : null;
        } catch (Throwable $e) {
            Log::warning('Apple JWS rejected', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** Лист цепочки [лист, промежуточный, корень] в PEM; null — цепочка не от Apple. */
    private function verifiedLeaf($chain): ?string
    {
        if (!is_array($chain) || count($chain) !== 3) {
            return null;
        }

        $pems = array_map(
            fn ($der) => "-----BEGIN CERTIFICATE-----\n" . chunk_split($der, 64, "\n") . "-----END CERTIFICATE-----\n",
            $chain,
        );
        [$leaf, $intermediate, $root] = $pems;

        $intact = openssl_x509_fingerprint($root, 'sha256') === self::ROOT_FINGERPRINT
            && openssl_x509_verify($intermediate, openssl_pkey_get_public($root)) === 1
            && openssl_x509_verify($leaf, openssl_pkey_get_public($intermediate)) === 1;
        if (!$intact) {
            return null;
        }

        foreach ($pems as $pem) {
            $cert = openssl_x509_parse($pem);
            if ($cert['validFrom_time_t'] > time() || $cert['validTo_time_t'] < time()) {
                return null;
            }
        }

        return isset(openssl_x509_parse($leaf)['extensions'][self::LEAF_OID],
            openssl_x509_parse($intermediate)['extensions'][self::INTERMEDIATE_OID]) ? $leaf : null;
    }
}
