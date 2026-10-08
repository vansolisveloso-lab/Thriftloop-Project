<?php

// Facebook Page auto-posting (Phase 3).
//
// Get both values from the Graph API Explorer:
//   query /me/accounts?fields=id,name,access_token
//   -> the Page's "id" and the "access_token" inside the result.
// Leave the placeholders until then — uploads keep working either way.

$FACEBOOK_PAGE_ID = "1365763053284928";

$FACEBOOK_PAGE_TOKEN = "EAAvMeIUVrVkBSr6UOExO8Rvf0vjUoBKGZAAVcWHa2RC0QaKJfRv9T1Aw2YpR5UH8WyLd2dJS5AUiLdBe5ZBMuF0Loutj3xZCNLrvg8Ji0LZChiaaXvxzJJZBVOXOZBzJlep43kiVM9MCCagxsAvz5yZCyT1HXXiLSZAQxKHW7MWalrV5HI8tBMEYZBeJMBMN9vaFkbAGpkT2DPvwpVHIArThylgPrxubjbls4sw0FKIjDhZAwD";

// Graph API version — Meta retires old versions ~2 years after release.
$FACEBOOK_API_VERSION = "v23.0";

// API base. Only change this when testing against a local mock server.
$FACEBOOK_API_BASE = "https://graph.facebook.com";


// True once a real Page id and token have been pasted into this file.
function facebook_is_configured() {
    global $FACEBOOK_PAGE_ID, $FACEBOOK_PAGE_TOKEN;

    return is_string($FACEBOOK_PAGE_ID)
        && strpos($FACEBOOK_PAGE_ID, "REPLACE") === false
        && is_string($FACEBOOK_PAGE_TOKEN)
        && strpos($FACEBOOK_PAGE_TOKEN, "REPLACE") === false;
}
