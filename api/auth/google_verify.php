<?php

// Google ID token (JWT / RS256) verification helpers.


function google_base64url_decode($data) {
    return base64_decode(strtr($data, "-_", "+/"));
}


function der_length($length) {
    if ($length < 0x80) {
        return chr($length);
    }

    if ($length < 0x100) {
        return "\x81" . chr($length);
    }

    return "\x82" . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
}


function der_sequence($content) {
    return "\x30" . der_length(strlen($content)) . $content;
}


function der_integer($bytes) {
    return "\x02" . der_length(strlen($bytes)) . $bytes;
}


function der_bit_string($bytes) {
    return "\x03" . der_length(strlen($bytes) + 1) . "\x00" . $bytes;
}


function google_jwk_to_pem($jwk) {

    if (($jwk["kty"] ?? "") !== "RSA" || empty($jwk["n"]) || empty($jwk["e"])) {
        return null;
    }

    $modulus = google_base64url_decode($jwk["n"]);

    $exponent = google_base64url_decode($jwk["e"]);

    if ($modulus === false || $exponent === false) {
        return null;
    }

    $modulus = ltrim($modulus, "\x00");

    if ($modulus === "") {
        return null;
    }

    if ((ord($modulus[0]) & 0x80) !== 0) {
        $modulus = "\x00" . $modulus;
    }

    $exponent = ltrim($exponent, "\x00");

    if ($exponent === "") {
        return null;
    }

    if ((ord($exponent[0]) & 0x80) !== 0) {
        $exponent = "\x00" . $exponent;
    }

    $rsaKey = der_sequence(
        der_integer($modulus) . der_integer($exponent)
    );

    // rsaEncryption OID (1.2.840.113549.1.1.1) + NULL parameters
    $algorithmId = hex2bin("300d06092a864886f70d0101010500");

    $keyDer = der_sequence(
        $algorithmId . der_bit_string($rsaKey)
    );

    return "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode($keyDer), 64, "\n")
        . "-----END PUBLIC KEY-----\n";
}


function google_fetch_jwks($url, $forceRefresh = false) {

    $cacheFile = __DIR__ . "/../config/google_jwks_cache.json";

    $cached = null;

    if (is_file($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
    }

    if (
        !$forceRefresh
        && is_array($cached)
        && isset($cached["fetched_at"])
        && (time() - (int) $cached["fetched_at"]) < 3600
        && !empty($cached["keys"])
        && is_array($cached["keys"])
    ) {
        return $cached["keys"];
    }

    $context = stream_context_create([
        "http" => ["timeout" => 5]
    ]);

    $raw = @file_get_contents($url, false, $context);

    if ($raw !== false) {
        $data = json_decode($raw, true);

        if (is_array($data) && !empty($data["keys"]) && is_array($data["keys"])) {

            @file_put_contents($cacheFile, json_encode([
                "fetched_at" => time(),
                "keys" => $data["keys"]
            ]));

            return $data["keys"];
        }
    }

    // Fall back to the last known keys if Google could not be reached.
    if (is_array($cached) && !empty($cached["keys"]) && is_array($cached["keys"])) {
        return $cached["keys"];
    }

    return [];
}


function verify_google_id_token($token, $clientId, $jwksUrl) {

    $parts = explode(".", $token);

    if (count($parts) !== 3) {
        return null;
    }

    [$headerB64, $payloadB64, $signatureB64] = $parts;

    $header = json_decode(google_base64url_decode($headerB64), true);

    if (
        !is_array($header)
        || ($header["alg"] ?? "") !== "RS256"
        || empty($header["kid"])
    ) {
        return null;
    }

    $jwk = null;

    foreach (google_fetch_jwks($jwksUrl) as $key) {
        if (($key["kid"] ?? "") === $header["kid"]) {
            $jwk = $key;
            break;
        }
    }

    // The key may have been rotated since the cache was written.
    if ($jwk === null) {
        foreach (google_fetch_jwks($jwksUrl, true) as $key) {
            if (($key["kid"] ?? "") === $header["kid"]) {
                $jwk = $key;
                break;
            }
        }
    }

    if ($jwk === null) {
        return null;
    }

    $pem = google_jwk_to_pem($jwk);

    if ($pem === null) {
        return null;
    }

    $signature = google_base64url_decode($signatureB64);

    $verified = openssl_verify(
        $headerB64 . "." . $payloadB64,
        $signature,
        $pem,
        OPENSSL_ALGO_SHA256
    );

    if ($verified !== 1) {
        return null;
    }

    $claims = json_decode(google_base64url_decode($payloadB64), true);

    if (!is_array($claims)) {
        return null;
    }

    $issuer = $claims["iss"] ?? "";

    if ($issuer !== "https://accounts.google.com" && $issuer !== "accounts.google.com") {
        return null;
    }

    if (($claims["aud"] ?? "") !== $clientId) {
        return null;
    }

    if (empty($claims["exp"]) || (int) $claims["exp"] < time()) {
        return null;
    }

    if (empty($claims["sub"])) {
        return null;
    }

    if (empty($claims["email"])) {
        return null;
    }

    $emailVerified = $claims["email_verified"] ?? false;

    if ($emailVerified !== true && $emailVerified !== "true") {
        return null;
    }

    return $claims;
}
