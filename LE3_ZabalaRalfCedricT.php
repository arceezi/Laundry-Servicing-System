<?php
/*
Name: Ralf Cedric Zabala
Date Created: 2025-02-14
Problem Description: Laundry Servicing System using multidimensional arrays in PHP to store bookings, compute total cost with discounts, and display results dynamically.
*/

session_start();

$SERVICES = [
    "regular" => ["label" => "Regular Wash", "price_per_kg" => 20.00],
    "express" => ["label" => "Express Wash", "price_per_kg" => 30.50],
    "dry" => ["label" => "Dry Cleaning", "price_per_kg" => 50.00],
];

function loadTemplate(string $path): string
{
    return file_get_contents($path);
}

function money(float $value): string
{
    return number_format($value, 2);
}

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

if (!isset($_SESSION["bookings"])) {
    $_SESSION["bookings"] = [];
}

if (!isset($_SESSION["next_id"])) {
    $_SESSION["next_id"] = 1;
}

if (!isset($_SESSION["show_prompt"])) {
    $_SESSION["show_prompt"] = false;
}

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

$layoutTemplate = loadTemplate(__DIR__ . "/templates/layout.html");
$output = str_replace(
    ["{{PAGE_TITLE}}", "{{ALERTS_BLOCK}}", "{{FORM_BLOCK}}", "{{TABLE_BLOCK}}", "{{PROMPT_BLOCK}}"],
    ["Laundry Servicing System (LE3)", $alertsBlock, $formBlock, $tableBlock, $promptBlock],
    $layoutTemplate
);

echo $output;
