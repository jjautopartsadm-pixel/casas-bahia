<?php
/**
 * Class PayberryGateway
 * Helper para integração com a API da Payberry (https://api.payberry.com.br)
 */
class PayberryGateway {
    private $baseUrl = 'https://api.payberry.com.br';
    private $publicKey;
    private $secretKey;

    public function __construct($publicKey = '', $secretKey = '') {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }

    /**
     * Retorna o cabeçalho Authorization no formato Basic Base64
     */
    private function getAuthHeader() {
        $credentials = $this->publicKey . ':' . $this->secretKey;
        return 'Authorization: Basic ' . base64_encode($credentials);
    }

    /**
     * Realiza requisição HTTP via cURL
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            $this->getAuthHeader(),
            'Accept: application/json'
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        @curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'http_code' => $httpCode,
                'error' => 'Erro de cURL: ' . $error
            ];
        }

        $json = json_decode($response, true);
        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'data' => $json ?? $response
        ];
    }

    /**
     * Retorna dados da empresa
     */
    public function getCompany() {
        return $this->request('/v1/company', 'GET');
    }

    /**
     * Gera transação Pix In
     */
    public function createPixTransaction($amountInCents, $customer, $itemTitle, $postbackUrl = '', $metadata = []) {
        $payload = [
            'amount' => $amountInCents,
            'payment_method' => 'pix',
            'postback_url' => $postbackUrl,
            'customer' => [
                'name' => $customer['name'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => preg_replace('/\D/', '', $customer['phone'] ?? ''),
                'document' => [
                    'number' => preg_replace('/\D/', '', $customer['document'] ?? ''),
                    'type' => (strlen(preg_replace('/\D/', '', $customer['document'] ?? '')) > 11) ? 'cnpj' : 'cpf'
                ]
            ],
            'items' => [
                [
                    'title' => substr($itemTitle, 0, 120),
                    'unit_price' => $amountInCents,
                    'quantity' => 1,
                    'tangible' => true
                ]
            ],
            'pix' => [
                'expires_in_days' => 1
            ],
            'metadata' => (object) $metadata
        ];

        return $this->request('/v1/payment-transaction/create', 'POST', $payload);
    }

    /**
     * Gera transação Cartão de Crédito
     */
    public function createCreditCardTransaction($amountInCents, $customer, $cardDetails, $itemTitle, $postbackUrl = '', $installments = 1) {
        $payload = [
            'amount' => $amountInCents,
            'payment_method' => 'credit_card',
            'postback_url' => $postbackUrl,
            'customer' => [
                'name' => $customer['name'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => preg_replace('/\D/', '', $customer['phone'] ?? ''),
                'document' => [
                    'number' => preg_replace('/\D/', '', $customer['document'] ?? ''),
                    'type' => (strlen(preg_replace('/\D/', '', $customer['document'] ?? '')) > 11) ? 'cnpj' : 'cpf'
                ]
            ],
            'card' => [
                'number' => preg_replace('/\D/', '', $cardDetails['number'] ?? ''),
                'holder_name' => strtoupper($cardDetails['holder_name'] ?? ''),
                'expiration_month' => intval($cardDetails['expiration_month'] ?? 1),
                'expiration_year' => intval($cardDetails['expiration_year'] ?? 2030),
                'cvv' => trim($cardDetails['cvv'] ?? '')
            ],
            'installments' => intval($installments),
            'items' => [
                [
                    'title' => substr($itemTitle, 0, 120),
                    'unit_price' => $amountInCents,
                    'quantity' => 1,
                    'tangible' => true
                ]
            ],
            'metadata' => new stdClass()
        ];

        return $this->request('/v1/payment-transaction/create', 'POST', $payload);
    }

    /**
     * Consulta status de uma transação
     */
    public function getTransactionInfo($transactionId) {
        return $this->request('/v1/payment-transaction/info/' . urlencode($transactionId), 'GET');
    }

    /**
     * Estornar transação
     */
    public function refundTransaction($transactionId, $amountInCents = null) {
        $payload = [];
        if ($amountInCents !== null) {
            $payload['amount'] = $amountInCents;
        }
        return $this->request('/v1/payment-transaction/' . urlencode($transactionId) . '/refund', 'POST', $payload);
    }

    /**
     * Consultar Saldo da Carteira (Pix Out)
     */
    public function getBalance() {
        return $this->request('/v1/payment-transaction/balance', 'GET');
    }
}
