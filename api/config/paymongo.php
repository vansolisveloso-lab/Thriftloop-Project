<?php

// PayMongo secret key (Dashboard > Developers > API Keys). Test keys start with sk_test_.
$PAYMONGO_SECRET_KEY = "sk_test_c5pdLZAGXLaqa5hzKPoMw2gx";

// Webhook signing secret (Dashboard > Developers > Webhooks — shown when you create the endpoint).
$PAYMONGO_WEBHOOK_SECRET = "whsec_REPLACE_WITH_YOUR_SIGNING_SECRET";

// PayMongo API base. Only change this when testing against a local mock server.
$PAYMONGO_API_BASE = "https://api.paymongo.com";

// Frontend origin customers return to after paying — must match the running dev server.
$PAYMONGO_FRONTEND_URL = "http://localhost:5173";

// Payment methods offered on the hosted checkout page.
// Only include methods your PayMongo account has enabled.
$PAYMONGO_PAYMENT_METHOD_TYPES = ["card", "gcash"];


// True once a real secret key has been pasted into this file.
function paymongo_is_configured() {
    global $PAYMONGO_SECRET_KEY;

    return is_string($PAYMONGO_SECRET_KEY)
        && strpos($PAYMONGO_SECRET_KEY, "REPLACE") === false;
}
