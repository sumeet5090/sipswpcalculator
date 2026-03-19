<?php
declare(strict_types=1);

require_once __DIR__ . '/src/AnonymizedInsightLogger.php';

// Only handle POST requests from our frontend
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// Receive JSON payload from fetch()
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!isset($data['calc_type'], $data['amount'], $data['duration'])) {
    http_response_code(400); // Bad Request
    die('Invalid payload');
}

$logger = new AnonymizedInsightLogger();

// Extract the mapped JS state values
$calcType   = $data['calc_type'];
$amount     = (float) $data['amount'];
$duration   = (int) $data['duration'];
$stepUpPct  = (float) ($data['step_up_pct'] ?? 0.0);
$currency   = $data['currency'] ?? 'INR';

// Execute the non-blocking log insert
$logger->logCalculation($calcType, $amount, $duration, $stepUpPct, $currency);

// Fast HTTP 204 No Content response
http_response_code(204);
exit;
