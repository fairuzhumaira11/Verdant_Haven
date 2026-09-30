<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/report-data.php';
require dirname(__DIR__, 2) . '/includes/pdf.php';
require_roles(['admin']);
$from = query_input('from', date('Y-m-d', strtotime('-29 days')), 10);
$to = query_input('to', date('Y-m-d'), 10);
try {
    report_dates($from, $to);
} catch (Exception $error) {
    http_response_code(400);
    exit(h($error->getMessage()));
}
$pdf = report_pdf(sales_report($from, $to));
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="verdant-sales-' . $from . '-to-' . $to . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
