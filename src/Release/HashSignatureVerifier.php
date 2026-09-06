<?php

declare(strict_types=1);

namespace Richness\RichAddons\Release;

class HashSignatureVerifier implements ReleaseSignatureVerifier
{
    public function verifyFile(string $path, string $expectedChecksum, string $signature): bool
    {
        if (! is_file($path) || $expectedChecksum === '' || $signature === '') {
            return false;
        }

        $actualChecksum = hash_file('sha256', $path);

        if (! is_string($actualChecksum) || ! hash_equals(strtolower($expectedChecksum), strtolower($actualChecksum))) {
            return false;
        }

        $publicKey = (string) config('rich-addons.release_public_key', '');

        if ($publicKey !== '' && function_exists('openssl_verify')) {
            $decodedSignature = base64_decode($signature, true);

            return $decodedSignature !== false
                && openssl_verify($actualChecksum, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        }

        $secret = (string) config('rich-addons.secret_key', '');

        if ($secret === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $actualChecksum, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
