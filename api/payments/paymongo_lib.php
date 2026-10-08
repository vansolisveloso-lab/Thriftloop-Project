<?php

// Shared PayMongo helpers: API calls and webhook signature checks.
// HTTP uses file_get_contents + stream context, same style as auth/google_verify.php.

require_once __DIR__ . "/../config/paymongo.php";


// Calls the PayMongo API and returns [status_code, decoded_body].
// Status 0 means the API could not be reached at all.
function paymongo_api_request($method, $path, $body = null) {
    global $PAYMONGO_API_BASE, $PAYMONGO_SECRET_KEY;

    $headers = "Authorization: Basic " . base64_encode($PAYMONGO_SECRET_KEY . ":") . "\r\n"
        . "Content-Type: application/json\r\n"
        . "Accept: application/json\r\n";

    $options = [
        "http" => [
            "method" => $method,
            "header" => $headers,
            "timeout" => 15,
            "ignore_errors" => true,
        ],
    ];

    if ($body !== null) {
        $options["http"]["content"] = json_encode($body);
    }

    $context = stream_context_create($options);

    $raw = @file_get_contents($PAYMONGO_API_BASE . $path, false, $context);

    if ($raw === false) {
        return [0, null];
    }

    $status = 0;

    foreach (($http_response_header ?? []) as $line) {
        if (preg_match("#^HTTP/\S+\s+(\d{3})#", $line, $matches)) {
            $status = (int) $matches[1];
        }
    }

    return [$status, json_decode($raw, true)];
}


// First human-readable error detail PayMongo returned, if any.
function paymongo_error_detail($body) {
    if (is_array($body)) {
        if (!empty($body["errors"][0]["detail"])) {
            return (string) $body["errors"][0]["detail"];
        }

        if (!empty($body["error"]["message"])) {
            return (string) $body["error"]["message"];
        }
    }

    return "";
}


// True when a retrieved checkout session actually collected the money:
// any payment with status "paid", or a payment intent that succeeded.
function paymongo_session_is_paid($session) {
    $attributes = $session["data"]["attributes"] ?? null;

    if (!is_array($attributes)) {
        return false;
    }

    foreach (($attributes["payments"] ?? []) as $payment) {
        if (($payment["attributes"]["status"] ?? "") === "paid") {
            return true;
        }
    }

    return ($attributes["payment_intent"]["attributes"]["status"] ?? "") === "succeeded";
}


// Amount charged for the session, in centavos (0 when unknown).
function paymongo_session_amount($session) {
    $attributes = $session["data"]["attributes"] ?? null;

    if (!is_array($attributes)) {
        return 0;
    }

    $amount = (int) ($attributes["payment_intent"]["attributes"]["amount"] ?? 0);

    if ($amount > 0) {
        return $amount;
    }

    foreach (($attributes["payments"] ?? []) as $payment) {
        $amount = (int) ($payment["attributes"]["amount"] ?? 0);

        if ($amount > 0) {
            return $amount;
        }
    }

    return 0;
}


// Recursively finds the first non-array value stored under $key.
// Used to pull reference_number / event types out of webhook payloads
// regardless of how PayMongo nests them.
function paymongo_find_value($data, $key) {
    if (!is_array($data)) {
        return null;
    }

    if (array_key_exists($key, $data) && !is_array($data[$key])) {
        return $data[$key];
    }

    foreach ($data as $value) {
        $found = paymongo_find_value($value, $key);

        if ($found !== null) {
            return $found;
        }
    }

    return null;
}


// Verifies the Paymongo-Signature header: t=<timestamp>,te=<test sig>,li=<live sig>.
// Each signature is HMAC-SHA256(secret, timestamp + "." + rawBody).
function paymongo_verify_webhook_signature($rawBody, $header, $secret) {
    if (
        !is_string($rawBody)
        || !is_string($header) || $header === ""
        || !is_string($secret) || $secret === ""
    ) {
        return false;
    }

    $parts = [];

    foreach (explode(",", $header) as $chunk) {
        $pair = explode("=", trim($chunk), 2);

        if (count($pair) === 2) {
            $parts[$pair[0]] = $pair[1];
        }
    }

    $timestamp = $parts["t"] ?? "";

    if ($timestamp === "") {
        return false;
    }

    $expected = hash_hmac("sha256", $timestamp . "." . $rawBody, $secret);

    $valid = false;

    foreach (["te", "li"] as $candidate) {
        if (!empty($parts[$candidate]) && hash_equals($expected, $parts[$candidate])) {
            $valid = true;
        }
    }

    return $valid;
}
