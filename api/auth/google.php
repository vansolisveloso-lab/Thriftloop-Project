<?php

header("Content-Type: application/json");

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Headers: Content-Type");

header("Access-Control-Allow-Methods: POST, OPTIONS");


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

require_once __DIR__ . "/../config/google.php";

require_once __DIR__ . "/google_verify.php";


$input = json_decode(file_get_contents("php://input"), true);

$credential = trim((string) ($input["credential"] ?? ""));


if ($credential === "") {

    echo json_encode([
        "success" => false,
        "message" => "MISSING GOOGLE CREDENTIAL."
    ]);

    exit;
}


try {

    $claims = verify_google_id_token(
        $credential,
        $GOOGLE_CLIENT_ID,
        $GOOGLE_JWKS_URL
    );

    if ($claims === null) {

        echo json_encode([
            "success" => false,
            "message" => "GOOGLE SIGN IN FAILED."
        ]);

        exit;
    }


    $googleId = $claims["sub"];

    $email = strtolower(trim($claims["email"]));


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo json_encode([
            "success" => false,
            "message" => "GOOGLE SIGN IN FAILED."
        ]);

        exit;
    }


    // 1. Existing user already linked to this Google account.
    $stmt = $pdo->prepare("
        SELECT
            id,
            first_name,
            last_name,
            email,
            role,
            status
        FROM users
        WHERE google_id = ?
        LIMIT 1
    ");

    $stmt->execute([$googleId]);

    $user = $stmt->fetch();


    // 2. Same email registered with a password: link the Google account.
    if (!$user) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                first_name,
                last_name,
                email,
                role,
                status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();


        if ($user) {

            $linkStmt = $pdo->prepare("
                UPDATE users
                SET google_id = ?
                WHERE id = ? AND google_id IS NULL
            ");

            $linkStmt->execute([$googleId, $user["id"]]);
        }
    }


    // 3. Brand new Google user.
    if (!$user) {

        $firstName = trim($claims["given_name"] ?? "");

        $lastName = trim($claims["family_name"] ?? "");


        if ($firstName === "" && !empty($claims["name"])) {

            $nameParts = preg_split("/\s+/", trim($claims["name"]), 2);

            $firstName = $nameParts[0];

            $lastName = $nameParts[1] ?? "";
        }


        if ($firstName === "") {
            $firstName = explode("@", $email)[0];
        }


        // Google users never use a password; store an unusable random hash.
        $hashedPassword = password_hash(
            bin2hex(random_bytes(32)),
            PASSWORD_DEFAULT
        );


        $stmt = $pdo->prepare("
            INSERT INTO users
            (
                first_name,
                last_name,
                email,
                password,
                google_id,
                role,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, 'customer', 'active')
        ");

        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            $hashedPassword,
            $googleId
        ]);


        $user = [
            "id" => (int) $pdo->lastInsertId(),
            "first_name" => $firstName,
            "last_name" => $lastName,
            "email" => $email,
            "role" => "customer",
            "status" => "active"
        ];
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
        "message" => "GOOGLE LOGIN FAILED.",
        "error" => $e->getMessage()
    ]);
}
