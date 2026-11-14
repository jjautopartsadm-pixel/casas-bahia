<?php
/**
 * includes/domain_guard.php
 * Sistema de Bloqueio de Acesso por Domínio Seco e Validação de Links
 */

if (file_exists(__DIR__ . '/session_handler.php')) {
    require_once __DIR__ . '/session_handler.php';
    startSecureSession();
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAccessControlSettings($db = null) {
    if (!$db) {
        if (function_exists('getDBConnection')) {
            $db = getDBConnection();
        }
    }

    $default = [
        'is_enabled' => 1,
        'block_action' => 'redirect',
        'redirect_url' => 'https://www.casasbahia.com.br',
        'param_name' => 'src',
        'master_token' => 'promo',
        'session_duration_hours' => 24
    ];

    if (!$db) {
        return $default;
    }

    try {
        $stmt = $db->query("SELECT * FROM access_control_settings WHERE id = 1 LIMIT 1");
        $settings = $stmt->fetch();
        if ($settings) {
            return array_merge($default, $settings);
        }
    } catch (Exception $e) {}

    return $default;
}

function checkDomainAccess() {
    // 1. Ignorar chamadas do painel administrativo ou CLI
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($uri, '/admin') !== false || strpos($script, '/admin') !== false) {
        return true;
    }

    // 2. Conectar ao banco e obter configurações
    $db = function_exists('getDBConnection') ? getDBConnection() : null;
    $settings = getAccessControlSettings($db);

    // Se o bloqueio estiver desativado no painel admin, libera o acesso imediatamente
    if (intval($settings['is_enabled']) === 0) {
        return true;
    }

    $durationSeconds = max(3600, intval($settings['session_duration_hours']) * 3600);
    $authExpiry = $_SESSION['store_access_authorized_until'] ?? 0;

    // 3. Verificar se o visitante já possui sessão autorizada ativa
    if (!empty($_SESSION['store_access_authorized']) && $authExpiry > time()) {
        return true;
    }

    // 4. Verificar cookie persistente de autorização
    $expectedCookieHash = md5('cb_guard_' . ($settings['master_token'] ?? 'promo'));
    if (!empty($_COOKIE['cb_store_auth']) && $_COOKIE['cb_store_auth'] === $expectedCookieHash) {
        $_SESSION['store_access_authorized'] = true;
        $_SESSION['store_access_authorized_until'] = time() + $durationSeconds;
        return true;
    }

    // 5. Verificar tokens enviados via URL ($_GET)
    $paramName = !empty($settings['param_name']) ? trim($settings['param_name']) : 'src';
    $masterToken = !empty($settings['master_token']) ? trim($settings['master_token']) : 'promo';

    $candidateTokens = [];
    if (isset($_GET[$paramName])) {
        $candidateTokens[] = trim($_GET[$paramName]);
    }
    // Também checa parâmetros comuns caso o admin use ?src, ?ref, ?token, ?k
    $fallbackParams = ['src', 'ref', 'token', 'k', 'camp', 'campanha'];
    foreach ($fallbackParams as $fp) {
        if (isset($_GET[$fp])) {
            $candidateTokens[] = trim($_GET[$fp]);
        }
    }
    $candidateTokens = array_unique(array_filter($candidateTokens));

    $isAuthorized = false;

    // 5.1. Comparar com o Token Mestre Global
    foreach ($candidateTokens as $tok) {
        if (strcasecmp($tok, $masterToken) === 0) {
            $isAuthorized = true;
            break;
        }
    }

    // 5.2. Comparar com a tabela de Links Gerados (access_links)
    if (!$isAuthorized && $db && !empty($candidateTokens)) {
        try {
            $inPlaceholders = implode(',', array_fill(0, count($candidateTokens), '?'));
            $stmt = $db->prepare("SELECT * FROM access_links WHERE token IN ($inPlaceholders) AND is_active = 1 LIMIT 1");
            $stmt->execute(array_values($candidateTokens));
            $matchedLink = $stmt->fetch();

            if ($matchedLink) {
                $isAuthorized = true;
                // Incrementar contador de cliques do link
                try {
                    $upStmt = $db->prepare("UPDATE access_links SET clicks_count = clicks_count + 1 WHERE id = :id");
                    $upStmt->execute(['id' => $matchedLink['id']]);
                } catch (Exception $eUp) {}
            }
        } catch (Exception $eLinks) {}
    }

    // Se o token for válido, autoriza a sessão e grava cookie
    if ($isAuthorized) {
        $_SESSION['store_access_authorized'] = true;
        $_SESSION['store_access_authorized_until'] = time() + $durationSeconds;
        @setcookie('cb_store_auth', $expectedCookieHash, time() + $durationSeconds, '/');
        return true;
    }

    // 6. BLOQUEIO DE DOMÍNIO SECO - O visitante tentou acessar diretamente sem link gerado
    executeBlockAction($settings);
}

function executeBlockAction($settings) {
    $action = $settings['block_action'] ?? 'redirect';
    $redirectUrl = !empty($settings['redirect_url']) ? $settings['redirect_url'] : 'https://www.casasbahia.com.br';

    switch ($action) {
        case '404':
            http_response_code(404);
            renderBlocked404();
            exit;

        case 'blank':
            http_response_code(200);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
            exit;

        case 'maintenance':
            http_response_code(503);
            renderBlockedMaintenance();
            exit;

        case 'redirect':
        default:
            header("Location: " . $redirectUrl, true, 302);
            exit;
    }
}

function renderBlocked404() {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>404 - Página Não Encontrada</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; text-align: center; }
        .box { background: #ffffff; padding: 48px 32px; border-radius: 16px; border: 1px solid #e2e8f0; max-width: 480px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .code { font-size: 72px; font-weight: 900; color: #003399; line-height: 1; margin-bottom: 12px; }
        h1 { font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 12px; }
        p { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; background: #003399; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; }
      </style>
    </head>
    <body>
      <div class="box">
        <div class="code">404</div>
        <h1>Página Não Encontrada</h1>
        <p>O endereço solicitado não está disponível ou a oferta expirou.</p>
        <a href="https://www.google.com" class="btn">Ir para o início</a>
      </div>
    </body>
    </html>
    <?php
}

function renderBlockedMaintenance() {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Loja em Atualização</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; text-align: center; }
        .box { background: #1e293b; padding: 48px 32px; border-radius: 16px; border: 1px solid #334155; max-width: 500px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 22px; font-weight: 800; color: #ffffff; margin-bottom: 12px; }
        p { font-size: 14px; color: #94a3b8; line-height: 1.6; }
      </style>
    </head>
    <body>
      <div class="box">
        <div class="icon">🛠️</div>
        <h1>Loja em Manutenção</h1>
        <p>Estamos realizando melhorias em nosso sistema para proporcionar uma melhor experiência. Por favor, volte em alguns instantes.</p>
      </div>
    </body>
    </html>
    <?php
}
