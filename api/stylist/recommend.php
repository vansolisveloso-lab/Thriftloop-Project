<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "METHOD NOT ALLOWED."
    ]);

    exit;
}

require_once __DIR__ . "/../config/openai.php";

if (
    !isset($OPENAI_API_KEY) ||
    empty($OPENAI_API_KEY) ||
    $OPENAI_API_KEY === "sk-f25c5ad96e73e25d048e3bfc66edbdd7825c775a79438c406dea72694b8bd757YOUR_TOKENAAS_API_KEY_HERE"
) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "TOKENAAS API KEY IS NOT SET."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT INFORMATION
|--------------------------------------------------------------------------
*/

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID REQUEST."
    ]);

    exit;
}

$productName = trim($input["product_name"] ?? "");
$category = trim($input["category"] ?? "");
$description = trim($input["description"] ?? "");

if ($productName === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRODUCT NAME IS REQUIRED."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| AI PROMPT
|--------------------------------------------------------------------------
*/

$prompt = "
You are the AI Fashion Stylist for ThriftLoop,
a thrift clothing marketplace.

Create a simple outfit recommendation based
on the customer's purchased clothing item.

PURCHASED ITEM:
Product: {$productName}
Category: {$category}
Description: {$description}

Give a practical and fashionable recommendation.

Use exactly these sections:

TOP:
BOTTOM:
SHOES:
ACCESSORIES:
STYLE:
COLOR PALETTE:
STYLING TIP:

Keep the answer concise and easy to understand.
";


/*
|--------------------------------------------------------------------------
| TOKENAAS API REQUEST
|--------------------------------------------------------------------------
*/

$requestBody = [
    "model" => "deepseek-v4-flash",

    "messages" => [
        [
            "role" => "system",
            "content" => "You are a helpful AI fashion stylist."
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],

    "temperature" => 0.7,
    "max_tokens" => 400,
    "stream" => false
];


$ch = curl_init("https://tokenaas.ai/v1/chat/completions");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $OPENAI_API_KEY
]);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($requestBody)
);


$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$curlError = curl_error($ch);

curl_close($ch);


/*
|--------------------------------------------------------------------------
| CURL ERROR
|--------------------------------------------------------------------------
*/

if ($response === false) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO CONNECT TO TOKENAAS.",
        "error" => $curlError
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| DECODE RESPONSE
|--------------------------------------------------------------------------
*/

$data = json_decode($response, true);


/*
|--------------------------------------------------------------------------
| TOKENAAS ERROR
|--------------------------------------------------------------------------
*/

if ($httpCode < 200 || $httpCode >= 300) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "TOKENAAS REQUEST FAILED.",
        "http_code" => $httpCode,
        "error" => $data,
        "raw_response" => $response
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET AI RESPONSE
|--------------------------------------------------------------------------
*/

$aiText = "";

if (
    isset($data["choices"][0]["message"]["content"])
) {
    $aiText = $data["choices"][0]["message"]["content"];
}


if ($aiText === "") {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "AI RETURNED AN EMPTY RESPONSE.",
        "raw_response" => $response
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "recommendation" => $aiText
]);