<?php
namespace Zeaven\EasySuit\Services;

class CryptJson
{
    private string $key = '';
    public function __construct(string $key)
    {
        $this->key = $key;
    }
    
    function encrypt(mixed $data): string {
        $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);
        $plainBytes = array_map('ord', str_split($plaintext));
        $keyBytes = array_map('ord', str_split($this->key));

        $cipherBytes = [];
        $kLen = count($keyBytes);

        foreach ($plainBytes as $i => $b) {
            $cipherBytes[] = ($b + $keyBytes[$i % $kLen]) % 256;
        }

        // 转回二进制字符串
        $binary = implode(array_map("chr", $cipherBytes));

        return base64_encode($binary);
    }

    function decrypt(string $cipher): array {
        $binary = base64_decode($cipher);
        $cipherBytes = array_map('ord', str_split($binary));
        $keyBytes = array_map('ord', str_split($this->key));

        $plainBytes = [];
        $kLen = count($keyBytes);

        foreach ($cipherBytes as $i => $b) {
            $plainBytes[] = ($b - $keyBytes[$i % $kLen] + 256) % 256;
        }

        $plainText = implode(array_map("chr", $plainBytes));

        return json_decode($plainText, true);
    }

}
