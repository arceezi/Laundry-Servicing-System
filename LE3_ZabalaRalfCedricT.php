<?php
/*
Name: Ralf Cedric Zabala
Date Created: 2025-02-14
Problem Description: Laundry Servicing System using multidimensional arrays in PHP to store bookings, compute total cost with discounts, and display results dynamically.
*/

// -------------------------------
// Bootstrapping: session + timezone
// -------------------------------
// Start/continue the session so bookings persist across page reloads (same browser session).
session_start();
// Force all date()/time outputs to use Philippine local time.
date_default_timezone_set("Asia/Manila");


$SERVICES = [
// -------------------------------
// Service catalog (price per kg)
// -------------------------------
// Keys (regular|express|dry) are used by the form and by the booking records stored in the session.
    "regular" => ["label" => "Regular Wash", "price_per_kg" => 20.00],
    "express" => ["label" => "Express Wash", "price_per_kg" => 30.50],
    "dry" => ["label" => "Dry Cleaning", "price_per_kg" => 50.00],
];


// -------------------------------
// Helper functions
// -------------------------------
function loadTemplate(string $path): string
{
// Loads an HTML template file as a string (keeps HTML separate from PHP logic).
    return file_get_contents($path);
}

// Formats a numeric value as currency (2 decimal places).
function money(float $value): string
{
    return number_format($value, 2);
}

// Returns the discount rate based on STRICT thresholds (must match the lab rules):
//  - weight > 20kg => 15%
//  - weight > 10kg => 10%
//  - otherwise     => 0%
function discountRate(float $weightKg): float
{
    if ($weightKg > 20) {
        return 0.15;
    }

    if ($weightKg > 10) {
        return 0.10;
    }

    return 0.00;
}

// Computes subtotal, discount, and final total given the weight and service price.
function computeTotals(float $weightKg, float $pricePerKg): array
{
    $subtotal = $weightKg * $pricePerKg;
    $discountRate = discountRate($weightKg);
    $discountAmt = $subtotal * $discountRate;
    $total = $subtotal - $discountAmt;

    return [
        "subtotal" => $subtotal,
        "discount_rate" => $discountRate,
        "discount_amt" => $discountAmt,
        "total" => $total,
    ];
}

// Validates user input from the form and returns a list of human-readable error messages.
function validate(array $post, array $services): array
{
    $errors = [];

    if (trim((string)($post["customer_name"] ?? "")) === "") {
        $errors[] = "Name is required.";
    }

    if (trim((string)($post["contact_number"] ?? "")) === "") {
        $errors[] = "Contact number is required.";
    }

    $weightValue = trim((string)($post["weight_kg"] ?? ""));
    if ($weightValue === "" || !is_numeric($weightValue) || (float)$weightValue <= 0) {
        $errors[] = "Weight must be a number greater than zero.";
    }

    $serviceKey = trim((string)($post["service_type"] ?? ""));
    if ($serviceKey === "" || !array_key_exists($serviceKey, $services)) {
        $errors[] = "Service type is required.";
    }

    return $errors;
}

// -------------------------------
// Session state initialization
// -------------------------------
// These session keys are the 'database' of the app (in-memory per browser session).
// bookings          : list of all booking records
// next_id           : auto-increment id for display
// show_prompt       : controls visibility of the 'New Entry' prompt UI
// session_started_at: shown to user for reference/debugging

if (!isset($_SESSION["bookings"])) {
    $_SESSION["bookings"] = [];
}

if (!isset($_SESSION["next_id"])) {
    $_SESSION["next_id"] = 1;
}

if (!isset($_SESSION["show_prompt"])) {
    $_SESSION["show_prompt"] = false;
}

if (!isset($_SESSION["session_started_at"])) {
    $_SESSION["session_started_at"] = date("Y-m-d H:i:s");
}

// -------------------------------
// Action: New Session (clear orders + regenerate session id)
// -------------------------------
// This is mainly for testing: it wipes the current session and starts a brand-new one.

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "new_session") {
    session_unset();
    session_destroy();
    setcookie(session_name(), "", time() - 3600, "/");
    session_start();
    session_regenerate_id(true);
    $_SESSION["bookings"] = [];
    $_SESSION["next_id"] = 1;
    $_SESSION["show_prompt"] = false;
    if (!isset($_SESSION["session_started_at"])) {
        $_SESSION["session_started_at"] = date("Y-m-d H:i:s");
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], "?"));
    exit;
}

// -------------------------------
// Request-scoped variables (used to render the page)
// -------------------------------
// alerts         : validation errors to show to the user
// successMessage : success notice after saving
// formValues     : used to repopulate the form after POST (UX-friendly)

$alerts = [];
$successMessage = "";
$formValues = [
    "name" => "",
    "contact" => "",
    "weight" => "",
    "service" => "",
];

if (isset($_GET["new"])) {
    $_SESSION["show_prompt"] = false;
    if ($_GET["new"] === "1") {
        $formValues = [
            "name" => "",
            "contact" => "",
            "weight" => "",
            "service" => "",
        ];
    }
}

// -------------------------------
// Action: Book Laundry (form submit)
// -------------------------------
// 1) Collect inputs
// 2) Validate
// 3) Compute totals + discount
// 4) Save booking record into $_SESSION['bookings']

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "book") {
    $formValues["name"] = trim((string)($_POST["customer_name"] ?? ""));
    $formValues["contact"] = trim((string)($_POST["contact_number"] ?? ""));
    $formValues["weight"] = trim((string)($_POST["weight_kg"] ?? ""));
    $formValues["service"] = trim((string)($_POST["service_type"] ?? ""));

    $alerts = validate($_POST, $SERVICES);

    if (empty($alerts)) {
        $weightKg = (float)$formValues["weight"];
        $serviceKey = $formValues["service"];
        $service = $SERVICES[$serviceKey];
        $totals = computeTotals($weightKg, (float)$service["price_per_kg"]);

        $booking = [
            "id" => $_SESSION["next_id"],
            "name" => $formValues["name"],
            "contact" => $formValues["contact"],
            "weight_kg" => $weightKg,
            "service_key" => $serviceKey,
            "service_label" => $service["label"],
            "price_per_kg" => (float)$service["price_per_kg"],
            "subtotal" => $totals["subtotal"],
            "discount_rate" => $totals["discount_rate"],
            "discount_amt" => $totals["discount_amt"],
            "total" => $totals["total"],
            "created_at" => date("Y-m-d H:i:s"),
        ];

        $_SESSION["bookings"][] = $booking;
        $_SESSION["next_id"]++;
        $_SESSION["show_prompt"] = true;
        $successMessage = "Booking saved successfully.";
    }
}

// -------------------------------
// Build alert HTML (errors + success)
// -------------------------------
// Note: htmlspecialchars() prevents HTML injection from user input.

$alertsBlock = "";
if (!empty($alerts)) {
    $alertItems = "";
    foreach ($alerts as $alert) {
        $alertItems .= "<li>" . htmlspecialchars($alert) . "</li>";
    }
    $alertsBlock = "<div class=\"error\"><ul>{$alertItems}</ul></div>";
}

if ($successMessage !== "") {
    $alertsBlock .= "<div class=\"success\">" . htmlspecialchars($successMessage) . "</div>";
}

// -------------------------------
// Build dynamic table rows from session bookings
// -------------------------------

$bookingRows = "";
foreach ($_SESSION["bookings"] as $booking) {
    $discountPercent = (int)round($booking["discount_rate"] * 100) . "%";
    $bookingRows .= "<tr>"
        . "<td>" . htmlspecialchars((string)$booking["id"]) . "</td>"
        . "<td>" . htmlspecialchars($booking["name"]) . "</td>"
        . "<td>" . htmlspecialchars($booking["contact"]) . "</td>"
        . "<td>" . htmlspecialchars(number_format($booking["weight_kg"], 2)) . "</td>"
        . "<td>" . htmlspecialchars($booking["service_label"]) . "</td>"
        . "<td>" . htmlspecialchars(money($booking["price_per_kg"])) . "</td>"
        . "<td>" . htmlspecialchars(money($booking["subtotal"])) . "</td>"
        . "<td>" . htmlspecialchars($discountPercent) . "</td>"
        . "<td>" . htmlspecialchars(money($booking["discount_amt"])) . "</td>"
        . "<td>" . htmlspecialchars(money($booking["total"])) . "</td>"
        . "<td>" . htmlspecialchars($booking["created_at"]) . "</td>"
        . "</tr>";
}

// -------------------------------
// Render templates (HTML files) by replacing placeholder tokens
// -------------------------------
// Using __DIR__ makes paths work regardless of where the script is run from.

$tableTemplate = loadTemplate(__DIR__ . "/templates/table.html");
$tableBlock = str_replace("{{BOOKING_ROWS}}", $bookingRows, $tableTemplate);

$formTemplate = loadTemplate(__DIR__ . "/templates/form.html");
$formBlock = str_replace(
    [
        "{{VAL_NAME}}",
        "{{VAL_CONTACT}}",
        "{{VAL_WEIGHT}}",
        "{{SEL_REGULAR}}",
        "{{SEL_EXPRESS}}",
        "{{SEL_DRY}}",
    ],
    [
        htmlspecialchars($formValues["name"]),
        htmlspecialchars($formValues["contact"]),
        htmlspecialchars($formValues["weight"]),
        $formValues["service"] === "regular" ? "selected" : "",
        $formValues["service"] === "express" ? "selected" : "",
        $formValues["service"] === "dry" ? "selected" : "",
    ],
    $formTemplate
);

$promptTemplate = loadTemplate(__DIR__ . "/templates/prompt.html");
$promptUi = "";
if (!empty($_SESSION["show_prompt"])) {
    $promptUi = "<div class=\"prompt\">"
        . "<p>Do you want to make a new entry?</p>"
        . "<div class=\"btn-row\">"
        . "<a class=\"btn\" href=\"?new=1\">Yes - New Entry</a>"
        . "<a class=\"btn\" href=\"?new=0\">No</a>"
        . "</div>"
        . "</div>";
}
$promptBlock = str_replace("{{PROMPT_UI}}", $promptUi, $promptTemplate);
$promptSection = "";
if (!empty($_SESSION["show_prompt"])) {
    $promptSection = "<section class=\"card\">{$promptBlock}</section>";
}

$layoutTemplate = loadTemplate(__DIR__ . "/templates/layout.html");
$output = str_replace(
    [
        "{{PAGE_TITLE}}",
        "{{SESSION_ID}}",
        "{{SESSION_STARTED_AT}}",
        "{{NEW_SESSION_FORM}}",
        "{{ALERTS_BLOCK}}",
        "{{FORM_BLOCK}}",
        "{{TABLE_BLOCK}}",
        "{{PROMPT_SECTION}}",
    ],
    [
        "Laundry Servicing System (LE3)",
        htmlspecialchars(session_id()),
        htmlspecialchars((string)$_SESSION["session_started_at"]),
        "<form method=\"post\" class=\"session-form\">"
            . "<input type=\"hidden\" name=\"action\" value=\"new_session\" />"
            . "<button type=\"submit\">New Session (Clear Orders)</button>"
            . "</form>",
        $alertsBlock,
        $formBlock,
        $tableBlock,
        $promptSection,
    ],
    $layoutTemplate
);

echo $output;
