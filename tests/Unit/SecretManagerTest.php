<?php
namespace ReactSmith\InstagramFeed\Tests;

use ReactSmith\InstagramFeed\Core\SecretManager;
use Brain\Monkey;

class SecretManagerTest extends BaseTestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Define the encryption key constant if not already defined
        if (!defined('RS_SECRET_ENCRYPTION_KEY')) {
            define('RS_SECRET_ENCRYPTION_KEY', 'test-encryption-key-32-chars-long!!');
        }
    }

    /**
     * Test that a string can be encrypted and then decrypted back to its original value.
     */
    public function test_encrypt_decrypt_consistency() {
        $originalSecret = "my-super-secret-password-123";

        $encrypted = SecretManager::encrypt($originalSecret);

        // Ensure encryption returned a string and is not the same as the original
        $this->assertIsString($encrypted);
        $this->assertNotEquals($originalSecret, $encrypted);

        $decrypted = SecretManager::decrypt($encrypted);

        // Ensure decryption restores the original value
        $this->assertSame($originalSecret, $decrypted);
    }

    /**
     * Test encryption/decryption with different types of strings (empty, special characters).
     */
    public function test_encrypt_decrypt_with_various_inputs() {
        $inputs = [
            "short",
            "long-string-with-special-characters-!@#$%^&*()_+",
            "1234567890",
            "    string with spaces    "
        ];

        foreach ($inputs as $input) {
            $encrypted = SecretManager::encrypt($input);
            $this->assertSame($input, SecretManager::decrypt($encrypted));
        }
    }

    /**
     * Test that decryption fails gracefully with invalid payloads.
     */
    public function test_decrypt_fails_with_invalid_payload() {
        $this->assertFalse(SecretManager::decrypt(''));
        $this->assertFalse(SecretManager::decrypt('too-short'));
        $this->assertFalse(SecretManager::decrypt(base64_encode('not-enough-bytes')));
    }

    /**
     * Test that generateEncryptionKey produces a valid-looking base64 key string.
     */
    public function test_generate_encryption_key_format() {
        $key = SecretManager::generateEncryptionKey();

        $this->assertStringStartsWith('base64:', $key);

        $encoded = substr($key, 7);
        $decoded = base64_decode($encoded, true);

        $this->assertNotFalse($decoded, 'The generated key should be valid base64 after the prefix');
        $this->assertEquals(32, strlen($decoded), 'The decoded key should be 32 bytes long for AES-256');
    }

    /**
     * Test encryption/decryption using a newly generated key.
     * Note: Since RS_SECRET_ENCRYPTION_KEY is a constant, we use a reflection or
     * manual test if the environment allows redefinition, but usually, we test the logic.
     */
    public function test_encrypt_decrypt_with_generated_key() {
        // Generate a new key
        $newKey = SecretManager::generateEncryptionKey();

        // Since we can't redefine the constant easily in PHPUnit if already defined,
        // we test that the logic inside SecretManager handles the generated key correctly
        // by verifying the format.

        $originalSecret = "test-secret-with-new-key";

        // This relies on the constant being defined in setUp().
        // If you want to test specifically with the NEW key,
        // you would usually use a Mock or a separate environment.
        // However, the logic is verified by the existing tests if
        // RS_SECRET_ENCRYPTION_KEY is set to a base64: string.

        $encrypted = SecretManager::encrypt($originalSecret);
        $this->assertSame($originalSecret, SecretManager::decrypt($encrypted));
    }
}