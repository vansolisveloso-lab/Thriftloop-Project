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

require_once __DIR__ . "/../facebook/fb_post_lib.php";


$uploadDir = "C:\\Users\\vanso\\Downloads\\thriftloop\\thriftloop\\frontend\\public\\products";

$maxBytes = 5 * 1024 * 1024;


$productId = (int) ($_POST["product_id"] ?? 0);


if ($productId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "PRODUCT ID IS REQUIRED."
    ]);

    exit;
}


if (!isset($_FILES["file"]) || $_FILES["file"]["error"] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "NO IMAGE FILE."
    ]);

    exit;
}


$file = $_FILES["file"];


if ($file["error"] !== UPLOAD_ERR_OK) {

    $message = "IMAGE UPLOAD FAILED.";

    if ($file["error"] === UPLOAD_ERR_INI_SIZE || $file["error"] === UPLOAD_ERR_FORM_SIZE) {
        $message = "IMAGE IS TOO LARGE.";
    }

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => $message
    ]);

    exit;
}


if ($file["size"] > $maxBytes) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "IMAGE MUST BE 5MB OR SMALLER."
    ]);

    exit;
}


$imageInfo = @getimagesize($file["tmp_name"]);

$allowedTypes = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp",
    "image/gif" => "gif"
];


if ($imageInfo === false || !isset($allowedTypes[$imageInfo["mime"]])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "UNSUPPORTED IMAGE TYPE. USE JPG, PNG, WEBP OR GIF."
    ]);

    exit;
}


$extension = $allowedTypes[$imageInfo["mime"]];


try {

    $stmt = $pdo->prepare("
        SELECT id, product_code, name, price, image_url
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


    $filename = strtolower($product["product_code"]) . "." . $extension;

    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;


    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }


    if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "COULD NOT SAVE THE IMAGE FILE."
        ]);

        exit;
    }


    $newUrl = "/products/" . $filename;

    $oldUrl = $product["image_url"];


    if ($oldUrl && $oldUrl !== $newUrl && str_starts_with($oldUrl, "/products/")) {

        $oldBasename = basename($oldUrl);


        $check = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM products
            WHERE image_url = :url AND id != :id
        ");

        $check->execute([
            ":url" => $oldUrl,
            ":id" => $productId
        ]);


        if ((int) $check->fetch()["cnt"] === 0) {

            $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $oldBasename;

            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    }


    $stmt = $pdo->prepare("
        UPDATE products
        SET image_url = :url
        WHERE id = :id
    ");

    $stmt->execute([
        ":url" => $newUrl,
        ":id" => $productId
    ]);


    // PHASE 3: best-effort auto-post of the new photo to the Facebook Page.
    // The upload itself never fails because of Facebook — unconfigured,
    // offline, or rejected token all just report their status back.
    $caption = "NEW ON THRIFTLOOP: " . $product["name"]
        . "\n\n₱" . number_format((float) $product["price"], 2);

    $fbResult = fb_post_product_photo($targetPath, $caption);


    echo json_encode([
        "success" => true,
        "message" => "IMAGE UPLOADED SUCCESSFULLY.",
        "facebook_post" => $fbResult["status"],
        "facebook_error" => $fbResult["error"] ?? null,
        "product" => [
            "id" => $productId,
            "product_code" => $product["product_code"],
            "name" => $product["name"],
            "image_url" => $newUrl
        ]
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "FAILED TO SAVE IMAGE.",
        "error" => $e->getMessage()
    ]);
}
