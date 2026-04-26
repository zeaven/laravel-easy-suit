<?php

namespace Zeaven\EasySuit\Services;


class AesCipher
{
    public static function encrypt(array $data, string $key): array
    {
        $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);
        $iv = random_bytes(12);

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            base64_decode($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return [
            'cipher' => base64_encode($ciphertext),
            'iv'     => base64_encode($iv),
            'tag'    => base64_encode($tag),
        ];
    }

    public static function decrypt(string $cipher, string $iv, string $tag, string $key): array
    {
        $plaintext = openssl_decrypt(
            base64_decode($cipher),
            'aes-256-gcm',
            base64_decode($key),
            OPENSSL_RAW_DATA,
            base64_decode($iv),
            base64_decode($tag)
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Invalid cipher payload');
        }

        return json_decode($plaintext, true);
    }
}
