<?php

// Phase 1 test: Google ID token verification (crypto + claim validation)
// Run: php.exe test_google_verify.php

require __DIR__ . "/google_verify.php";

$pass = 0;
$fail = 0;

function check($label, $condition) {
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "PASS: $label\n";
    } else {
        $fail++;
        echo "FAIL: $label\n";
    }
}

function b64url($data) {
    return rtrim(strtr(base64_encode($data), "+/", "-_"), "=");
}

// --- Setup: generate RSA keypair, build JWKS + signed token ---

$key = openssl_pkey_new([
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA
]);

$details = openssl_pkey_get_details($key);

$jwks = json_encode([
    "keys" => [[
        "kty" => "RSA",
        "kid" => "test-key-1",
        "use" => "sig",
        "alg" => "RS256",
        "n" => b64url($details["rsa"]["n"]),
        "e" => b64url($details["rsa"]["e"])
    ]]
]);

$jwksFile = sys_get_temp_dir() . "/test_google_jwks.json";
file_put_contents($jwksFile, $jwks);

$clientId = "test-client.apps.googleusercontent.com";

function make_token($claims, $key, $kid = "test-key-1") {
    $header = b64url(json_encode(["alg" => "RS256", "typ" => "JWT", "kid" => $kid]));
    $payload = b64url(json_encode($claims));
    $unsigned = $header . "." . $payload;
    openssl_sign($unsigned, $sig, $key, OPENSSL_ALGO_SHA256);
    return $unsigned . "." . b64url($sig);
}

$validClaims = [
    "iss" => "https://accounts.google.com",
    "aud" => $clientId,
    "sub" => "123456789012345678901",
    "email" => "jane.doe@gmail.com",
    "email_verified" => true,
    "name" => "Jane Doe",
    "given_name" => "Jane",
    "family_name" => "Doe",
    "iat" => time(),
    "exp" => time() + 3600
];

// --- Tests ---

$token = make_token($validClaims, $key);
$result = verify_google_id_token($token, $clientId, $jwksFile);
check("valid token returns claims", is_array($result) && ($result["email"] ?? "") === "jane.doe@gmail.com");

// email_verified as string "true" (Google sometimes sends strings)
$stringVerified = $validClaims;
$stringVerified["email_verified"] = "true";
$result = verify_google_id_token(make_token($stringVerified, $key), $clientId, $jwksFile);
check("email_verified='true' string accepted", is_array($result));

// email_verified false
$notVerified = $validClaims;
$notVerified["email_verified"] = false;
$result = verify_google_id_token(make_token($notVerified, $key), $clientId, $jwksFile);
check("email_verified=false rejected", $result === null);

// tampered payload (signature no longer matches)
$parts = explode(".", $token);
$tamperedPayload = b64url(json_encode(array_merge($validClaims, ["email" => "attacker@gmail.com"])));
$result = verify_google_id_token($parts[0] . "." . $tamperedPayload . "." . $parts[2], $clientId, $jwksFile);
check("tampered payload rejected", $result === null);

// wrong audience
$result = verify_google_id_token(make_token($validClaims, $key), "other-client.apps.googleusercontent.com", $jwksFile);
check("wrong audience rejected", $result === null);

// expired token
$expired = $validClaims;
$expired["exp"] = time() - 100;
$result = verify_google_id_token(make_token($expired, $key), $clientId, $jwksFile);
check("expired token rejected", $result === null);

// wrong issuer
$badIssuer = $validClaims;
$badIssuer["iss"] = "https://evil.example.com";
$result = verify_google_id_token(make_token($badIssuer, $key), $clientId, $jwksFile);
check("wrong issuer rejected", $result === null);

// unknown kid (different key)
$otherKey = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
$result = verify_google_id_token(make_token($validClaims, $otherKey), $clientId, $jwksFile);
check("token signed by unknown key rejected", $result === null);

// garbage input
check("garbage string rejected", verify_google_id_token("not-a-jwt", $clientId, $jwksFile) === null);
check("empty string rejected", verify_google_id_token("", $clientId, $jwksFile) === null);

// non-RS256 algorithm (e.g. none/HS256)
$hsHeader = b64url(json_encode(["alg" => "HS256", "typ" => "JWT", "kid" => "test-key-1"]));
$hsPayload = b64url(json_encode($validClaims));
$result = verify_google_id_token($hsHeader . "." . $hsPayload . "." . b64url("fakesig"), $clientId, $jwksFile);
check("non-RS256 alg rejected", $result === null);

// JWK -> PEM sanity: verify a signature using the converted PEM directly
$token2 = make_token($validClaims, $key);
$p = explode(".", $token2);
google_base64url_decode($p[2]); // ensure helper exists
$jwksDecoded = json_decode($jwks, true);
$pem = google_jwk_to_pem($jwksDecoded["keys"][0]);
$sig = google_base64url_decode($p[2]);
check("JWK->PEM converts to valid PEM", $pem !== null && str_contains($pem, "BEGIN PUBLIC KEY"));
check("PEM verifies signature via openssl_verify", openssl_verify($p[0] . "." . $p[1], $sig, $pem, OPENSSL_ALGO_SHA256) === 1);

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
