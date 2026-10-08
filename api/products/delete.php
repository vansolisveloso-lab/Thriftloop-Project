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


if ($productId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRODUCT ID IS REQUIRED."
    ]);

    exit;
}


$uploadDir = "E:\\Documents\\thriftloop\\thriftloop\\frontend\\public\\products";


try {

    $stmt = $pdo->prepare("
        SELECT id, product_code, name, image_url, status
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


    // PRODUCTS REFERENCED BY PAST ORDERS CANNOT BE DELETED
    // BECAUSE order_items.product_id IS A FOREIGN KEY.
    // ARCHIVE THEM INSTEAD SO ORDER HISTORY STAYS VALID.
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM order_items
        WHERE product_id = :id
    ");

    $stmt->execute([":id" => $productId]);

    $orderCount = (int) $stmt->fetch()["cnt"];


    if ($orderCount > 0) {

        if ($product["status"] === "inactive") {
            echo json_encode([
                "success" => true,
                "message" => "PRODUCT IS ALREADY REMOVED.",
                "removed" => "archived",
                "product" => [
                    "id" => $productId,
                    "product_code" => $product["product_code"],
                    "name" => $product["name"],
                    "status" => $product["status"]
                ]
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
            UPDATE products
            SET status = 'inactive'
            WHERE id = :id
        ");

        $stmt->execute([":id" => $productId]);


        echo json_encode([
            "success" => true,
            "message" => "PRODUCT REMOVED FROM THE SHOP — KEPT FOR ORDER HISTORY.",
            "removed" => "archived",
            "product" => [
                "id" => $productId,
                "product_code" => $product["product_code"],
                "name" => $product["name"],
                "status" => "inactive"
            ]
        ]);

        exit;
    }


    $stmt = $pdo->prepare("
        DELETE FROM products
        WHERE id = :id
    ");

    $stmt->execute([":id" => $productId]);


    $imageDeleted = false;


    if (
        $product["image_url"] &&
        str_starts_with($product["image_url"], "/products/")
    ) {

        $imagePath = $uploadDir . DIRECTORY_SEPARATOR .
            basename($product["image_url"]);


        if (is_file($imagePath)) {
            $imageDeleted = @unlink($imagePath);
        }
    }


    echo json_encode([
        "success" => true,
        "message" => "PRODUCT DELETED SUCCESSFULLY.",
        "removed" => "deleted",
        "image_deleted" => $imageDeleted,
        "product" => [
            "id" => $productId,
            "product_code" => $product["product_code"],
            "name" => $product["name"]
        ]
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO REMOVE PRODUCT.",
        "error" => $e->getMessage()
    ]);
}
