<?php

// Handles the customer's return from the PayMongo hosted checkout page.
//
//   action=success   (came back via success_url)  -> verify the session with
//                    PayMongo; if it really collected the money, mark the order paid.
//   action=cancelled (came back via cancel_url)   -> verify first; if it was never
//                    paid, auto-cancel the order and return the stock so a retry is clean.
//
// Reaching success_url is NOT proof of payment on its own, so nothing here
// trusts the query parameters — only PayMongo's session state.

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

$action = trim((string) ($input["action"] ?? ""));


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


if (!in_array($action, ["success", "cancelled"], true)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID RETURN ACTION."
    ]);

    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT id, order_number, email, total, order_status, payment_status, payment_reference
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


    // Already settled — keep the return idempotent (StrictMode / refresh / retries).
    if ($order["payment_status"] === "paid") {
        echo json_encode([
            "success" => true,
            "payment_status" => "paid",
            "order_status" => $order["order_status"],
            "message" => "ORDER " . $orderNumber . " IS ALREADY PAID."
        ]);

        exit;
    }


    if ($order["order_status"] === "cancelled") {
        echo json_encode([
            "success" => true,
            "payment_status" => $order["payment_status"],
            "order_status" => "cancelled",
            "message" => "ORDER " . $orderNumber . " IS CANCELLED."
        ]);

        exit;
    }


    // Ask PayMongo whether this session actually collected the money.
    $sessionId = (string) ($order["payment_reference"] ?? "");

    $paid = false;


    if ($sessionId !== "") {

        [$status, $session] = paymongo_api_request(
            "GET",
            "/v1/checkout_sessions/" . rawurlencode($sessionId)
        );


        if ($status === 404) {
            // Unknown session — treat as never paid.
            $session = null;

        } elseif ($status < 200 || $status >= 300) {
            $detail = paymongo_error_detail($session);

            http_response_code(502);

            echo json_encode([
                "success" => false,
                "message" => $status === 0
                    ? "PAYMONGO COULD NOT BE REACHED — CHECK YOUR CONNECTION AND TRY AGAIN."
                    : "COULD NOT VERIFY THE PAYMENT WITH PAYMONGO"
                        . ($detail !== "" ? ": " . $detail : " (HTTP " . $status . ").")
            ]);

            exit;
        }


        $paid = paymongo_session_is_paid($session);
    }


    if ($paid) {

        $charged = paymongo_session_amount($session);

        $expected = (int) round(((float) $order["total"]) * 100);


        if ($charged > 0 && $charged !== $expected) {
            http_response_code(409);

            echo json_encode([
                "success" => false,
                "payment_status" => $order["payment_status"],
                "order_status" => $order["order_status"],
                "message" => "THE PAID AMOUNT DOES NOT MATCH THIS ORDER — CONTACT SUPPORT."
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
            UPDATE orders
            SET payment_status = 'paid'
            WHERE id = :id
              AND payment_status != 'paid'
        ");

        $stmt->execute([":id" => $order["id"]]);


        echo json_encode([
            "success" => true,
            "payment_status" => "paid",
            "order_status" => $order["order_status"],
            "message" => "PAYMENT CONFIRMED FOR ORDER " . $orderNumber . "."
        ]);

        exit;
    }


    // The session never collected anything.
    if ($action === "cancelled") {

        $pdo->beginTransaction();


        $stmt = $pdo->prepare("
            UPDATE orders
            SET order_status = 'cancelled',
                payment_status = 'cancelled'
            WHERE id = :id
              AND order_status = 'pending'
              AND payment_status != 'paid'
        ");

        $stmt->execute([":id" => $order["id"]]);


        if ($stmt->rowCount() === 0) {
            // The order changed underneath us (e.g. a webhook just landed).
            $pdo->rollBack();

            $fresh = $pdo->prepare("
                SELECT order_status, payment_status
                FROM orders
                WHERE id = :id
            ");

            $fresh->execute([":id" => $order["id"]]);

            $fresh = $fresh->fetch() ?: $order;


            echo json_encode([
                "success" => true,
                "payment_status" => $fresh["payment_status"],
                "order_status" => $fresh["order_status"],
                "message" => "ORDER " . $orderNumber . " IS NOW " . strtoupper($fresh["order_status"]) . "."
            ]);

            exit;
        }


        // RETURN THE ITEMS TO INVENTORY — and make them shoppable again
        // (create.php marks products SOLD once stock hits zero).
        $stmt = $pdo->prepare("
            UPDATE products p
            INNER JOIN order_items oi ON oi.product_id = p.id
            SET p.stock = p.stock + oi.quantity,
                p.status = CASE
                    WHEN p.stock + oi.quantity > 0 THEN 'available'
                    ELSE p.status
                END
            WHERE oi.order_id = :order_id
        ");

        $stmt->execute([":order_id" => $order["id"]]);


        $pdo->commit();


        echo json_encode([
            "success" => true,
            "payment_status" => "cancelled",
            "order_status" => "cancelled",
            "message" => "PAYMENT CANCELLED — ORDER " . $orderNumber . " WAS CANCELLED."
        ]);

        exit;
    }


    // success_url, but PayMongo says the money never arrived — leave it pending.
    echo json_encode([
        "success" => true,
        "payment_status" => $order["payment_status"],
        "order_status" => $order["order_status"],
        "message" => "PAYMENT NOT CONFIRMED YET — ORDER " . $orderNumber . " IS STILL PENDING."
    ]);


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO VERIFY THE PAYMENT.",
        "error" => $e->getMessage()
    ]);
}
