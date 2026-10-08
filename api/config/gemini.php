<?php

// Google Gemini — powers the ThriftLoop shopping-assistant chat box.
//
// Get a FREE key (no credit card): https://aistudio.google.com/apikey
// Paste it here — the rest of the site works fine while this is a placeholder.

$GEMINI_API_KEY = "AQ.Ab8RN6LwbSyEDf7TmPHG_k7iJIVeGxgXdw-iZ8M5D05iKDrATQ";

// Chat model. Must be one your key can access — the free tier has a
// specific model list: https://ai.google.dev/gemini-api/docs/models
// (gemini-2.5-flash is retired for new keys; 3.5-flash-lite verified working.)
$GEMINI_MODEL = "gemini-3.5-flash-lite";

// API base. Only change this when testing against a local mock server.
$GEMINI_API_BASE = "https://generativelanguage.googleapis.com";


// True once a real key has been pasted into this file.
function gemini_is_configured() {
    global $GEMINI_API_KEY;

    return is_string($GEMINI_API_KEY)
        && strpos($GEMINI_API_KEY, "REPLACE") === false;
}
