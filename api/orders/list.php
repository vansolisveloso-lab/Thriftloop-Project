<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "METHOD NOT ALLOWED."
    ]);
    exit;
}

require_once __DIR__ . "/../config/database.php";

$email = strtolower(trim((string) ($_GET["email"] ?? "")));
$all = isset($_GET["all"]);

try {

    if ($all) {

        $stmt = $pdo->query("
            SELECT
                id,
                order_number,
                first_name,
                last_name,
                email,
                phone,
                address,
                city,
                postal_code,
                province,
                subtotal,
                shipping_fee,
                total,
                payment_status,
                payment_method,
                payment_reference,
                order_status,
                courier,
                tracking_number,
                created_at
            FROM orders
            ORDER BY created_at DESC, id DESC
        ");

        $orders = $stmt->fetchAll();

    } elseif ($email !== "") {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "INVALID EMAIL ADDRESS."
            ]);

            exit;
        }

        $stmt = $pdo->prepare("
            SELECT
                id,
                order_number,
                first_name,
                last_name,
                email,
                phone,
                address,
                city,
                postal_code,
                province,
                subtotal,
                shipping_fee,
                total,
                payment_status,
                payment_method,
                payment_reference,
                order_status,
                courier,
                tracking_number,
                created_at
            FROM orders
            WHERE email = :email
            ORDER BY created_at DESC, id DESC
        ");

        $stmt->execute([
            ":email" => $email
        ]);

        $orders = $stmt->fetchAll();

    } else {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "EMAIL OR ALL=1 IS REQUIRED."
        ]);

        exit;
    }


    /*
     * GET ORDER ITEMS
     */

    $itemStmt = $pdo->prepare("
        SELECT
            oi.product_id,
            oi.product_name,
            oi.price,
            oi.quantity,
            oi.subtotal,
            p.image_url,
            p.category AS product_category,
            p.description AS product_description
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = :order_id
        ORDER BY oi.id ASC
    ");


    /*
     * ADD ITEMS TO EACH ORDER
     */

    foreach ($orders as &$order) {

        $order["id"] = (int) $order["id"];
        $order["subtotal"] = (float) $order["subtotal"];
        $order["shipping_fee"] = (float) $order["shipping_fee"];
        $order["total"] = (float) $order["total"];

        $itemStmt->execute([
            ":order_id" => $order["id"]
        ]);

        $items = $itemStmt->fetchAll();

        foreach ($items as &$item) {

            $item["product_id"] = (int) $item["product_id"];
            $item["price"] = (float) $item["price"];
            $item["quantity"] = (int) $item["quantity"];
            $item["subtotal"] = (float) $item["subtotal"];

            $item["category"] = $item["product_category"] ?? "";
            $item["description"] = $item["product_description"] ?? "";

            unset($item["product_category"]);
            unset($item["product_description"]);
        }

        unset($item);

        $order["items"] = $items;
    }

    unset($order);


    /*
     * RETURN RESPONSE
     */

    echo json_encode([
        "success" => true,
        "count" => count($orders),
        "orders" => $orders
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO LOAD ORDERS.",
        "error" => $e->getMessage()
    ]);
}