<?php

// Creates a PayMongo hosted-checkout session for an already-created order
// and returns the checkout_url the customer must be redirected to.

header("Content-Type: application/json");

header("Access-Control-Allow-Origin: *");

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


require_once __DIR__ . "/../config/database.php";

require_once __DIR__ . "/paymongo_lib.php";


$input = json_decode(file_get_contents("php://input"), true);


if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID REQUEST BODY."
    ]);

    exit;
}


$orderNumber = trim((string) ($input["order_number"] ?? ""));

$email = strtolower(trim((string) ($input["email"] ?? "")));


if ($orderNumber === "" || $email === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "ORDER NUMBER AND EMAIL ARE REQUIRED."
    ]);

    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID EMAIL ADDRESS."
    ]);

    exit;
}


if (!paymongo_is_configured()) {
    http_response_code(503);

    echo json_encode([
        "success" => false,
        "message" => "PAYMONGO IS NOT CONFIGURED YET — PASTE YOUR SECRET KEY INTO api/config/paymongo.php."
    ]);

    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT id, order_number, email, shipping_fee, order_status, payment_status, payment_method
        FROM orders
        WHERE order_number = :order_number
    ");

    $stmt->execute([":order_number" => $orderNumber]);

    $order = $stmt->fetch();


    if (!$order) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "ORDER NOT FOUND."
        ]);

        exit;
    }


    if (strtolower($order["email"]) !== $email) {
        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER DOES NOT BELONG TO YOU."
        ]);

        exit;
    }


    if ($order["order_status"] === "cancelled") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER IS CANCELLED — PLACE A NEW ONE."
        ]);

        exit;
    }


    if ($order["payment_status"] === "paid") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER IS ALREADY PAID."
        ]);

        exit;
    }


    // Cash on delivery orders never go through PayMongo.
    if ($order["payment_method"] === "cod") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS IS A CASH ON DELIVERY ORDER — NO ONLINE PAYMENT IS NEEDED."
        ]);

        exit;
    }


    // Build the PayMongo line items from the stored order rows.
    $stmt = $pdo->prepare("
        SELECT product_name, price, quantity
        FROM order_items
        WHERE order_id = :order_id
    ");

    $stmt->execute([":order_id" => $order["id"]]);

    $items = $stmt->fetchAll();


    if (!$items) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER HAS NO ITEMS."
        ]);

        exit;
    }


    $lineItems = [];

    foreach ($items as $item) {
        $lineItems[] = [
            "name" => (string) $item["product_name"],
            "amount" => (int) round($item["price"] * 100),
            "currency" => "PHP",
            "quantity" => (int) $item["quantity"]
        ];
    }

    $shippingFee = (float) $order["shipping_fee"];

    if ($shippingFee > 0) {
        $lineItems[] = [
            "name" => "SHIPPING FEE",
            "amount" => (int) round($shippingFee * 100),
            "currency" => "PHP",
            "quantity" => 1
        ];
    }


    global $PAYMONGO_FRONTEND_URL, $PAYMONGO_PAYMENT_METHOD_TYPES;

    $successUrl = $PAYMONGO_FRONTEND_URL . "/?payment=success"
        . "&order=" . urlencode($orderNumber)
        . "&email=" . urlencode($order["email"]);

    $cancelUrl = $PAYMONGO_FRONTEND_URL . "/?payment=cancelled"
        . "&order=" . urlencode($orderNumber)
        . "&email=" . urlencode($order["email"]);


    [$status, $body] = paymongo_api_request("POST", "/v2/checkout_sessions", [
        "data" => [
            "attributes" => [
                "line_items" => $lineItems,
                "payment_method_types" => $PAYMONGO_PAYMENT_METHOD_TYPES,
                "success_url" => $successUrl,
                "cancel_url" => $cancelUrl,
                "reference_number" => $orderNumber
            ]
        ]
    ]);


    if ($status < 200 || $status >= 300) {
        $detail = paymongo_error_detail($body);

        http_response_code(502);

        echo json_encode([
            "success" => false,
            "message" => $status === 0
                ? "PAYMONGO COULD NOT BE REACHED — CHECK YOUR CONNECTION AND TRY AGAIN."
                : "PAYMONGO REJECTED THE CHECKOUT"
                    . ($detail !== "" ? ": " . $detail : " (HTTP " . $status . ").")
        ]);

        exit;
    }


    $sessionId = (string) ($body["data"]["id"] ?? "");

    $checkoutUrl = (string) ($body["data"]["attributes"]["checkout_url"] ?? "");


    if ($sessionId === "" || $checkoutUrl === "") {
        http_response_code(502);

        echo json_encode([
            "success" => false,
            "message" => "PAYMONGO RETURNED AN INVALID RESPONSE."
        ]);

        exit;
    }


    // Keep the session id for later confirmation (orders.payment_reference).
    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_reference = :payment_reference
        WHERE id = :id
          AND payment_status IN ('pending', 'failed')
    ");

    $stmt->execute([
        ":payment_reference" => $sessionId,
        ":id" => $order["id"]
    ]);


    if ($stmt->rowCount() === 0) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER CAN NO LONGER START A PAYMENT."
        ]);

        exit;
    }


    echo json_encode([
        "success" => true,
        "checkout_url" => $checkoutUrl,
        "session_id" => $sessionId
    ]);


} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO START THE PAYMENT.",
        "error" => $e->getMessage()
    ]);
}
