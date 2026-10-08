<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json");

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


$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID REQUEST BODY."
    ]);

    exit;
}


$orderId = (int) ($input["order_id"] ?? 0);

$orderStatus = $input["order_status"] ?? null;

$paymentStatus = $input["payment_status"] ?? null;

$courier = $input["courier"] ?? null;

$trackingNumber = $input["tracking_number"] ?? null;


if ($orderId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "ORDER ID IS REQUIRED."
    ]);

    exit;
}


$allowedOrderStatuses = [
    "pending",
    "processing",
    "packed",
    "shipped",
    "out_for_delivery",
    "delivered",
    "cancelled"
];

$allowedPaymentStatuses = [
    "pending",
    "paid",
    "failed",
    "cancelled",
    "refunded"
];


if ($orderStatus !== null && !in_array($orderStatus, $allowedOrderStatuses, true)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID ORDER STATUS."
    ]);

    exit;
}


if ($paymentStatus !== null && !in_array($paymentStatus, $allowedPaymentStatuses, true)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID PAYMENT STATUS."
    ]);

    exit;
}


if (
    $orderStatus === null &&
    $paymentStatus === null &&
    $courier === null &&
    $trackingNumber === null
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "NOTHING TO UPDATE."
    ]);

    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT
            order_number,
            order_status,
            payment_status,
            payment_method,
            courier,
            tracking_number,
            updated_at
        FROM orders
        WHERE id = :id
    ");
    $stmt->execute([":id" => $orderId]);

    $order = $stmt->fetch();


    if (!$order) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "ORDER NOT FOUND."
        ]);

        exit;
    }


    // DELIVERED ORDERS ARE FULLY CLOSED.
    if ($order["order_status"] === "delivered") {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER IS CLOSED AND CANNOT BE MODIFIED."
        ]);

        exit;
    }


    // CANCELLED ORDERS: THE LIFECYCLE STAYS FROZEN.
    if ($order["order_status"] === "cancelled" && $orderStatus !== null) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER IS CANCELLED — ONLY THE PAYMENT STATUS CAN BE UPDATED."
        ]);

        exit;
    }


    // ONCE A CANCELLED ORDER HAS BEEN REFUNDED,
    // NOTHING IS LEFT TO CORRECT.
    if (
        $order["order_status"] === "cancelled"
        && $order["payment_status"] === "refunded"
        && $paymentStatus !== null
    ) {
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "THIS ORDER HAS BEEN REFUNDED AND IS NOW CLOSED."
        ]);

        exit;
    }


    // CASH ON DELIVERY: the money is collected at the door, so marking the
    // order delivered settles its payment automatically — unless the admin
    // sent an explicit payment status to use instead.
    if (
        $orderStatus === "delivered"
        && $paymentStatus === null
        && ($order["payment_method"] ?? "online") === "cod"
        && $order["payment_status"] === "pending"
    ) {
        $paymentStatus = "paid";
    }


    $fields = [];

    $params = [
        ":id" => $orderId
    ];


    // UPDATE COURIER
    if ($courier !== null) {
        $fields[] = "courier = :courier";

        $params[":courier"] = trim($courier);
    }


    // UPDATE TRACKING NUMBER
    if ($trackingNumber !== null) {
        $fields[] = "tracking_number = :tracking_number";

        $params[":tracking_number"] = trim($trackingNumber);
    }


    // UPDATE ORDER STATUS
    if ($orderStatus !== null) {
        $fields[] = "order_status = :order_status";

        $params[":order_status"] = $orderStatus;
    }


    // UPDATE PAYMENT STATUS
    if ($paymentStatus !== null) {
        $fields[] = "payment_status = :payment_status";

        $params[":payment_status"] = $paymentStatus;
    }


    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        UPDATE orders
        SET " . implode(", ", $fields) . "
        WHERE id = :id
    ");

    $stmt->execute($params);


    // RETURN ITEMS TO INVENTORY ONLY WHEN THE ORDER
    // ACTUALLY BECOMES CANCELLED.
    if ($orderStatus === "cancelled") {

        $stmt = $pdo->prepare("
            UPDATE products p
            INNER JOIN order_items oi ON oi.product_id = p.id
            SET p.stock = p.stock + oi.quantity
            WHERE oi.order_id = :order_id
        ");

        $stmt->execute([
            ":order_id" => $orderId
        ]);
    }


    // GET UPDATED ORDER
    $stmt = $pdo->prepare("
        SELECT
            order_number,
            order_status,
            payment_status,
            courier,
            tracking_number,
            updated_at
        FROM orders
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $orderId
    ]);

    $updated = $stmt->fetch();


    $pdo->commit();


    echo json_encode([
        "success" => true,

        "message" => "ORDER UPDATED SUCCESSFULLY.",

        "order" => [
            "id" => $orderId,
            "order_number" => $updated["order_number"],
            "order_status" => $updated["order_status"],
            "payment_status" => $updated["payment_status"],
            "courier" => $updated["courier"],
            "trackingNumber" => $updated["tracking_number"],
            "updated_at" => $updated["updated_at"]
        ]
    ]);


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO UPDATE ORDER.",
        "error" => $e->getMessage()
    ]);
}
