<?php
declare(strict_types=1);

require_once __DIR__ . '/../../AnonymizedInsightLogger.php';

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
$calcType      = $data['calc_type'];
$amount        = (float) $data['amount'];
$duration      = (int) $data['duration'];
$stepUpPct     = (float) ($data['step_up_pct'] ?? 0.0);
$currency      = $data['currency'] ?? 'INR';
$pdfDownloaded = !empty($data['pdf_downloaded']);

// Extract the new high-fidelity fields
$interestRate  = isset($data['interest_rate']) ? (float) $data['interest_rate'] : null;
$sipAmount     = isset($data['sip_amount']) ? (float) $data['sip_amount'] : null;
$sipDuration   = isset($data['sip_duration']) ? (int) $data['sip_duration'] : null;
$sipStepUp     = isset($data['sip_step_up']) ? (float) $data['sip_step_up'] : null;
$swpEnabled    = !empty($data['swp_enabled']) ? 1 : 0;
$swpWithdrawal = isset($data['swp_withdrawal']) ? (float) $data['swp_withdrawal'] : null;
$swpDuration   = isset($data['swp_duration']) ? (int) $data['swp_duration'] : null;
$swpStepUp     = isset($data['swp_step_up']) ? (float) $data['swp_step_up'] : null;

// Execute the non-blocking log insert
$logger->logCalculation(
    $calcType,
    $amount,
    $duration,
    $stepUpPct,
    $currency,
    $pdfDownloaded,
    $interestRate,
    $sipAmount,
    $sipDuration,
    $sipStepUp,
    $swpEnabled,
    $swpWithdrawal,
    $swpDuration,
    $swpStepUp
);

// Fast HTTP 204 No Content response
http_response_code(204);
exit;
