<?php


namespace Woohoo\GoapptivCoupon;


class Utils {

    /** @var string Encryption key */
    static $key = 'aW50aGViZWdpbm5pbmc=';

    /** @var float request time out duration */
    static $REQUEST_TIMEOUT = 10.0;

    /** @var int pagination default length */
    static $PAGE_LENGTH = 50;

    /**
     * Encrypt signature
     *
     * @param $signature
     * @return string
     */
    static function encryptSignature($signature) {
        return hash_hmac('sha512', $signature, env('WOOHOO_CLIENT_SECRET'));
    }

    /**
     * Sorts the parameters according to the ASCII table.
     *
     * @param array $params
     */
    static function sortParams(array &$params) {
        ksort($params);
        foreach ($params as $key => &$value) {
            $value = is_object($value) ? (array)$value : $value;
            if (is_array($value)) {
                Utils::sortParams($value);
            }
        }
    }

    /**
     * Encrypt string
     *
     * @param $value
     * @return false|string
     */
    static function encrypt($value) {
        if (!isset($value))
            return null;

        $encryptionKey = base64_decode(Utils::$key);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($value, 'aes-256-cbc', $encryptionKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt string
     *
     * @param $value
     * @return false|string
     */
    static function decrypt($value) {
        if (!isset($value))
            return null;

        $encryption_key = base64_decode(Utils::$key);
        list($encrypted_data, $iv) = explode('::', base64_decode($value), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }

    /**
     * Prepare json error
     *
     * @param $errors
     * @return array
     */
    public static function errorToJson($errors) {
        $jsonErrors = $errors;

        $newErrors = array();
        foreach ($jsonErrors as $key => $value) {
            $newErrors[$key] = $value[0];
        }

        return $newErrors;
    }
}
