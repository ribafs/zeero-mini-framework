<?php

namespace Zeero\Core\Crypto;

use InvalidArgumentException;
use Zeero\Core\Env;
use Zeero\Core\Exceptions\NotFoundException;
use Zeero\Core\Exceptions\SecureException;



/**
 * Crypto Class
 * 
 * 
 * @author carlos bumba <carlosbumbanio@gmail.com>
 */
abstract class Crypto
{
    private static $_method;
    private static $_blocks;
    private static $_bytes;
    // initialization vector length
    private static $_IvLength;
    // 
    private static $_key;

    public static function getKey()
    {
        return self::$_key ?? Env::get('APP_KEY');
    }

    public static function setConfigs(int $bytes, int $blocks)
    {
        self::$_blocks = $blocks;
        self::$_bytes = $bytes;
    }

    
    public static function setMethod(string $method)
    {
        if (!in_array($method, openssl_get_cipher_methods())) {
            throw new InvalidArgumentException("Undefined cipher method: {$method}");
        }

        self::$_IvLength = openssl_cipher_iv_length($method);

        self::$_method = $method;
    }

    public static function setKey(string $key)
    {
        if (strlen($key) == 0)
            throw new InvalidArgumentException("Invalid or Empty Encryption Key");

        self::$_key = $key;
    }

    /**
     * Encrypt a text
     *
     * @param string $plaintext
     * @throws Zeero\Core\Exceptions\SecureException
     * @return string
     */
    public static function encryptText(string $plaintext)
    {
        $strong = false;
        $password = self::getKey();
        $iv = openssl_random_pseudo_bytes(self::$_IvLength, $strong);

        if (!$strong) {
            throw new SecureException("IV not cryptographically strong!");
        }

        $raw = openssl_encrypt(
            $plaintext,
            self::$_method,
            $password,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        $hmac = hash_hmac(
            'sha256',
            $raw,
            $password,
            true
        );

        return base64_encode($iv . $hmac . $raw);
    }

    /**
     * Decrypt a encrypted text
     *
     * @param string $encrypted
     * @return string|boolean
     */
    public static function decryptText(string $encrypted)
    {
        $password = self::getKey();
        // the ciphertext
        $c = base64_decode($encrypted);
        $ivlen = self::$_IvLength;
        // extract the iv substring
        $iv = substr($c, 0, $ivlen);
        // 
        $hmac = substr($c, $ivlen, $sha2len = 32);
        // extract the ciphertext raw
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        // decrypt
        $original_plaintext = openssl_decrypt(
            $ciphertext_raw,
            self::$_method,
            $password,
            OPENSSL_RAW_DATA,
            $iv
        );

        $calcmac = hash_hmac(
            'sha256',
            $ciphertext_raw,
            $password,
            true
        );

        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        } else {
            return false;
        }
    }



    /**
     * Encrypt a file
     *
     * @param string $source
     * @param string $output
     * @param string|null $password
     * 
     * @throws Zeero\Core\Exceptions\NotFoundException if the source file not exists
     * @return string|boolean
     * 
     */
    public static function encryptFile(string $source, string $output, string $password = null)
    {
        if (!file_exists($source)) {
            throw new NotFoundException("File '$source' Not Found");
        }

        $error = false;
        $iv = openssl_random_pseudo_bytes(self::$_bytes);

        if ($fileOutput = fopen($output, 'w')) {
            // Put the initialzation vector to the beginning of the file
            fwrite($fileOutput, $iv);

            if ($fileSource = fopen($source, 'rb')) {
                while (!feof($fileSource)) {
                    $plaintext = fread($fileSource, self::$_bytes * self::$_blocks);

                    $ciphertext = openssl_encrypt(
                        $plaintext,
                        self::$_method,
                        $password ?? self::getKey(),
                        OPENSSL_RAW_DATA,
                        $iv
                    );

                    fwrite($fileOutput, $ciphertext);
                }
                fclose($fileSource);
            } else {
                $error = true;
            }
            fclose($fileOutput);
        } else {
            $error = true;
        }

        return $error ? false : $output;
    }


    /**
     * Decrypt a file
     *
     * @param string $source
     * @param string $output
     * @param string|null $password
     * 
     * @throws Zeero\Core\Exceptions\NotFoundException if the source file not exists
     * @return string|boolean
     */
    public static function decryptFile(string $source, string $output, string $password = null)
    {
        if (!file_exists($source)) {
            throw new NotFoundException("File '$source' Not Found");
        }


        $error = false;

        if ($fileOutput = fopen($output, 'w')) {
            if ($fileSource = fopen($source, 'rb')) {
                // Get the initialzation vector from the beginning of the file
                $iv = fread($fileSource, self::$_bytes);

                while (!feof($fileSource)) {
                    $ciphertext = fread($fileSource, self::$_bytes * (self::$_blocks + 1));

                    $plaintext = openssl_decrypt(
                        $ciphertext,
                        self::$_method,
                        $password ?? self::getKey(),
                        \OPENSSL_RAW_DATA,
                        $iv
                    );

                    fwrite($fileOutput, $plaintext);
                }
                fclose($fileSource);
            } else {
                $error = true;
            }
            fclose($fileOutput);
        } else {
            $error = true;
        }
        return $error ? false : $output;
    }
}
