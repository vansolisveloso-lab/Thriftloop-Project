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


$name = trim((string) ($input["name"] ?? ""));

$category = trim((string) ($input["category"] ?? ""));

$type = trim((string) ($input["type"] ?? ""));

$description = trim((string) ($input["description"] ?? ""));

$price = $input["price"] ?? null;

$stock = $input["stock"] ?? 1;


if ($name === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRODUCT NAME IS REQUIRED."
    ]);

    exit;
}


if (mb_strlen($name) > 150) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRODUCT NAME IS TOO LONG."
    ]);

    exit;
}


if ($category === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "CATEGORY IS REQUIRED."
    ]);

    exit;
}


if (mb_strlen($category) > 100) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "CATEGORY IS TOO LONG."
    ]);

    exit;
}


if (!is_numeric($price) || (float) $price <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRICE MUST BE GREATER THAN ZERO."
    ]);

    exit;
}


$price = round((float) $price, 2);


if ($price > 99999999.99) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRICE IS TOO HIGH."
    ]);

    exit;
}


if (!is_numeric($stock) || (int) $stock != $stock) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "STOCK MUST BE A WHOLE NUMBER."
    ]);

    exit;
}


$stock = (int) $stock;


if ($stock < 0 || $stock > 100000) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "STOCK MUST BE BETWEEN 0 AND 100000."
    ]);

    exit;
}


if ($type === "") {
    $type = "PRE-LOVED";
}


if (mb_strlen($type) > 100) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "TYPE IS TOO LONG."
    ]);

    exit;
}


if ($description === "") {
    $description = null;
}


try {

    $productCode = null;


    for ($attempt = 0; $attempt < 50; $attempt++) {

        $stmt = $pdo->query("
            SELECT COALESCE(
                MAX(CAST(SUBSTRING(product_code, 4) AS UNSIGNED)),
                0
            ) AS next_number
            FROM products
            WHERE product_code REGEXP '^TL-[0-9]+$'
        ");

        $nextNumber = (int) $stmt->fetch()["next_number"] + 1 + $attempt;


        $candidate = sprintf("TL-%03d", $nextNumber);


        $stmt = $pdo->prepare("
            SELECT id FROM products WHERE product_code = :code
        ");

        $stmt->execute([":code" => $candidate]);


        if (!$stmt->fetch()) {
            $productCode = $candidate;
            break;
        }
    }


    if ($productCode === null) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "COULD NOT GENERATE PRODUCT CODE."
        ]);

        exit;
    }


    $stmt = $pdo->prepare("
        INSERT INTO products (
            product_code,
            name,
            category,
            description,
            price,
            stock,
            image_url,
            type,
            status
        ) VALUES (
            :product_code,
            :name,
            :category,
            :description,
            :price,
            :stock,
            NULL,
            :type,
            'available'
        )
    ");

    $stmt->execute([
        ":product_code" => $productCode,
        ":name" => $name,
        ":category" => $category,
        ":description" => $description,
        ":price" => $price,
        ":stock" => $stock,
        ":type" => $type
    ]);


    $productId = (int) $pdo->lastInsertId();


    echo json_encode([
        "success" => true,
        "message" => "PRODUCT ADDED SUCCESSFULLY.",
        "product" => [
            "id" => $productId,
            "product_code" => $productCode,
            "name" => $name,
            "category" => $category,
            "description" => $description,
            "price" => $price,
            "stock" => $stock,
            "type" => $type,
            "status" => "available"
        ]
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO ADD PRODUCT.",
        "error" => $e->getMessage()
    ]);
}
