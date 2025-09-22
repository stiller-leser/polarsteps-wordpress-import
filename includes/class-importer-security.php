<?php
// class-polarsteps-token.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Polarsteps_Importer_Security {

    /**
     * Verschlüsselt einen Token (AES-256-CBC + HMAC).
     *
     * @param string $plaintext
     * @return string|false
     */
    public static function encrypt( $plaintext ) {
        if ( $plaintext === '' ) return '';
        if ( ! function_exists('openssl_encrypt') ) return false;

        $key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true);
        $iv  = random_bytes(16);

        $cipher = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
        if ( $cipher === false ) return false;

        $hmac = hash_hmac( 'sha256', $iv . $cipher, $key, true );
        return base64_encode( $iv . $hmac . $cipher );
    }

    /**
     * Entschlüsselt einen zuvor gespeicherten Token.
     *
     * @param string $payload
     * @return string|false
     */
    public static function decrypt( $payload ) {
        if ( $payload === '' ) return '';
        if ( ! function_exists('openssl_decrypt') ) return false;

        $data = base64_decode( $payload );
        if ( $data === false || strlen( $data ) < 48 ) return false;

        $iv     = substr( $data, 0, 16 );
        $hmac   = substr( $data, 16, 32 );
        $cipher = substr( $data, 48 );

        $key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true);
        if ( ! hash_equals( $hmac, hash_hmac('sha256', $iv . $cipher, $key, true) ) ) {
            return false; // Integritätsfehler
        }

        return openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
    }
}
