<?php

header("Content-Type: application/json");

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Headers: Content-Type");

header("Access-Control-Allow-Methods: POST, OPTIONS");


if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}


require_once __DIR__ . "/../config/database.php";


$input = json_decode(file_get_contents("php://input"), true);


$email = strtolower(trim($input["email"] ?? ""));

$password = $input["password"] ?? "";


if ($email === "" || $password === "") {

    echo json_encode([
        "success" => false,
        "message" => "PLEASE ENTER YOUR EMAIL AND PASSWORD."
    ]);

    exit;
}


try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            first_name,
            last_name,
            email,
            password,
            role,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch();


    if (!$user) {

        echo json_encode([
            "success" => false,
            "message" => "INVALID EMAIL OR PASSWORD."
        ]);

        exit;
    }


    if (!password_verify($password, $user["password"])) {

        echo json_encode([
            "success" => false,
            "message" => "INVALID EMAIL OR PASSWORD."
        ]);

        exit;
    }


    if ($user["status"] !== "active") {

        echo json_encode([
            "success" => false,
            "message" => "THIS ACCOUNT IS DEACTIVATED."
        ]);

        exit;
    }


    echo json_encode([
        "success" => true,
        "message" => "LOGIN SUCCESSFUL.",
        "user" => [
            "id" => (int) $user["id"],
            "first_name" => $user["first_name"],
            "last_name" => $user["last_name"],
            "email" => $user["email"],
            "role" => $user["role"],
            "status" => $user["status"]
        ]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "LOGIN FAILED.",
        "error" => $e->getMessage()
    ]);
}
