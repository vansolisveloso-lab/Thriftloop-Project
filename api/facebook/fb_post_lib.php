<?php

// Facebook Page posting helpers.
//
// Posts the freshly uploaded product photo straight to the configured Page.
// Uses a multipart POST to /{page-id}/photos with the image BYTES (not a URL),
// so it works from localhost where Facebook cannot fetch our image URLs.
//
// Everything here is best-effort: the upload endpoint must keep succeeding
// even when Facebook is unreachable, unconfigured, or rejects the token.

require_once __DIR__ . "/../config/facebook.php";


// Multipart POST to the Graph API. Returns [status_code, decoded_body].
// Status 0 means the API could not be reached at all.
function fb_api_multipart_post($path, array $fields, $filePath = null) {
    global $FACEBOOK_API_BASE;

    $boundary = "fb" . bin2hex(random_bytes(16));

    $body = "";

    foreach ($fields as $name => $value) {
        $body .= "--" . $boundary . "\r\n"
            . "Content-Disposition: form-data; name=\"" . $name . "\"\r\n\r\n"
            . (string) $value . "\r\n";
    }

    if ($filePath !== null) {

        $fileContents = @file_get_contents($filePath);

        if ($fileContents === false) {
            return [0, null];
        }

        $mime = function_exists("mime_content_type") ? @mime_content_type($filePath) : false;

        if (!$mime) {
            $mime = "application/octet-stream";
        }

        $body .= "--" . $boundary . "\r\n"
            . "Content-Disposition: form-data; name=\"source\"; filename=\""
            . basename($filePath) . "\"\r\n"
            . "Content-Type: " . $mime . "\r\n\r\n"
            . $fileContents . "\r\n";
    }

    $body .= "--" . $boundary . "--\r\n";

    $options = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: multipart/form-data; boundary=" . $boundary,
            "content" => $body,
            "timeout" => 8,
            "ignore_errors" => true,
        ],
    ];

    $context = stream_context_create($options);

    $raw = @file_get_contents($FACEBOOK_API_BASE . $path, false, $context);

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


// First human-readable error detail the Graph API returned, if any.
function fb_error_detail($body) {
    if (is_array($body) && !empty($body["error"]["message"])) {
        return (string) $body["error"]["message"];
    }

    return "";
}


/**
 * Post one product photo to the configured Facebook Page.
 *
 * Returns:
 *   ["status" => "disabled"]                        not configured yet
 *   ["status" => "posted",  "post_id" => "..."]      published
 *   ["status" => "failed",  "error" => "..."]        Facebook said no
 */
function fb_post_product_photo($imagePath, $caption) {

    if (!facebook_is_configured()) {
        return ["status" => "disabled"];
    }

    if (!is_string($imagePath) || $imagePath === "" || !is_file($imagePath)) {
        return ["status" => "failed", "error" => "IMAGE FILE NOT FOUND."];
    }

    global $FACEBOOK_PAGE_ID, $FACEBOOK_PAGE_TOKEN, $FACEBOOK_API_VERSION;

    [$status, $body] = fb_api_multipart_post(
        "/" . $FACEBOOK_API_VERSION . "/" . rawurlencode($FACEBOOK_PAGE_ID) . "/photos",
        [
            "message" => $caption,
            "access_token" => $FACEBOOK_PAGE_TOKEN,
        ],
        $imagePath
    );

    if ($status >= 200 && $status < 300 && !empty($body["id"])) {
        return [
            "status" => "posted",
            "post_id" => (string) ($body["post_id"] ?? ""),
        ];
    }

    $detail = fb_error_detail($body);

    return [
        "status" => "failed",
        "error" => $status === 0
            ? "FACEBOOK COULD NOT BE REACHED."
            : ($detail !== "" ? $detail : "HTTP " . $status . "."),
    ];
}
