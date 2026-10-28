<?php
// One concurrent client: waits for the shared start time, then completes an order as the customer.
// Usage: php worker.php <customerId> <orderId> <startUnixMicro>
require __DIR__ . '/boot.php';

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Auth;

[, $customerId, $orderId, $start] = $argv;
Auth::loginUsingId((int) $customerId);
while ((int) (microtime(true) * 1e6) < (int) $start) usleep(200);
$res = app(OrderController::class)->complete((int) $orderId);
echo json_encode(['order' => (int) $orderId, 'status' => $res->getStatusCode(), 'message' => $res->getData(true)['message'] ?? '']), PHP_EOL;
