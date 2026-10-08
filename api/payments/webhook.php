<?php

// PayMongo webhook receiver — the production confirmation path.
// PayMongo cannot reach localhost, so during local development the
// confirmation happens in payments/return.php when the customer returns.
//
// Header: Paymongo-Signature = t=<timestamp>,te=<test signature>,li=<live signature>
// Signature = HMAC-SHA256(webhook secret, timestamp + "." + raw body)

header("Content-Type: application/json");

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Methods: POST, OPTIONS");

header("Access-Control-Allow-Headers: Content-Type, Paymongo-Signature");


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


require_once __DIR__ . "/../config/database.php";

require_once __DIR__ . "/paymongo_lib.php";


$rawBody = file_get_contents("php://input");

$signature = $_SERVER["HTTP_PAYMONGO_SIGNATURE"] ?? "";


if (!paymongo_verify_webhook_signature($rawBody, $signature, $PAYMONGO_WEBHOOK_SECRET)) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "INVALID SIGNATURE."
    ]);

    exit;
}


$payload = json_decode($rawBody, true);


if (!is_array($payload)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID PAYLOAD."
    ]);

    exit;
}


// The event type sits at different depths depending on the event.
$eventType = (string) (
    $payload["data"]["attributes"]["type"]
    ?? $payload["data"]["type"]
    ?? $payload["event_type"]
    ?? ""
);


// Always acknowledge with 200 so PayMongo does not keep retrying events
// we do not care about.
if ($eventType === "") {
    echo json_encode([
        "success" => true,
        "ignored" => true,
        "message" => "NO EVENT TYPE."
    ]);

    exit;
}


$isPaidEvent = strpos($eventType, "payment.paid") !== false
    || strpos($eventType, "payment_succeeded") !== false;

$isFailedEvent = strpos($eventType, "payment.failed") !== false
    || strpos($eventType, "payment_failed") !== false;


if (!$isPaidEvent && !$isFailedEvent) {
    echo json_encode([
        "success" => true,
        "ignored" => true,
        "message" => "EVENT IGNORED: " . $eventType
    ]);

    exit;
}


$reference = paymongo_find_value($payload, "reference_number");


if (!is_string($reference) || $reference === "") {
    echo json_encode([
        "success" => true,
        "ignored" => true,
        "message" => "NO REFERENCE NUMBER."
    ]);

    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT id, payment_status
        FROM orders
        WHERE order_number = :order_number
    ");

    $stmt->execute([":order_number" => $reference]);

    $order = $stmt->fetch();


    if (!$order) {
        echo json_encode([
            "success" => true,
            "ignored" => true,
            "message" => "UNKNOWN ORDER: " . $reference
        ]);

        exit;
    }


    if ($isPaidEvent) {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET payment_status = 'paid'
            WHERE id = :id
              AND payment_status IN ('pending', 'failed')
        ");

        $stmt->execute([":id" => $order["id"]]);


        echo json_encode([
            "success" => true,
            "payment_status" => "paid",
            "message" => $stmt->rowCount() > 0
                ? "ORDER " . $reference . " MARKED PAID."
                : "ORDER " . $reference . " IS ALREADY SETTLED."
        ]);

        exit;
    }


    // A failed payment only downgrades an order that is still awaiting payment.
    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = 'failed'
        WHERE id = :id
          AND payment_status = 'pending'
    ");

    $stmt->execute([":id" => $order["id"]]);


    echo json_encode([
        "success" => true,
        "payment_status" => $stmt->rowCount() > 0 ? "failed" : $order["payment_status"],
        "message" => "RECORDED FAILED PAYMENT FOR ORDER " . $reference . "."
    ]);


} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO PROCESS THE WEBHOOK.",
        "error" => $e->getMessage()
    ]);
}
