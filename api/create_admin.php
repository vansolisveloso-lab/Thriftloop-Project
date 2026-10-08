<?php

header("Content-Type: application/json");

require_once "config/database.php";


$firstName = "ThriftLoop";

$lastName = "Admin";

$email = "admin@thriftloop.com";

$password = "admin123";


try {

    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $check->execute([
        $email
    ]);


    if ($check->fetch()) {

        echo json_encode([

            "success" => false,

            "message" =>
                "Admin account already exists."

        ]);

        exit;
    }


    $hashedPassword =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


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
        (?, ?, ?, ?, 'admin', 'active')
    ");


    $stmt->execute([

        $firstName,

        $lastName,

        $email,

        $hashedPassword

    ]);


    echo json_encode([

        "success" => true,

        "message" =>
            "Admin account created successfully.",

        "email" =>
            $email,

        "password" =>
            $password

    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Failed to create admin account.",

        "error" =>
            $e->getMessage()

    ]);
}