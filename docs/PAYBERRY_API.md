# 🍓 Documentação Oficial da API Payberry (v1.0)

Documentação técnica da API da **Payberry** para integração de pagamentos (PIX In, Cartão de Crédito, PIX Out e Webhooks).

---

## 📌 Visão Geral & Autenticação

A API do Payberry segue o padrão **REST** com respostas no formato **JSON**.

* **Base URL**: `https://api.payberry.com.br`
* **Formato de Envio/Resposta**: `application/json`

### Autenticação
A autenticação é feita via **Basic Access Authentication** enviando o cabeçalho `Authorization` nas requisições HTTP:

```http
Authorization: Basic {TOKEN_BASE64}
```

O token Base64 pode ser gerado codificando `{PUBLIC_KEY}:{SECRET_KEY}`:
`Base64(pk_live_...:sk_live_...)`

As chaves podem ser encontradas no painel Payberry em: **Dashboard -> Credenciais de API**.

---

## 🏬 Dados da Empresa

### `GET /v1/company`
Retorna as informações cadastrais, localização e o status da empresa associada às credenciais da API.

#### Exemplo de Resposta (200 OK):
```json
{
  "id": "e6fb6532522e4f13ac0ce8a11fbf0a24",
  "fantasy_name": "PAY BERRY Ltda",
  "postal_code": "01311-200",
  "address": "Avenida Paulista",
  "number": "1000",
  "cnpj": "12345678000199",
  "complement": "Conjunto 51",
  "city": "São Paulo",
  "state": "SP",
  "status": "ACTIVE",
  "created_at": "2025-11-12T18:15:10.891Z",
  "updated_at": "2025-11-12T21:05:36.910Z"
}
```

---

## 📥 Pix In & Cartão de Crédito (Recebimentos)

### 1. Criar Transação PIX

#### `POST /v1/payment-transaction/create`

Cria uma nova cobrança via PIX (Gera o QR Code e o código Copia e Cola).

#### Parâmetros do Payload:

| Campo | Tipo | Obrigatório | Descrição |
| :--- | :--- | :--- | :--- |
| `amount` | `number` | **Sim** | Valor total em centavos (Ex: `19900` = R$ 199,00) |
| `payment_method` | `string` | **Sim** | `pix` |
| `postback_url` | `string` | **Sim** | URL para receber notificações via Webhook |
| `customer.name` | `string` | **Sim** | Nome completo do cliente |
| `customer.email` | `string` | **Sim** | E-mail do cliente |
| `customer.phone` | `string` | **Sim** | Telefone com DDD |
| `customer.document.number` | `string` | **Sim** | CPF ou CNPJ (apenas números) |
| `customer.document.type` | `string` | **Sim** | `cpf` ou `cnpj` |
| `items.0.title` | `string` | **Sim** | Título/nome do produto |
| `items.0.unit_price` | `number` | **Sim** | Preço unitário em centavos |
| `items.0.quantity` | `int` | **Sim** | Quantidade |
| `items.0.tangible` | `boolean` | **Sim** | `true` se físico, `false` se digital |
| `pix.expires_in_days` | `int` | Não | Dias para expiração do Pix (padrão: `1` ou `3`) |
| `metadata` | `object` | **Sim** | Objeto JSON com dados extras |

#### Exemplo de Requisição (JSON):
```json
{
  "amount": 19900,
  "payment_method": "pix",
  "postback_url": "https://seu-site.com/api/webhook_payberry.php",
  "customer": {
    "name": "Roberto Souza",
    "email": "roberto@gmail.com",
    "phone": "11999999999",
    "document": {
      "number": "49567673071",
      "type": "cpf"
    }
  },
  "items": [
    {
      "title": "Smart TV 43 AOC Full HD",
      "unit_price": 19900,
      "quantity": 1,
      "tangible": true
    }
  ],
  "pix": {
    "expires_in_days": 1
  },
  "metadata": {}
}
```

#### Exemplo de Resposta (200 OK):
```json
{
  "data": [
    {
      "id": "0GQt3rjeido",
      "amount": 19900,
      "payment_method": "pix",
      "status": "PENDING",
      "postback_url": "https://seu-site.com/api/webhook_payberry.php",
      "created_from_url": "https://seu-site.com/checkout",
      "pix": [
        {
          "expiration_date": "2026-06-09T21:47:52.467Z",
          "qr_code": "00020126870014br.gov.bcb.pix2565pix.basspago.com.br/qr/v3/at/35ec026c...",
          "url": ""
        }
      ]
    }
  ]
}
```

---

### 2. Criar Transação de Cartão de Crédito

#### `POST /v1/payment-transaction/create`

#### Parâmetros Adicionais para Cartão:

| Campo | Tipo | Obrigatório | Descrição |
| :--- | :--- | :--- | :--- |
| `payment_method` | `string` | **Sim** | `credit_card` |
| `card.number` | `string` | **Sim** | Número do cartão |
| `card.holder_name` | `string` | **Sim** | Nome impresso no cartão |
| `card.expiration_month` | `int` | **Sim** | Mês de expiração (Ex: `6`) |
| `card.expiration_year` | `int` | **Sim** | Ano de expiração (Ex: `2028`) |
| `card.cvv` | `string` | **Sim** | Código de segurança CVV |
| `installments` | `int` | Não | Número de parcelas (Ex: `1`) |

#### Exemplo de Resposta (200 OK):
```json
{
  "data": [
    {
      "id": "0GQt3rjeido",
      "amount": 19900,
      "payment_method": "credit_card",
      "status": "PAID",
      "postback_url": "https://seu-site.com/api/webhook_payberry.php",
      "installments": 1,
      "card": [
        {
          "number": "530005******1501",
          "holder_name": "ROBERTO SOUZA",
          "expiration_month": 6,
          "expiration_year": 2028
        }
      ]
    }
  ]
}
```

---

### 3. Buscar Transação pelo ID

#### `GET /v1/payment-transaction/info/{id}`

Consulta o status e detalhes de uma transação específica.

#### Exemplo de Resposta (200 OK):
```json
{
  "id": "0GQt3rjeido",
  "amount": 19900,
  "status": "PAID",
  "paid_at": "2026-08-22T14:20:00Z",
  "metadata": {
    "product_name": "Smart TV 43 AOC"
  }
}
```

---

### 4. Estornar Transação

#### `POST /v1/payment-transaction/{id}/refund`

Realiza o estorno total ou parcial de uma transação.

#### Parâmetros do Payload:
* `amount` (*number, opcional*): Valor do estorno em centavos. Se omitido, estorna o valor total.

#### Exemplo de Resposta (200 OK):
```json
{
  "data": [
    {
      "id": "0GQt3rjeido",
      "amount": 19900,
      "refunded_amount": 19900,
      "payment_method": "credit_card",
      "status": "REFUNDED"
    }
  ]
}
```

---

## 📤 Pix Out & Carteira (Saques)

### 1. Consultar Saldo

#### `GET /v1/payment-transaction/balance`

Retorna o saldo disponível na carteira Payberry em centavos.

#### Exemplo de Resposta (200 OK):
```json
{
  "data": {
    "amount": {
      "value": 15723
    }
  }
}
```

---

### 2. Criar Transferência (Pix Out)

#### `POST /v1/transfers`

Realiza uma transferência Pix para uma chave Pix externa.

---

## 🔔 Formato dos Webhooks (Postback)

A Payberry enviará requisições `POST` com os payloads abaixo para a `postback_url` cadastrada.

### 1. Webhook de Transações (Pix In / Cartão)

#### Payload Recebido (JSON):
```json
{
  "Id": "a24207e615224923bf4a68265d519fc6",
  "CreatedAt": "05/11/2025 21:19:42",
  "UpdatedAt": "2025-11-05T21:19:42.3648396",
  "ExternalId": "27615041",
  "PaidAt": "2025-11-05T21:20:00",
  "Amount": 100,
  "Installments": 0,
  "PaymentMethod": "pix",
  "Status": "PAID",
  "PostbackUrl": "https://seu-site.com/api/webhook_payberry.php"
}
```

#### Tabela de Status de Transação:

| Status | Descrição |
| :--- | :--- |
| `PENDING` | Aguardando Pagamento |
| `PAID` | Pagamento Confirmado / Realizado |
| `REFUNDED` | Transação Estornada |
| `REFUSED` | Transação Recusada |
| `CHARGEBACK` | Contestação / Chargeback |
| `PRECHARGEBACK` | Pré-Chargeback |
| `EXPIRED` | Cobrança Expirada |
| `ERROR` | Erro na transação |

---

### 2. Webhook de Transferências (Pix Out)

#### Payload Recebido (JSON):
```json
{
  "Id": "2471f1c668de4900b0634bff114f0820",
  "CompanyId": "e6fb6532522e4f13ac0ce8a11fbf0a24",
  "PixKey": "chavepix@exemplo.com",
  "CreatedAt": "2025-12-02T18:02:58",
  "UpdatedAt": "2025-12-02T18:03:05",
  "Status": "PAID",
  "WalletTransactionType": "WITHDRAWAL",
  "PaidAt": "02/12/2025 21:03:05",
  "RequiredAmount": 1.00,
  "Fee": 0.00,
  "TotalAmount": 1.00,
  "Reason": "",
  "PostbackUrl": "https://seu-site.com/api/webhook_payberry.php"
}
```

#### Tabela de Status de Transferência:

| Status | Descrição |
| :--- | :--- |
| `PAID` | Transferência realizada com sucesso |
| `PROCESSING` | Em processamento |
| `REFUSED` | Transferência recusada |

---

## 💻 Exemplo de Requisição em PHP (cURL)

```php
<?php
$publicKey = "pk_live_SUA_CHAVE_PUBLICA";
$secretKey = "sk_live_SUA_CHAVE_SECRETA";
$authToken = base64_encode($publicKey . ":" . $secretKey);

$payload = [
    "amount" => 158007, // R$ 1.580,07 em centavos
    "payment_method" => "pix",
    "postback_url" => "https://seu-site.com/api/webhook_payberry.php",
    "customer" => [
        "name" => "João Silva",
        "email" => "joao@email.com",
        "phone" => "11999999999",
        "document" => [
            "number" => "12345678909",
            "type" => "cpf"
        ]
    ],
    "items" => [
        [
            "title" => "Smart TV 43 AOC",
            "unit_price" => 158007,
            "quantity" => 1,
            "tangible" => true
        ]
    ],
    "pix" => [
        "expires_in_days" => 1
    ],
    "metadata" => new stdClass()
];

$ch = curl_init("https://api.payberry.com.br/v1/payment-transaction/create");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic " . $authToken,
        "Content-Type: application/json",
        "Accept: application/json"
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
```
