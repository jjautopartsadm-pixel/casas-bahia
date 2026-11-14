<?php
/**
 * includes/pixel_tracker.php - Helper de Rastreamento Meta Pixel & Conversions API (CAPI) 100%
 */
require_once __DIR__ . '/../config/database.php';

function getPixelSettings() {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }

    $default = [
        'pixel_id' => '',
        'access_token' => '',
        'active_status' => 1
    ];

    try {
        $db = getDBConnection();
        $stmt = $db->query("SELECT * FROM pixel_settings WHERE id = 1 LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            $settings = array_merge($default, $row);
            return $settings;
        }
    } catch (Exception $e) {}

    $settings = $default;
    return $settings;
}

function normalizeHashUserData($userData = []) {
    $hashed = [];

    // Email
    if (!empty($userData['email'])) {
        $cleanEmail = strtolower(trim($userData['email']));
        $hashed['em'] = [hash('sha256', $cleanEmail)];
    }

    // Telefone (normalizar para padrão internacional E.164: ex 5511999999999)
    if (!empty($userData['phone'])) {
        $cleanPhone = preg_replace('/\D/', '', $userData['phone']);
        if (strlen($cleanPhone) >= 10 && substr($cleanPhone, 0, 2) !== '55') {
            $cleanPhone = '55' . $cleanPhone;
        }
        $hashed['ph'] = [hash('sha256', $cleanPhone)];
    }

    // Nome
    if (!empty($userData['name'])) {
        $parts = explode(' ', trim($userData['name']), 2);
        $fn = strtolower(trim($parts[0] ?? ''));
        $ln = strtolower(trim($parts[1] ?? ''));
        if (!empty($fn)) $hashed['fn'] = [hash('sha256', $fn)];
        if (!empty($ln)) $hashed['ln'] = [hash('sha256', $ln)];
    }

    // IP e User Agent
    $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }
    $hashed['client_ip_address'] = $clientIp;
    $hashed['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0';

    // Cookies do Facebook (_fbp e _fbc)
    if (!empty($_COOKIE['_fbp'])) {
        $hashed['fbp'] = $_COOKIE['_fbp'];
    }
    if (!empty($_COOKIE['_fbc'])) {
        $hashed['fbc'] = $_COOKIE['_fbc'];
    }

    return $hashed;
}

function sendMetaCapiEvent($pixelId, $accessToken, $eventName, $eventData = [], $userData = [], $eventId = null, $testEventCode = null) {
    if (empty($pixelId) || empty($accessToken)) {
        return false;
    }

    $url = "https://graph.facebook.com/v18.0/" . urlencode($pixelId) . "/events?access_token=" . urlencode($accessToken);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
    $currentUrl = $protocol . $host . $reqUri;

    $userPayload = normalizeHashUserData($userData);

    $customData = [];
    if (!empty($eventData['value'])) {
        $customData['value'] = (float)$eventData['value'];
    }
    if (!empty($eventData['currency'])) {
        $customData['currency'] = $eventData['currency'];
    }
    if (!empty($eventData['content_name'])) {
        $customData['content_name'] = $eventData['content_name'];
    }
    if (!empty($eventData['content_ids'])) {
        $customData['content_ids'] = is_array($eventData['content_ids']) ? $eventData['content_ids'] : [$eventData['content_ids']];
    }
    if (!empty($eventData['content_type'])) {
        $customData['content_type'] = $eventData['content_type'];
    }
    if (!empty($eventData['order_id'])) {
        $customData['order_id'] = (string)$eventData['order_id'];
    }
    if (!empty($eventData['num_items'])) {
        $customData['num_items'] = (int)$eventData['num_items'];
    }

    $eventPayload = [
        'event_name' => $eventName,
        'event_time' => time(),
        'event_source_url' => $currentUrl,
        'action_source' => 'website',
        'user_data' => $userPayload,
        'custom_data' => $customData
    ];

    if (!empty($eventId)) {
        $eventPayload['event_id'] = $eventId;
    }

    $reqPayload = ['data' => [$eventPayload]];
    if (!empty($testEventCode)) {
        $reqPayload['test_event_code'] = $testEventCode;
    }

    $body = json_encode($reqPayload);

    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            return $res;
        } else {
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $body,
                    'timeout' => 3
                ]
            ];
            $ctx = stream_context_create($opts);
            return @file_get_contents($url, false, $ctx);
        }
    } catch (Exception $e) {
        return false;
    }
}

function renderFacebookPixel($eventName = 'PageView', $eventData = [], $userData = []) {
    try {
        $pixel = getPixelSettings();
        if (empty($pixel['pixel_id']) || intval($pixel['active_status']) !== 1) {
            return;
        }

        $pixelId = trim($pixel['pixel_id']);
        $accessToken = trim($pixel['access_token'] ?? '');
        $testEventCode = trim($pixel['test_event_code'] ?? '');

        // Gerar Event ID único para Deduplicação perfeita (Pixel Navegador + CAPI Servidor)
        $uniqueSuffix = substr(md5(uniqid(rand(), true)), 0, 10);
        $eventId = 'cb_' . strtolower($eventName) . '_' . time() . '_' . $uniqueSuffix;
        if ($eventName === 'Purchase' && !empty($eventData['order_id'])) {
            $eventId = 'cb_purchase_' . preg_replace('/[^0-9]/', '', $eventData['order_id']);
        }

        // 1. DISPARAR EVENTO NO SERVIDOR (CAPI - Meta Conversions API)
        if (!empty($accessToken)) {
            sendMetaCapiEvent($pixelId, $accessToken, $eventName, $eventData, $userData, $eventId, $testEventCode);
        }

        // 2. DISPARAR EVENTO NO NAVEGADOR (Browser Pixel com fbq)
        $jsonParam = !empty($eventData) ? json_encode($eventData, JSON_UNESCAPED_UNICODE) : '';
        ?>
        <!-- Facebook Pixel Code -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/pt_BR/fbevents.js');
        fbq('init', '<?= htmlspecialchars($pixelId) ?>');
        fbq('track', 'PageView', {}, {eventID: 'pv_<?= $eventId ?>'});
        <?php if ($eventName !== 'PageView'): ?>
          fbq('track', '<?= htmlspecialchars($eventName) ?>', <?= $jsonParam ?: '{}' ?>, {eventID: '<?= $eventId ?>'});
        <?php endif; ?>
        </script>
        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= htmlspecialchars($pixelId) ?>&ev=PageView&noscript=1"/></noscript>
        <!-- End Facebook Pixel Code -->
        <?php
    } catch (Exception $e) {}
}
