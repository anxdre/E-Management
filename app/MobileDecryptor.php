<?php

namespace App;

trait MobileDecryptor
{
    function decryptAES($encryptedText, $key)
    {
        $cipher = 'AES-256-CBC';
        $data = base64_decode($encryptedText);

        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

    function decodeDeviceToken(string $encryptedToken): ?array
    {
        try {
            $step1 = $this->decryptAES($encryptedToken, env('MOBILE_KEY'));
            $step2 = decrypt($step1);

            $parts = explode('-', $step2);
            if (count($parts) < 3) return null;

            return [
                'prefix' => $parts[0],
                'date'   => $parts[1],
                'user_id' => $parts[2],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
