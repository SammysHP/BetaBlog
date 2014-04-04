<?php
namespace util;

session_start();

class AntiCSRF {
    const TOKEN_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const TOKEN_LENGTH = 32;
    const N_SESSION = 'csrf_';
    const N_KEY = '_csrf_key';
    const N_TOKEN = '_csrf_token';

    private $key;
    private $token;

    /**
     * Create a new key/token pair and save it in the current session.
     */
    public function __construct() {
        $key;
        do {
            $key = self::generateToken();
        } while ($_SESSION[self::N_SESSION . $key]);

        $this->key = $key;
        $this->token = self::generateToken();

        $_SESSION[self::N_SESSION . $key] = $this->token;
    }

    /**
     * Get the key of this pair.
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * Get the token of this pair.
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Get a string that can be inserted into an html <form>.
     */
    public function getPostString() {
        return '<input type="hidden" name="' . self::N_KEY . '" value="' . $this->key . '" />'
            . '<input type="hidden" name="' . self::N_TOKEN . '" value="' . $this->token . '" />';
    }

    /**
     * Get a string that can be appended to an URL.
     */
    public function getGetString() {
        return self::N_KEY . '=' . $this->key . '&' . self::N_TOKEN . '=' . $this->token;
    }

    /**
     * Verify a key/token pair.
     *
     * Both are loaded automatically from POST or GET parameters (in this order).
     * A match in the session will be deleted so this method can return only for
     * the fist invocation.
     */
    public static function verifyToken() {
        $key;
        $token;
        if ($_POST[self::N_KEY] && $_POST[self::N_TOKEN]) {
            $key = $_POST[self::N_KEY];
            $token = $_POST[self::N_TOKEN];
        } elseif ($_GET[self::N_KEY] && $_GET[self::N_TOKEN]) {
            $key = $_GET[self::N_KEY];
            $token = $_GET[self::N_TOKEN];
        } else {
            return false;
        }

        $sessionToken = $_SESSION[self::N_SESSION . $key];
        unset($_SESSION[self::N_SESSION . $key]);

        return !empty($sessionToken) && $sessionToken === $token;
    }

    /**
     * Exit with http status code 400 if no valid pair found.
     */
    public static function verifyOrFail() {
        if (!self::verifyToken()) {
            header('HTTP/1.0 400 Bad Request');
            echo 'CSRF ATTACK DETECTED!';
            die();
        }
    }

    /**
     * Generate a alphanumeric token.
     */
    private static function generateToken() {
        $chars = self::TOKEN_CHARACTERS;
        $maxRand = strlen($chars) - 1;

        $token = '';
        for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
            $token .= $chars{mt_rand(0, $maxRand)};
        }

        return $token;
    }
}
