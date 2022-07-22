<?php

namespace Zeero\Core\Crypto;


/**
 * Hashing class
 * 
 * a simple class that contains utils functions for hashing passwords
 * 
 */
abstract class Hashing
{

    /**
     * generate a random id
     *
     * @return string
     */
    public static function random_id(): string
    {
        $id = bin2hex(random_bytes(4));

        for ($i = 1; $i <= 3; $i++) {
            $id .= '-' . bin2hex(random_bytes(2));
        }

        $id .= '-' . bin2hex(random_bytes(6));

        return $id;
    }


    /**
     * Generate a Random Token
     *
     * @param integer $length
     * @return string
     */
    public static function random_token(int $length = 16): string
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    /**
     * Generate a hash text using the native password_hash() function
     *
     * @param string $plaintext
     * @param integer $algo the algorithm id
     * 
     * 1 for PASSWORD_BCRYPT
     * 
     * 2 for PASSWORD_DEFAULT
     * @param integer $cost
     * @return string
     */
    public static function hash(string $plaintext, int $algo = 2, int $cost = 11): string
    {
        $options = ["cost" => $cost];

        if ($algo == 2)
            return \password_hash($plaintext, \PASSWORD_BCRYPT, $options);
        else
            return \password_hash($plaintext, \PASSWORD_DEFAULT, $options);
    }


    /**
     * Verifiy a hash string with a plaintext
     *
     * @param string $plaintext
     * @param string $hashed
     * @return boolean
     */
    public static function verify_hash(string $plaintext, string $hashed): bool
    {
        return password_verify($plaintext, $hashed);
    }
}
