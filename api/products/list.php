<?php

header("Content-Type: application/json");

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Methods: GET, OPTIONS");


if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}


require_once __DIR__ . "/../config/database.php";


try {

    $stmt = $pdo->query("
        SELECT
            id,
            product_code,
            name,
            category,
            description,
            price,
            stock,
            image_url,
            type,
            status
        FROM products
        WHERE status = 'available'
        ORDER BY id ASC
    ");


    $products = $stmt->fetchAll();


    foreach ($products as &$product) {

        $product["id"] = (int) $product["id"];

        $product["price"] = (float) $product["price"];

        $product["stock"] = (int) $product["stock"];
    }

    unset($product);


    echo json_encode([
        "success" => true,
        "count" => count($products),
        "products" => $products
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO LOAD PRODUCTS.",
        "error" => $e->getMessage()
    ]);
}
