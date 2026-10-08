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


$firstName = trim($input["first_name"] ?? "");

$lastName = trim($input["last_name"] ?? "");

$email = strtolower(trim($input["email"] ?? ""));

$password = $input["password"] ?? "";


if ($firstName === "" || $lastName === "" || $email === "" || $password === "") {

    echo json_encode([
        "success" => false,
        "message" => "PLEASE COMPLETE ALL FIELDS."
    ]);

    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "success" => false,
        "message" => "PLEASE ENTER A VALID EMAIL ADDRESS."
    ]);

    exit;
}


if (substr($email, -10) !== "@gmail.com") {

    echo json_encode([
        "success" => false,
        "message" => "ONLY @GMAIL.COM EMAIL ADDRESSES ARE ALLOWED."
    ]);

    exit;
}


if (strlen($password) < 6) {

    echo json_encode([
        "success" => false,
        "message" => "PASSWORD MUST BE AT LEAST 6 CHARACTERS."
    ]);

    exit;
}


try {

    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $check->execute([$email]);


    if ($check->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "THIS EMAIL IS ALREADY REGISTERED."
        ]);

        exit;
    }


    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);


    $stmt = $pdo->prepare("
        INSERT INTO users
        (
            first_name,
            last_name,
            email,
            password,
            role,
            status
        )
        VALUES
        (?, ?, ?, ?, 'customer', 'active')
    ");


    $stmt->execute([

        $firstName,

        $lastName,

        $email,

        $hashedPassword

    ]);


    echo json_encode([
        "success" => true,
        "message" => "ACCOUNT CREATED SUCCESSFULLY."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "REGISTRATION FAILED.",
        "error" => $e->getMessage()
    ]);
}
