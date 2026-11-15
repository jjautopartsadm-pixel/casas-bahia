<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_handler.php';

startSecureSession();

// Chave secreta para assinatura criptográfica de autenticação Stateless/Serverless
define('AUTH_SECRET_KEY', 'cb_adm_stateless_sec_token_987123_vercel');

/**
 * Cria um cookie assinado criptograficamente com HMAC-SHA256
 * Garante que a sessão persista mesmo entre múltiplos contêineres efêmeros da Vercel
 */
function setAdminAuthCookie($userId, $username, $name) {
    $expires = time() + (86400 * 7); // Válido por 7 dias
    $payload = json_encode([
        'id'   => $userId,
        'user' => $username,
        'name' => $name,
        'exp'  => $expires
    ]);
    
    $signature = hash_hmac('sha256', $payload, AUTH_SECRET_KEY);
    $token = base64_encode($payload . '::' . $signature);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    @setcookie('cb_admin_session', $token, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Limpa o cookie de autenticação ao fazer logout
 */
function clearAdminAuthCookie() {
    @setcookie('cb_admin_session', '', [
        'expires'  => time() - 86400,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    if (isset($_COOKIE['cb_admin_session'])) {
        unset($_COOKIE['cb_admin_session']);
    }
}

/**
 * Valida a assinatura do cookie criptográfico e restabelece a sessão do Admin
 */
function checkAdminAuthToken() {
    if (!empty($_SESSION['admin_logged_in'])) {
        return true;
    }

    if (empty($_COOKIE['cb_admin_session'])) {
        return false;
    }

    $raw = $_COOKIE['cb_admin_session'];
    $decoded = base64_decode($raw);
    if (!$decoded || strpos($decoded, '::') === false) {
        return false;
    }

    list($payload, $signature) = explode('::', $decoded, 2);
    $expectedSignature = hash_hmac('sha256', $payload, AUTH_SECRET_KEY);

    if (!hash_equals($expectedSignature, $signature)) {
        return false;
    }

    $data = json_decode($payload, true);
    if (!$data || empty($data['exp']) || $data['exp'] < time()) {
        return false;
    }

    // Token autêntico e válido: restaura a sessão
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id']        = $data['id'];
    $_SESSION['admin_user']      = $data['user'];
    $_SESSION['admin_name']      = $data['name'];
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn() {
    if (!empty($_SESSION['admin_logged_in'])) {
        return true;
    }
    return checkAdminAuthToken();
}
