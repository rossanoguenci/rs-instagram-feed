<?php
namespace ReactSmith\InstagramFeed\Core;

/**
 * Manager for handling encryption and decryption of secrets.
 * Uses AES-256-CBC encryption with a key defined in wp-config.php.
 */
final class SecretManager {

    /** @var string The encryption method used. */
    const METHOD = 'AES-256-CBC';

    /**
     * Encrypts a secret value.
     *
     * @param string $secret The raw secret value to encrypt.
     * @return string|false The encrypted payload as a base64 string, or false on failure.
     */
    public static function encrypt(string $secret): string|false {
        $encryptionKey = self::getEncryptionKey();
        if (!$encryptionKey) return false;

        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($secret, self::METHOD, $encryptionKey, 0, $iv);
        if ($encrypted === false) return false;

        $payload = base64_encode($iv . $encrypted);
        return $payload;
    }

    /**
     * Decrypts an encrypted payload.
     *
     * @param string $payload The base64-encoded encrypted payload.
     * @return string|false The decrypted raw secret, or false on failure.
     */
    public static function decrypt(string $payload): string|false {
        if (!$payload) return false;

        $payload = base64_decode($payload);
        if (!$payload || strlen($payload) < 16) return false;

        $iv = substr($payload, 0, 16);
        $encrypted = substr($payload, 16);

        $encryptionKey = self::getEncryptionKey();
        if (!$encryptionKey) return false;

        $secret = openssl_decrypt($encrypted, self::METHOD, $encryptionKey, 0, $iv);
        return $secret ?: false;
    }

    /**
     * Retrieves the encryption key from wp-config.php.
     *
     * Handles base64-encoded binary data or raw strings (hashed to 32 bytes).
     *
     * @return string|null The binary encryption key, or null if not defined.
     */
    private static function getEncryptionKey(): ?string {
        if (!defined('RS_SECRET_ENCRYPTION_KEY')) {
            return null;
        }

        $v = RS_SECRET_ENCRYPTION_KEY;

        // If the key is base64-encoded binary data
        if (str_starts_with($v, 'base64:')) {
            return base64_decode(substr($v, 7));
        }

        // Fallback: If it's a raw string, we hash it to ensure it's exactly 32 bytes for AES-256
        return hash('sha256', $v, true);
    }

    /**
     * Generate a new secure encryption key.
     *
     * @return string The formatted key for wp-config.php
     */
    public static function generateEncryptionKey(): string {
        // Generate 32 bytes of high-entropy random data
        $bytes = random_bytes(32);

        // Return with base64: prefix for safe storage in wp-config.php
        return 'base64:' . base64_encode($bytes);
    }
}
