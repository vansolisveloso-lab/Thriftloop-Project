<?php

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

require_once __DIR__ . "/../payments/paymongo_lib.php";


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


try {

    $stmt = $pdo->prepare("
        SELECT id, order_number, email, order_status, payment_status, payment_reference
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


    if ($order["email"] !== $email) {
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
            "message" => "THIS ORDER IS ALREADY CANCELLED."
        ]);

        exit;
    }


    if ($order["order_status"] !== "pending") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "ONLY PENDING ORDERS CAN BE CANCELLED — THIS ONE IS ALREADY BEING PROCESSED."
        ]);

        exit;
    }


    // PAID ORDERS: cancelling means refunding. Ask PayMongo to return the
    // money FIRST — the order is only cancelled once the refund is accepted,
    // so a failed refund never strands the customer with a dead order.
    $wasPaid = ($order["payment_status"] === "paid");

    $sessionId = (string) ($order["payment_reference"] ?? "");


    if ($wasPaid && strpos($sessionId, "cs_") === 0) {

        [$status, $session] = paymongo_api_request(
            "GET",
            "/v1/checkout_sessions/" . rawurlencode($sessionId)
        );


        if ($status < 200 || $status >= 300) {
            http_response_code(502);

            echo json_encode([
                "success" => false,
                "message" => $status === 0
                    ? "PAYMONGO COULD NOT BE REACHED — YOUR ORDER WAS NOT CANCELLED. PLEASE TRY AGAIN."
                    : "COULD NOT VERIFY THE PAYMENT FOR A REFUND — YOUR ORDER WAS NOT CANCELLED."
            ]);

            exit;
        }


        // The payment the money actually came from (session-level list first,
        // then the copy nested under payment_intent).
        $payments = array_merge(
            (array) ($session["data"]["attributes"]["payments"] ?? []),
            (array) ($session["data"]["attributes"]["payment_intent"]["attributes"]["payments"] ?? [])
        );

        $paymentId = "";
        $amount = 0;


        foreach ($payments as $candidate) {
            $candidateId = (string) ($candidate["id"] ?? "");
            $candidateAmount = (int) ($candidate["attributes"]["amount"] ?? 0);

            if ($candidateId !== "" && $candidateAmount > 0) {
                $paymentId = $candidateId;
                $amount = $candidateAmount;
                break;
            }
        }


        if ($paymentId !== "") {

            [$refundStatus, $refundBody] = paymongo_api_request("POST", "/v1/refunds", [
                "data" => [
                    "amount" => $amount,
                    "payment_id" => $paymentId,
                    "reason" => "requested_by_customer",
                ],
            ]);


            if ($refundStatus < 200 || $refundStatus >= 300) {
                $detail = paymongo_error_detail($refundBody);

                http_response_code(409);

                echo json_encode([
                    "success" => false,
                    "message" => "THE REFUND COULD NOT BE PROCESSED — YOUR ORDER WAS NOT CANCELLED"
                        . ($detail !== "" ? ": " . $detail : ".")
                ]);

                exit;
            }

        }

        // Session shows no payment (order was marked paid by hand):
        // nothing to push through PayMongo — cancel with refunded status.

    }


    $newPaymentStatus = $wasPaid ? "refunded" : "cancelled";


    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        UPDATE orders
        SET order_status = 'cancelled',
            payment_status = :new_payment_status
        WHERE id = :id
          AND order_status = 'pending'
          AND payment_status = :seen_payment_status
    ");

    $stmt->execute([
        ":id" => $order["id"],
        ":new_payment_status" => $newPaymentStatus,
        ":seen_payment_status" => $order["payment_status"],
    ]);


    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER CAN NO LONGER BE CANCELLED."
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
        "message" => $wasPaid
            ? "ORDER CANCELLED — YOUR REFUND HAS BEEN REQUESTED."
            : "ORDER CANCELLED SUCCESSFULLY.",
        "order" => [
            "order_number" => $order["order_number"],
            "order_status" => "cancelled",
            "payment_status" => $newPaymentStatus
        ]
    ]);


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO CANCEL THE ORDER.",
        "error" => $e->getMessage()
    ]);
}
