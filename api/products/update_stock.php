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


$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID REQUEST BODY."
    ]);

    exit;
}


$productId = (int) ($input["product_id"] ?? 0);

$quantity = $input["quantity"] ?? null;


if ($productId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRODUCT ID IS REQUIRED."
    ]);

    exit;
}


if (!is_numeric($quantity) || (int) $quantity != $quantity) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "QUANTITY MUST BE A WHOLE NUMBER."
    ]);

    exit;
}


$quantity = (int) $quantity;


if ($quantity < 1 || $quantity > 10000) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "QUANTITY MUST BE BETWEEN 1 AND 10000."
    ]);

    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT id, name, stock
        FROM products
        WHERE id = :id
    ");

    $stmt->execute([":id" => $productId]);

    $product = $stmt->fetch();


    if (!$product) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "PRODUCT NOT FOUND."
        ]);

        exit;
    }


    $newStock = (int) $product["stock"] + $quantity;


    $stmt = $pdo->prepare("
        UPDATE products
        SET stock = :stock
        WHERE id = :id
    ");

    $stmt->execute([
        ":stock" => $newStock,
        ":id" => $productId
    ]);


    echo json_encode([
        "success" => true,
        "message" => "STOCK UPDATED SUCCESSFULLY.",
        "product" => [
            "id" => $productId,
            "name" => $product["name"],
            "previous_stock" => (int) $product["stock"],
            "added" => $quantity,
            "stock" => $newStock
        ]
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO UPDATE STOCK.",
        "error" => $e->getMessage()
    ]);
}
