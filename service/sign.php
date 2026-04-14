<?php

$pubKey = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC4MAtJOkbtlo3ZzkpDbH7Okk7jvMGs2Cw45od68M4EwYY6orTCQDcENGIlro8QBDjTqeaQb9O/aPgnxlChcqfoPIP8hAj5NRcTQZs0skwyy/3jVXDt+1XsLnrLSEXlNqFFKRQDMMEEZDKOznt4pVwSWdZidWatOgCP0xZC3vWIFQIDAQAB
-----END PUBLIC KEY-----';//Enter the public key string (without newlines)
$priKey = '';
$priKey = formatPriKey($priKey);


$params = array(
    
);//input parameter group


//Get preprocessed string
$signString = getSignString($params);
//Get signature
var_dump('String to be signed：');
var_dump($signString);
var_dump('Signature to be verified：');
$hexSign='Signature String';//Enter hexadecimal signature
var_dump($hexSign);
var_dump('View signature results：');
$sign = hexToStr($hexSign);

//Verify signature
$res = checkSign($pubKey,$sign,$signString);
var_dump($res);//The result is true
function strToHex($string) //Convert string to hexadecimal
{
    $hex = "";
    for ($i = 0; $i < strlen($string); $i++) {
        $hex .= dechex(ord($string[$i]));
    }

    $hex = strtoupper($hex);
    return $hex;
}

function hexToStr($hex) //Hexadecimal to string
{
    $string = "";
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }

    return $string;
}

/**
 * Generate signature
 * @param    string     $signString String to be signed
 * @param    [type]     $priKey     private key
 * @return   string     base64 result value
 */
function getSign($signString,$priKey){
    $privKeyId = openssl_pkey_get_private($priKey);
    $signature = '';
    openssl_sign($signString, $signature, $privKeyId);
    openssl_free_key($privKeyId);
    return base64_encode($signature);
}

/**
 * Verify signature
 * @param    string     $pubKey public key
 * @param    string     $sign   sign
 * @param    string     $toSign String to be signed
 * @param    string     $signature_alg Signature method such as sha1WithRSAEncryption or sha512
 * @return   bool
 */
function checkSign($pubKey,$sign,$toSign,$signature_alg=OPENSSL_ALGO_SHA1){
    $publicKeyId = openssl_pkey_get_public($pubKey);
    $result = openssl_verify($toSign, base64_decode($sign), $publicKeyId,$signature_alg);
    openssl_free_key($publicKeyId);
    return $result === 1 ? true : false;
}

/**
 * Get the string to be signed
 * @param    array     $params parameter array
 * @return   string
 */
function getSignString($params){
    unset($params['sign']);
    ksort($params);
    reset($params);

    $pairs = array();
    foreach ($params as $k => $v) {
        if(!empty($v)){
            $pairs[] = "$k=$v";
        }
    }

    return implode('&', $pairs);
}

/**
 * Format private key
 */
function formatPriKey($priKey) {
    $fKey = "-----BEGIN PRIVATE KEY-----\n";
    $len = strlen($priKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($priKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END PRIVATE KEY-----";
    return $fKey;
}

/**
 * Format public key
 */
function formatPubKey($pubKey) {
    $fKey = "-----BEGIN PUBLIC KEY-----\n";
    $len = strlen($pubKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END PUBLIC KEY-----";
    return $fKey;
}
?>
