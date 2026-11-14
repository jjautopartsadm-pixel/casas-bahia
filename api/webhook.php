<?php
/**
 * Webhook / Postback Receiver para o Payberry Gateway
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$rawBody = file_get_contents('php://input');
$logMsg = date('[Y-m-d H:i:s]') . " WEBHOOK RECEIVED: " . $rawBody . "\n";
@file_put_contents(__DIR__ . '/../gateway_debug.log', $logMsg, FILE_APPEND);

$data = json_decode($rawBody, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

$db = getDBConnection();

$transactionId = $data['Id'] ?? $data['id'] ?? $data['transaction_id'] ?? $data['transactionId'] ?? $data['data']['id'] ?? $data['data']['transactionId'] ?? '';
$externalId = $data['ExternalId'] ?? $data['external_id'] ?? $data['external_ref'] ?? $data['externalRef'] ?? $data['data']['externalRef'] ?? '';

if (empty($externalId) && !empty($data['metadata'])) {
    if (is_string($data['metadata'])) {
        $metaObj = json_decode($data['metadata'], true);
        $externalId = $metaObj['order_id'] ?? $metaObj['orderId'] ?? '';
    } elseif (is_array($data['metadata'])) {
        $externalId = $data['metadata']['order_id'] ?? $data['metadata']['orderId'] ?? '';
    }
}
if (empty($externalId) && !empty($data['data']['metadata'])) {
    if (is_string($data['data']['metadata'])) {
        $metaObj = json_decode($data['data']['metadata'], true);
        $externalId = $metaObj['order_id'] ?? $metaObj['orderId'] ?? '';
    } elseif (is_array($data['data']['metadata'])) {
        $externalId = $data['data']['metadata']['order_id'] ?? $data['data']['metadata']['orderId'] ?? '';
    }
}

$status = strtoupper($data['Status'] ?? $data['status'] ?? $data['payment_status'] ?? $data['data']['status'] ?? $data['data']['payment_status'] ?? '');

$mappedStatus = 'Aguardando Pagamento';
switch ($status) {
    case 'PAID':
    case 'APPROVED':
    case 'CONFIRMED':
    case 'COMPLETED':
    case 'PAID_OUT':
    case 'CAPTURED':
        $mappedStatus = 'Pago';
        break;
    case 'REFUNDED':
    case 'CHARGEBACK':
        $mappedStatus = 'Estornado';
        break;
    case 'REFUSED':
    case 'EXPIRED':
    case 'ERROR':
    case 'CANCELED':
    case 'CANCELLED':
    case 'FAILED':
        $mappedStatus = 'Cancelado';
        break;
    case 'PENDING':
    case 'WAITING':
    default:
        $mappedStatus = 'Aguardando Pagamento';
        break;
}

try {
    $updated = false;
    if (!empty($externalId)) {
        $baseOffset = 522837993;
        $rawId = (intval($externalId) > $baseOffset) ? (intval($externalId) - $baseOffset) : intval($externalId);
        $stmt = $db->prepare("UPDATE orders SET payment_status = :status WHERE id = :eid OR id = :raw_id");
        $stmt->execute(['status' => $mappedStatus, 'eid' => $externalId, 'raw_id' => $rawId]);
        if ($stmt->rowCount() > 0) $updated = true;
    }
    
    if (!$updated && !empty($transactionId)) {
        $stmt = $db->prepare("UPDATE orders SET payment_status = :status WHERE transaction_id = :tx1 OR id = :tx2 OR pix_code LIKE :txlike");
        $stmt->execute(['status' => $mappedStatus, 'tx1' => $transactionId, 'tx2' => $transactionId, 'txlike' => '%' . $transactionId . '%']);
        if ($stmt->rowCount() > 0) $updated = true;
    }

    @file_put_contents(__DIR__ . '/../gateway_debug.log', date('[Y-m-d H:i:s]') . " WEBHOOK PROCESSED | Order: $externalId | Tx: $transactionId | Status: $mappedStatus | Updated: " . ($updated ? 'YES' : 'NO') . "\n", FILE_APPEND);
} catch (Exception $e) {
    @file_put_contents(__DIR__ . '/../gateway_debug.log', "[ERROR UPDATE ORDER] " . $e->getMessage() . "\n", FILE_APPEND);
}

http_response_code(200);
echo json_encode(['status' => 'success', 'received_status' => $status]);
