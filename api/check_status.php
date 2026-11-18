<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();
$orderId = intval($_GET['order_id'] ?? $_GET['id'] ?? 0);

if ($orderId <= 0) {
    try {
        $stmtLast = $db->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
        $lastRow = $stmtLast->fetch();
        if ($lastRow && !empty($lastRow['id'])) {
            $orderId = intval($lastRow['id']);
        }
    } catch(Exception $e) {}
}

if ($orderId <= 0) {
    echo json_encode(['status' => 'PENDING', 'paid' => false]);
    exit;
}

try {
    $baseOffset = 522837993;
    $rawId = ($orderId > $baseOffset) ? ($orderId - $baseOffset) : $orderId;
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id OR id = :raw_id LIMIT 1");
    $stmt->execute(['id' => $orderId, 'raw_id' => $rawId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['status' => 'NOT_FOUND', 'paid' => false]);
        exit;
    }

    $currentStatus = trim($order['payment_status'] ?? '');
    $stLower = strtolower($currentStatus);

    // Status válidos que indicam pagamento realmente aprovado pelo gateway/banco
    $isPaid = in_array($stLower, ['pago', 'pagamento confirmado', 'aprovado', 'paid', 'approved', 'confirmed', 'completed', 'paid_out']);


    if ($isPaid) {
        echo json_encode([
            'status' => 'PAID',
            'payment_status' => 'Pago',
            'paid' => true,
            'message' => 'Pagamento Confirmado com Sucesso!'
        ]);
        exit;
    }

    // Se ainda não estiver marcado como pago no MySQL, faz a CONSULTA EM TEMPO REAL via API com o Gateway
    $transactionId = trim($order['transaction_id'] ?? '');
    
    if (!empty($transactionId)) {
        // Carregar Gateway Ativo
        $activeGateway = 'payberry';
        $payberryPublicKey = getenv('PAYBERRY_PUBLIC_KEY') ?: base64_decode('cGtfbGl2ZV83b3BtSzhZWFNVNGQzdVN5bkxlWFQyZ3VuMDI4dUw5YQ==');
        $payberrySecretKey = getenv('PAYBERRY_SECRET_KEY') ?: base64_decode('c2tfbGl2ZV8wOHFWZm5uZ0ZVOEt6eU5WWlBOalp6SW1hb0JxRTNBd2JGUzk3THVocjVHczBsMHc=');
        $velanaApiKey = getenv('VELANA_API_KEY') ?: '';
        $velanaSecretKey = getenv('VELANA_SECRET_KEY') ?: '';

        $sigilopayApiKey = '';
        $sigilopaySecretKey = '';
        $unipayPublicKey = '';
        $unipaySecretKey = '';

        try {
            $stmtGw = $db->query("SELECT * FROM gateway_settings WHERE id = 1 LIMIT 1");
            $gw = $stmtGw->fetch();
            if ($gw) {
                if (!empty($gw['active_gateway'])) $activeGateway = trim($gw['active_gateway']);
                if (!empty($gw['payberry_public_key'])) $payberryPublicKey = trim($gw['payberry_public_key']);
                if (!empty($gw['payberry_secret_key'])) $payberrySecretKey = trim($gw['payberry_secret_key']);
                if (!empty($gw['velana_api_key'])) $velanaApiKey = trim($gw['velana_api_key']);
                if (!empty($gw['velana_secret_key'])) $velanaSecretKey = trim($gw['velana_secret_key']);
                if (!empty($gw['sigilopay_api_key'])) $sigilopayApiKey = trim($gw['sigilopay_api_key']);
                if (!empty($gw['sigilopay_secret_key'])) $sigilopaySecretKey = trim($gw['sigilopay_secret_key']);
                if (!empty($gw['unipay_public_key'])) $unipayPublicKey = trim($gw['unipay_public_key']);
                if (!empty($gw['unipay_secret_key'])) $unipaySecretKey = trim($gw['unipay_secret_key']);
            }
        } catch (Exception $eGw) {}

        $livePaid = false;

        if ($activeGateway === 'unipay') {
            // Consulta de status na UniPay BR / FastSoft: GET /api/user/transactions/{id}
            $uSec = !empty($unipaySecretKey) ? $unipaySecretKey : (!empty($gw['secret_key']) ? $gw['secret_key'] : '');
            $uPub = !empty($unipayPublicKey) ? $unipayPublicKey : (!empty($gw['api_key']) ? $gw['api_key'] : '');

            $unipayCheckUrls = [
                'https://api.unipaybr.com/api/user/transactions/' . urlencode($transactionId),
                'https://api.fastsoftbrasil.com/api/user/transactions/' . urlencode($transactionId)
            ];

            $authHeaders = [];
            if (!empty($uSec)) {
                $authHeaders[] = 'Basic ' . base64_encode('x:' . $uSec);
                if (!empty($uPub)) {
                    $authHeaders[] = 'Basic ' . base64_encode($uPub . ':' . $uSec);
                }
            }

            foreach ($unipayCheckUrls as $checkUrl) {
                foreach ($authHeaders as $authHeader) {
                    $httpHeaders = [
                        'Authorization: ' . $authHeader,
                        'Accept: application/json',
                        'User-Agent: UMBRELLAB2B/1.0'
                    ];
                    if (!empty($uPub)) {
                        $httpHeaders[] = 'x-api-key: ' . $uPub;
                    }

                    $ch = curl_init($checkUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER     => $httpHeaders,
                        CURLOPT_TIMEOUT        => 6,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false
                    ]);
                    $res = curl_exec($ch);
                    $chCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    @curl_close($ch);

                    if ($res && ($chCode === 200 || $chCode === 201)) {
                        $resJson = json_decode($res, true);
                        $st = strtoupper(
                            $resJson['status'] ??
                            $resJson['paymentStatus'] ??
                            $resJson['data']['status'] ??
                            $resJson['data']['paymentStatus'] ??
                            ''
                        );
                        if (in_array($st, ['PAID', 'APPROVED', 'CONFIRMED', 'COMPLETED', 'PAID_OUT', 'CAPTURED', 'PAGO'])) {
                            $livePaid = true;
                            break 2;
                        }
                    }
                }
            }
        } else if ($activeGateway === 'payberry') {
            // Consulta de transação na Payberry: GET /v1/payment-transaction/info/{id}
            $ch = curl_init('https://api.payberry.com.br/v1/payment-transaction/info/' . urlencode($transactionId));
            $credentials = $payberryPublicKey . ':' . $payberrySecretKey;
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($credentials),
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $res = curl_exec($ch);
            @curl_close($ch);

            if ($res) {
                $resJson = json_decode($res, true);
                $st = strtoupper($resJson['status'] ?? $resJson['data'][0]['status'] ?? '');
                if (in_array($st, ['PAID', 'APPROVED', 'CONFIRMED', 'COMPLETED', 'PAID_OUT'])) {
                    $livePaid = true;
                }
            }
        } else if ($activeGateway === 'velana') {
            // Consulta de transação na Velana: GET /v1/transactions/{id}
            $ch = curl_init('https://api.velana.com.br/v1/transactions/' . urlencode($transactionId));
            $basicAuthHeader = 'Basic ' . base64_encode($velanaSecretKey . ':x');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $basicAuthHeader,
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $res = curl_exec($ch);
            @curl_close($ch);

            if ($res) {
                $resJson = json_decode($res, true);
                $st = strtoupper($resJson['status'] ?? $resJson['paymentStatus'] ?? $resJson['data']['status'] ?? '');
                if (in_array($st, ['PAID', 'APPROVED', 'CONFIRMED', 'COMPLETED', 'PAID_OUT'])) {
                    $livePaid = true;
                }
            }
        } else if ($activeGateway === 'sigilopay') {
            // Consulta de status oficial SigiloPay v1 (app.sigilopay.com.br/docs/v1)
            $pubK = !empty($sigilopayApiKey)    ? $sigilopayApiKey    : 'jonasmgtd2_1qgte20cj4igtcb8';
            $secK = !empty($sigilopaySecretKey) ? $sigilopaySecretKey : 'mx68p6qujgsetcgfe2z2ui22zqv1c67k6xi5sh4nodkh6njcfgd12as149s5k6xs';

            $checkUrls = [
                'https://app.sigilopay.com.br/api/v1/gateway/transactions/' . urlencode($transactionId),
                'https://app.sigilopay.com.br/api/v1/gateway/pix/' . urlencode($transactionId),
                'https://app.sigilopay.com.br/api/v1/transactions/' . urlencode($transactionId),
                'https://api.sigilopay.com.br/api/v1/gateway/pix/receive/' . urlencode($transactionId)
            ];

            foreach ($checkUrls as $checkUrl) {
                $ch = curl_init($checkUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        'x-public-key: ' . $pubK,
                        'x-secret-key: ' . $secK,
                        'Accept: application/json'
                    ],
                    CURLOPT_TIMEOUT        => 6,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);
                $res = curl_exec($ch);
                $chCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                @curl_close($ch);

                if ($res && ($chCode === 200 || $chCode === 201)) {
                    $resJson = json_decode($res, true);
                    $st = strtoupper(
                        $resJson['status'] ??
                        $resJson['paymentStatus'] ??
                        $resJson['payment_status'] ??
                        $resJson['data']['status'] ??
                        $resJson['data']['paymentStatus'] ??
                        ''
                    );
                    if (in_array($st, ['PAID', 'APPROVED', 'CONFIRMED', 'COMPLETED', 'PAID_OUT', 'CAPTURED', 'PAGO'])) {
                        $livePaid = true;
                        break;
                    }
                }
            }
        }

        if ($livePaid) {
            // Atualiza imediatamente a order no banco de dados MySQL para 'Pago'
            $stmtUp = $db->prepare("UPDATE orders SET payment_status = 'Pago' WHERE id = :id OR id = :raw_id");
            $stmtUp->execute(['id' => $orderId, 'raw_id' => $rawId]);

            echo json_encode([
                'status' => 'PAID',
                'payment_status' => 'Pago',
                'paid' => true,
                'message' => 'Pagamento Confirmado via API!'
            ]);
            exit;
        }
    }

    echo json_encode([
        'status' => 'PENDING',
        'payment_status' => $currentStatus ?: 'Aguardando Pagamento',
        'paid' => false
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'ERROR', 'paid' => false, 'error' => $e->getMessage()]);
}
