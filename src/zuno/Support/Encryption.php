<?php

namespace Zuno\Support;

use RuntimeException;

class Encryption
{
    /**
     * Encrypt data using AES-256-CBC encryption.
     *
     * @param mixed $data The data to encrypt. Can be a string or an array.
     * @return string Returns a base64-encoded string containing the encrypted data and IV.
     * @throws RuntimeException If encryption fails.
     */
    public function encrypt(mixed $data): string
    {
        $cipher = config('app.cipher') ?? 'AES-256-CBC';

        $key = base64_decode(getenv('APP_KEY'));

        if (is_array($data)) {
            $data = json_encode($data);
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

        $encryptedData = openssl_encrypt($data, $cipher, $key, 0, $iv);

        if ($encryptedData === false) {
            throw new RuntimeException('Encryption failed');
        }

        // Combine the encrypted data and IV,
        // Then base64-encode the result for safe storage/transmission
        return base64_encode($encryptedData . '::' . $iv);
    }

    /**
     * Decrypt data that was encrypted using AES-256-CBC.
     *
     * @param string $encryptedData The base64-encoded encrypted data string (including IV).
     * @return mixed Returns the decrypted data as a string or array (if the original data was an array).
     * @throws RuntimeException If decryption fails.
     */
    public function decrypt(string $encryptedData): mixed
    {
        $cipher = config('app.cipher') ?? 'AES-256-CBC';

        $key = base64_decode(getenv('APP_KEY'));

        $data = base64_decode($encryptedData);

        [$encryptedData, $iv] = explode('::', $data, 2);

        $decryptedData = openssl_decrypt($encryptedData, $cipher, $key, 0, $iv);

        if ($decryptedData === false) {
            throw new RuntimeException('Decryption failed');
        }

        $decodedData = json_decode($decryptedData, true);

        // Return the decrypted data as an array (if JSON decoding succeeded) or as a string
        return $decodedData ? $decodedData : $decryptedData;
    }
}
