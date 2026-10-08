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


$requiredFields = [
    "first_name",
    "last_name",
    "email",
    "phone",
    "address",
    "city",
    "postal_code",
    "province"
];


foreach ($requiredFields as $field) {

    $value = trim((string) ($input[$field] ?? ""));

    if ($value === "") {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => strtoupper($field) . " IS REQUIRED."
        ]);

        exit;
    }

    $input[$field] = $value;
}


if (!filter_var($input["email"], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID EMAIL ADDRESS."
    ]);

    exit;
}


$input["email"] = strtolower($input["email"]);


// How the customer will pay: "online" (PayMongo) or "cod" (cash on delivery).
$paymentMethod = strtolower(trim((string) ($input["payment_method"] ?? "online")));

if ($paymentMethod === "") {
    $paymentMethod = "online";
}

if (!in_array($paymentMethod, ["online", "cod"], true)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "INVALID PAYMENT METHOD."
    ]);

    exit;
}


$items = $input["items"] ?? [];

if (!is_array($items) || count($items) === 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "YOUR CART IS EMPTY."
    ]);

    exit;
}


$shippingFee = 100.00;


try {

    $pdo->beginTransaction();


    $subtotal = 0.00;

    $orderItems = [];


    foreach ($items as $item) {

        $productId = (int) ($item["product_id"] ?? 0);

        $quantity = (int) ($item["quantity"] ?? 0);


        if ($productId <= 0 || $quantity < 1) {
            $pdo->rollBack();

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "INVALID CART ITEM."
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                price,
                stock,
                status
            FROM products
            WHERE id = :id
            FOR UPDATE
        ");

        $stmt->execute([":id" => $productId]);

        $product = $stmt->fetch();


        if (!$product || $product["status"] !== "available") {
            $pdo->rollBack();

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "A PRODUCT IN YOUR CART IS NO LONGER AVAILABLE."
            ]);

            exit;
        }


        if ((int) $product["stock"] < $quantity) {
            $pdo->rollBack();

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" => "NOT ENOUGH STOCK FOR " . $product["name"] . "."
            ]);

            exit;
        }


        $price = (float) $product["price"];

        $lineSubtotal = $price * $quantity;

        $subtotal += $lineSubtotal;


        $orderItems[] = [
            "product_id" => $productId,
            "product_name" => $product["name"],
            "price" => $price,
            "quantity" => $quantity,
            "subtotal" => $lineSubtotal
        ];
    }


    $total = $subtotal + $shippingFee;


    $year = date("Y");

    $orderNumber = null;


    for ($attempt = 1; $attempt <= 50; $attempt++) {

        $stmt = $pdo->query("
            SELECT COUNT(*) AS cnt
            FROM orders
            WHERE order_number LIKE 'TL-" . $year . "-%'
        ");

        $count = (int) $stmt->fetch()["cnt"];

        $candidate = sprintf("TL-%s-%03d", $year, $count + $attempt);


        $stmt = $pdo->prepare("
            SELECT id FROM orders WHERE order_number = :num
        ");

        $stmt->execute([":num" => $candidate]);


        if (!$stmt->fetch()) {
            $orderNumber = $candidate;
            break;
        }
    }


    if ($orderNumber === null) {
        $pdo->rollBack();

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "COULD NOT GENERATE ORDER NUMBER."
        ]);

        exit;
    }


    $stmt = $pdo->prepare("
        INSERT INTO orders (
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
            order_status
        ) VALUES (
            :order_number,
            :first_name,
            :last_name,
            :email,
            :phone,
            :address,
            :city,
            :postal_code,
            :province,
            :subtotal,
            :shipping_fee,
            :total,
            'pending',
            :payment_method,
            'pending'
        )
    ");

    $stmt->execute([
        ":order_number" => $orderNumber,
        ":first_name" => $input["first_name"],
        ":last_name" => $input["last_name"],
        ":email" => $input["email"],
        ":phone" => $input["phone"],
        ":address" => $input["address"],
        ":city" => $input["city"],
        ":postal_code" => $input["postal_code"],
        ":province" => $input["province"],
        ":subtotal" => $subtotal,
        ":shipping_fee" => $shippingFee,
        ":total" => $total,
        ":payment_method" => $paymentMethod
    ]);


    $orderId = (int) $pdo->lastInsertId();


    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            price,
            quantity,
            subtotal
        ) VALUES (
            :order_id,
            :product_id,
            :product_name,
            :price,
            :quantity,
            :subtotal
        )
    ");
// integ 1
    $stockStmt = $pdo->prepare("
        UPDATE products
        SET 
            stock = stock - :qty,
            status = CASE
                WHEN stock - :qty <= 0 THEN 'SOLD' 
                ELSE 'available'
            END
        WHERE id = :id 
            AND stock >= :qty
            AND status = 'available'
    ");


    foreach ($orderItems as $orderItem) {

        $itemStmt->execute([
            ":order_id" => $orderId,
            ":product_id" => $orderItem["product_id"],
            ":product_name" => $orderItem["product_name"],
            ":price" => $orderItem["price"],
            ":quantity" => $orderItem["quantity"],
            ":subtotal" => $orderItem["subtotal"]
        ]);


        $stockStmt->execute([
            ":qty" => $orderItem["quantity"],
            ":id" => $orderItem["product_id"]
        ]);


        if ($stockStmt->rowCount() === 0) {
            $pdo->rollBack();

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" => "NOT ENOUGH STOCK FOR " . $orderItem["product_name"] . "."
            ]);

            exit;
        }
    }


    $pdo->commit();


    echo json_encode([
        "success" => true,
        "message" => "ORDER PLACED SUCCESSFULLY.",
        "order" => [
            "id" => $orderId,
            "order_number" => $orderNumber,
            "subtotal" => $subtotal,
            "shipping_fee" => $shippingFee,
            "total" => $total,
            "order_status" => "pending",
            "payment_status" => "pending",
            "payment_method" => $paymentMethod,
            "items" => $orderItems
        ]
    ]);


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO PLACE ORDER.",
        "error" => $e->getMessage()
    ]);
}
