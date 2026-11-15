<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Por favor, informe usuário e senha.';
    } else {
        try {
            $db = getDBConnection();
            
            // Garantir que a coluna username exista caso a tabela seja legada
            try { $db->exec("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) DEFAULT NULL"); } catch (Exception $ex) {}
            try { $db->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(150) DEFAULT NULL"); } catch (Exception $ex) {}

            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR name = ? LIMIT 1");
            $stmt->execute([$username, $username, $username]);
            $user = $stmt->fetch();

            if ($user) {
                $passwordMatch = false;
                if (!empty($user['password'])) {
                    if (password_verify($password, $user['password'])) {
                        $passwordMatch = true;
                    } else if ($user['password'] === $password) {
                        // Se senha antiga em texto simples, atualiza para hash seguro
                        $passwordMatch = true;
                        try {
                            $rehashed = password_hash($password, PASSWORD_DEFAULT);
                            $upStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $upStmt->execute([$rehashed, $user['id']]);
                        } catch (Exception $eUp) {}
                    }
                }

                if ($passwordMatch) {
                    $adminId = $user['id'];
                    $adminUser = !empty($user['username']) ? $user['username'] : (!empty($user['email']) ? $user['email'] : $username);
                    $adminName = !empty($user['name']) ? $user['name'] : 'Administrador';

                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $adminId;
                    $_SESSION['admin_user'] = $adminUser;
                    $_SESSION['admin_name'] = $adminName;

                    // Cria o cookie de autenticação assinado criptograficamente (Serverless persistente)
                    if (function_exists('setAdminAuthCookie')) {
                        setAdminAuthCookie($adminId, $adminUser, $adminName);
                    }

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Usuário ou senha incorretos.';
                }
            } else {
                $error = 'Usuário ou senha incorretos.';
            }
        } catch (Exception $e) {
            $error = 'Erro ao processar login: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Painel de Controle E-Commerce</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #002266; color: #333; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 16px; }
    .login-card { background: #ffffff; border-radius: 12px; padding: 32px; width: 100%; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
    .brand-title { font-size: 24px; font-weight: 900; color: #003399; text-align: center; margin-bottom: 8px; }
    .brand-title span { color: #e30613; }
    .sub-title { font-size: 14px; color: #666; text-align: center; margin-bottom: 24px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: 13px; font-weight: 700; color: #444; margin-bottom: 6px; }
    input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; box-sizing: border-box; }
    input:focus { border-color: #003399; outline: none; box-shadow: 0 0 0 2px rgba(0,51,153,0.15); }
    .btn-submit { width: 100%; background: #168019; color: white; border: none; padding: 14px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; margin-top: 8px; }
    .btn-submit:hover { background: #126815; }
    .alert-danger { background: #ffebee; color: #c62828; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 16px; border: 1px solid #ffcdd2; }
    .input-wrapper { position: relative; display: flex; align-items: center; }
    .btn-toggle-pass { position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #666; font-size: 16px; padding: 4px; }
    .btn-toggle-pass:hover { color: #000; }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand-title">CASAS<span>BAHIA</span> ADMIN</div>
    <div class="sub-title">Gestão de Produtos & Preços</div>

    <?php if ($error): ?>
      <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="username">Usuário ou E-mail</label>
        <input type="text" id="username" name="username" placeholder="Digite seu usuário ou e-mail" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">Senha</label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
          <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('password')">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn-submit">Entrar no Painel</button>
    </form>
  </div>

  <script>
    function togglePasswordVisibility(inputId) {
      const input = document.getElementById(inputId);
      input.type = input.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>
