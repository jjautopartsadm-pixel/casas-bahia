<?php
/**
 * Z-API WhatsApp Integration Service
 * Casas Bahia E-Commerce
 */

require_once __DIR__ . '/../config/database.php';

class ZapiService {

    /**
     * Obter as configurações ativas da Z-API do banco de dados
     */
    public static function getSettings($db = null) {
        if (!$db) {
            $db = getDBConnection();
        }
        
        $settings = [
            'instance_id' => '',
            'instance_token' => '',
            'client_token' => '',
            'is_active' => 1,
            'send_image' => 1,
            'message_template' => "Olá, *{nome}*! 👋\nRecebemos o seu pedido na *Casas Bahia*!\n\n📦 *Pedido:* #{pedido_id}\n🛒 *Produto:* {produto}\n💰 *Valor:* {valor}\n\n🔑 *PIX COPIA E COLA:*\n{pix_copia_cola}\n\n📱 Ou pague escaneando o QR Code que enviamos acima.\n\nAgradecemos pela preferência! Caso precise de ajuda, estamos à disposição."
        ];

        try {
            $stmt = $db->query("SELECT * FROM zapi_settings WHERE id = 1 LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $settings['instance_id'] = trim($row['instance_id'] ?? '');
                $settings['instance_token'] = trim($row['instance_token'] ?? '');
                $settings['client_token'] = trim($row['client_token'] ?? '');
                $settings['is_active'] = isset($row['is_active']) ? intval($row['is_active']) : 1;
                $settings['send_image'] = isset($row['send_image']) ? intval($row['send_image']) : 1;
                if (!empty($row['message_template'])) {
                    $settings['message_template'] = $row['message_template'];
                }
            }
        } catch (Exception $e) {}

        return $settings;
    }

    /**
     * Formatar e higienizar número para o padrão internacional do WhatsApp (ex: 5511999999999)
     */
    public static function formatPhone($phone) {
        $digits = preg_replace('/[^\d]/', '', (string)$phone);
        
        if (empty($digits)) {
            return null;
        }

        // Se começar com 0 (ex: 011999998888), remove o 0 inicial
        if (strlen($digits) > 10 && substr($digits, 0, 1) === '0') {
            $digits = substr($digits, 1);
        }

        // Se tiver 10 ou 11 dígitos (DDD + Número), adiciona o DDI do Brasil (55)
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        // Se tiver 12 ou 13 dígitos e já começar com 55, está correto
        if (strlen($digits) >= 12 && strlen($digits) <= 14 && substr($digits, 0, 2) === '55') {
            return $digits;
        }

        // Retorna o que conseguiu limpar caso não se encaixe nos padrões acima
        return strlen($digits) >= 10 ? $digits : null;
    }

    /**
     * Montar o texto da mensagem substituindo as tags dinâmicas
     */
    public static function parseTemplate($template, $data = []) {
        $name = trim($data['name'] ?? $data['client_name'] ?? 'Cliente');
        $parts = explode(' ', $name);
        $firstName = !empty($parts[0]) ? $parts[0] : 'Cliente';
        
        $orderId = (string)($data['order_id'] ?? $data['id'] ?? time());
        $product = (string)($data['product_title'] ?? $data['product'] ?? 'Produto Casas Bahia');
        
        $price = $data['price'] ?? $data['product_price'] ?? $data['pix_price'] ?? '0,00';
        if (is_numeric($price)) {
            $priceFormatted = 'R$ ' . number_format((float)$price, 2, ',', '.');
        } else {
            $priceFormatted = (strpos((string)$price, 'R$') === false ? 'R$ ' : '') . $price;
        }

        $pixCode = (string)($data['pix_code'] ?? $data['pix'] ?? '');
        $cpf = (string)($data['cpf'] ?? $data['client_cpf'] ?? '');
        $pixUrl = (string)($data['pix_url'] ?? '');
        if (empty($pixUrl) && !empty($orderId)) {
            $pixUrl = 'pix.php?order_id=' . $orderId;
        }

        $replacements = [
            '{nome}'           => $name,
            '{primeiro_nome}'  => $firstName,
            '{pedido_id}'      => $orderId,
            '{produto}'        => $product,
            '{valor}'          => $priceFormatted,
            '{pix_copia_cola}' => $pixCode,
            '{cpf}'            => $cpf,
            '{link_pix}'       => $pixUrl
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Enviar texto via Z-API
     */
    public static function sendText($instanceId, $instanceToken, $clientToken, $phone, $message) {
        $phoneFormatted = self::formatPhone($phone);
        if (!$phoneFormatted) {
            return ['success' => false, 'error' => 'Número de telefone inválido.'];
        }

        if (empty($instanceId) || empty($instanceToken)) {
            return ['success' => false, 'error' => 'Instância e Token da Z-API não configurados.'];
        }

        $url = "https://api.z-api.io/instances/" . trim($instanceId) . "/token/" . trim($instanceToken) . "/send-text";
        $body = json_encode([
            'phone'   => $phoneFormatted,
            'message' => $message
        ]);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        if (!empty($clientToken)) {
            $headers[] = 'Client-Token: ' . trim($clientToken);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $isSuccess = ($httpCode >= 200 && $httpCode < 300);
        
        self::logDispatch([
            'phone' => $phoneFormatted,
            'type' => 'text',
            'status' => $isSuccess ? 'sent' : 'failed',
            'http_code' => $httpCode,
            'response' => $response ?: $curlError
        ]);

        return [
            'success'   => $isSuccess,
            'http_code' => $httpCode,
            'response'  => $response,
            'error'     => $curlError
        ];
    }

    /**
     * Enviar imagem (QR Code Pix) via Z-API
     */
    public static function sendImage($instanceId, $instanceToken, $clientToken, $phone, $imageUrl, $caption = '') {
        $phoneFormatted = self::formatPhone($phone);
        if (!$phoneFormatted) {
            return ['success' => false, 'error' => 'Número de telefone inválido.'];
        }

        if (empty($instanceId) || empty($instanceToken)) {
            return ['success' => false, 'error' => 'Instância e Token da Z-API não configurados.'];
        }

        $url = "https://api.z-api.io/instances/" . trim($instanceId) . "/token/" . trim($instanceToken) . "/send-image";
        $body = json_encode([
            'phone'   => $phoneFormatted,
            'image'   => $imageUrl,
            'caption' => $caption
        ]);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        if (!empty($clientToken)) {
            $headers[] = 'Client-Token: ' . trim($clientToken);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 18);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $isSuccess = ($httpCode >= 200 && $httpCode < 300);

        self::logDispatch([
            'phone' => $phoneFormatted,
            'type' => 'image',
            'status' => $isSuccess ? 'sent' : 'failed',
            'http_code' => $httpCode,
            'response' => $response ?: $curlError
        ]);

        return [
            'success'   => $isSuccess,
            'http_code' => $httpCode,
            'response'  => $response,
            'error'     => $curlError
        ];
    }

    /**
     * Checar status de conexão da instância Z-API
     */
    public static function checkStatus($instanceId, $instanceToken, $clientToken) {
        if (empty($instanceId) || empty($instanceToken)) {
            return ['connected' => false, 'message' => 'Instância ou Token não configurados.'];
        }

        $url = "https://api.z-api.io/instances/" . trim($instanceId) . "/token/" . trim($instanceToken) . "/status";
        
        $headers = [
            'Accept: application/json'
        ];
        if (!empty($clientToken)) {
            $headers[] = 'Client-Token: ' . trim($clientToken);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        $connected = false;
        if ($data && isset($data['connected']) && ($data['connected'] === true || $data['connected'] === 'true')) {
            $connected = true;
        }

        return [
            'http_code' => $httpCode,
            'connected' => $connected,
            'raw'       => $data,
            'response'  => $response
        ];
    }

    /**
     * Enviar notificação completa do Pix para o cliente (QR Code + Mensagem Personalizada + Copia e Cola)
     */
    public static function sendPixNotification($data, $db = null) {
        $settings = self::getSettings($db);
        
        if (empty($settings['is_active'])) {
            return ['success' => false, 'reason' => 'zapi_inactive'];
        }

        if (empty($settings['instance_id']) || empty($settings['instance_token'])) {
            return ['success' => false, 'reason' => 'zapi_credentials_missing'];
        }

        $phone = $data['phone'] ?? $data['client_phone'] ?? '';
        $phoneFormatted = self::formatPhone($phone);
        if (!$phoneFormatted) {
            return ['success' => false, 'reason' => 'invalid_phone'];
        }

        $qrCodeUrl = $data['qr_code_url'] ?? '';
        $pixCode = $data['pix_code'] ?? '';
        if (empty($qrCodeUrl) && !empty($pixCode)) {
            $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($pixCode);
        }

        $message = self::parseTemplate($settings['message_template'], $data);

        // 1. Se envio de imagem estiver ativado e houver QR Code URL, envia a imagem primeiro
        if (!empty($settings['send_image']) && !empty($qrCodeUrl)) {
            self::sendImage(
                $settings['instance_id'],
                $settings['instance_token'],
                $settings['client_token'],
                $phoneFormatted,
                $qrCodeUrl,
                "📱 QR Code Pix - Casas Bahia (Pedido #" . ($data['order_id'] ?? '') . ")"
            );
        }

        // 2. Envia a mensagem completa com Copia e Cola e Frases personalizadas
        $textResult = self::sendText(
            $settings['instance_id'],
            $settings['instance_token'],
            $settings['client_token'],
            $phoneFormatted,
            $message
        );

        return $textResult;
    }

    /**
     * Gravar log de envio no banco de dados e arquivo de log
     */
    public static function logDispatch($logData) {
        $logLine = date('[Y-m-d H:i:s]') . " [Z-API] Phone: " . ($logData['phone'] ?? '') . " | Type: " . ($logData['type'] ?? '') . " | Status: " . ($logData['status'] ?? '') . " | HTTP: " . ($logData['http_code'] ?? '') . " | Resp: " . substr((string)($logData['response'] ?? ''), 0, 300) . "\n";
        @file_put_contents(__DIR__ . '/../gateway_debug.log', $logLine, FILE_APPEND);

        try {
            $db = getDBConnection();
            $stmt = $db->prepare("
                INSERT INTO zapi_logs (order_id, client_name, phone, message_type, status, response_payload, created_at)
                VALUES (:oid, :cname, :phone, :type, :status, :resp, NOW())
            ");
            $stmt->execute([
                'oid'   => $logData['order_id'] ?? null,
                'cname' => $logData['client_name'] ?? null,
                'phone' => $logData['phone'] ?? '',
                'type'  => $logData['type'] ?? 'text',
                'status'=> $logData['status'] ?? 'sent',
                'resp'  => substr((string)($logData['response'] ?? ''), 0, 1000)
            ]);
        } catch (Exception $e) {}
    }
}