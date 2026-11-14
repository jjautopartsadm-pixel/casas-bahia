<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$message = '';
$error = '';

// Identificar usuário logado
$adminUserSession = $_SESSION['admin_user'] ?? 'admin';
$adminId = $_SESSION['admin_id'] ?? null;

$user = null;
try {
    if ($adminId) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $adminId]);
        $user = $stmt->fetch();
    }
    if (!$user) {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :u OR email = :u OR name = :u LIMIT 1");
        $stmt->execute(['u' => $adminUserSession]);
        $user = $stmt->fetch();
    }
    if (!$user) {
        $stmt = $db->query("SELECT * FROM users ORDER BY id ASC LIMIT 1");
        $user = $stmt->fetch();
    }
} catch (Exception $e) {}

// Fallback se ainda não existir registro no banco: cria o registro
if (!$user) {
    try {
        $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (username, name, email, password) VALUES ('admin', 'Administrador', 'admin@casasbahia.com', '$defaultHash')");
        $newId = $db->lastInsertId();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$newId]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $user = [
            'id' => 1,
            'username' => 'admin',
            'name' => 'Administrador',
            'email' => 'admin@casasbahia.com',
            'password' => '',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}

// Processar formulários POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ALTERAR DADOS DO USUÁRIO (Username, Nome, E-mail)
    if ($action === 'update_profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $newName = trim($_POST['name'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');

        if (empty($newUsername)) {
            $error = 'O Nome de Usuário (Login) não pode ficar em branco.';
        } else {
            try {
                // Verificar se o username já está em uso por outro usuário
                $stmtCheck = $db->prepare("SELECT id FROM users WHERE (username = :u OR email = :e) AND id != :id LIMIT 1");
                $stmtCheck->execute([
                    'u' => $newUsername,
                    'e' => $newEmail ?: $newUsername,
                    'id' => $user['id'] ?? 1
                ]);
                $existing = $stmtCheck->fetch();

                if ($existing) {
                    $error = 'Este Nome de Usuário ou E-mail já está sendo utilizado por outro cadastro.';
                } else {
                    $stmtUp = $db->prepare("
                        UPDATE users SET 
                            username = :username, 
                            name = :name, 
                            email = :email 
                        WHERE id = :id
                    ");
                    $stmtUp->execute([
                        'username' => $newUsername,
                        'name' => $newName ?: 'Administrador',
                        'email' => $newEmail,
                        'id' => $user['id'] ?? 1
                    ]);

                    // Atualizar na sessão
                    $_SESSION['admin_user'] = $newUsername;
                    $_SESSION['admin_name'] = $newName ?: 'Administrador';

                    $user['username'] = $newUsername;
                    $user['name'] = $newName ?: 'Administrador';
                    $user['email'] = $newEmail;

                    $message = 'Dados do usuário atualizados com sucesso!';
                }
            } catch (Exception $e) {
                $error = 'Erro ao atualizar dados: ' . $e->getMessage();
            }
        }
    }

    // 2. ALTERAR SENHA DO USUÁRIO
    else if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $error = 'Por favor, informe a sua senha atual para autorizar a alteração.';
        } else if (empty($newPassword)) {
            $error = 'Por favor, informe a nova senha.';
        } else if (strlen($newPassword) < 4) {
            $error = 'A nova senha deve ter no mínimo 4 caracteres.';
        } else if ($newPassword !== $confirmPassword) {
            $error = 'A confirmação de senha não confere com a nova senha digitada.';
        } else {
            try {
                // Verificar senha atual no banco de dados
                $storedHash = $user['password'] ?? '';
                $isCurrentValid = false;

                if (!empty($storedHash)) {
                    if (password_verify($currentPassword, $storedHash) || $storedHash === $currentPassword) {
                        $isCurrentValid = true;
                    }
                }

                if (!$isCurrentValid) {
                    $error = 'A senha atual digitada está incorreta.';
                } else {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    $stmtUpPass = $db->prepare("UPDATE users SET password = :pass WHERE id = :id");
                    $stmtUpPass->execute([
                        'pass' => $newHash,
                        'id' => $user['id'] ?? 1
                    ]);

                    $user['password'] = $newHash;
                    $message = 'Senha alterada com sucesso! Todas as senhas antigas foram invalidadas.';
                }
            } catch (Exception $e) {
                $error = 'Erro ao alterar senha: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuário & Senha | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --primary-dark: #002266;
      --bg-gray: #f4f6f9;
      --sidebar-width: 240px;
      --success: #168019;
      --danger: #e30613;
      --card-border: #e2e8f0;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background-color: var(--bg-gray); color: #333333; display: flex; min-height: 100vh; }

    /* SIDEBAR (BARRA LATERAL) */
    .admin-sidebar { width: var(--sidebar-width); background: #001a4d; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: 4px 0 12px rgba(0,0,0,0.05); }
    .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .brand-logo-admin { font-size: 20px; font-weight: 900; color: #ffffff; letter-spacing: -0.5px; }
    .brand-logo-admin span { color: #e30613; }
    .sidebar-badge-tag { font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

    .sidebar-nav { padding: 20px 0; flex: 1; }
    .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
    .sidebar-nav li a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #cbd5e1; text-decoration: none; font-size: 15px; font-weight: 600; transition: all 0.2s ease; border-left: 4px solid transparent; }
    .sidebar-nav li a:hover, .sidebar-nav li a.active { background: rgba(255,255,255,0.08); color: #ffffff; border-left-color: #38bdf8; }

    .sidebar-user-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; }
    .user-info { display: flex; align-items: center; gap: 10px; }
    .user-avatar-circle { width: 34px; height: 34px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }
    .user-details { display: flex; flex-direction: column; }
    .user-name { font-size: 13px; font-weight: 700; color: #ffffff; }
    .user-role { font-size: 11px; color: #4ade80; }
    .btn-sidebar-logout { color: #94a3b8; display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; text-decoration: none; }

    /* MAIN CONTENT */
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: 1000px; }
    .dashboard-header { margin-bottom: 24px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 10px; }
    
    .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media (max-width: 860px) { .grid-2col { grid-template-columns: 1fr; } }

    .admin-card { background: #ffffff; border-radius: 12px; border: 1px solid var(--card-border); padding: 26px 28px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .card-title { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .form-group .help-text { font-size: 12px; color: #64748b; margin-top: 4px; line-height: 1.4; }
    
    .input-wrapper { position: relative; display: flex; align-items: center; }
    .form-control { width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #ffffff; transition: border-color 0.2s; }
    .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 51, 153, 0.1); }
    
    .btn-toggle-pass { position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: #64748b; font-size: 16px; padding: 4px; }
    .btn-toggle-pass:hover { color: #1e293b; }

    .btn-submit { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: center; transition: background-color 0.2s; }
    .btn-submit:hover { background: var(--primary-dark); }
    .btn-submit-pass { background: var(--success); }
    .btn-submit-pass:hover { background: #126815; }

    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }

    .profile-badge-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; }
    .big-avatar { width: 52px; height: 52px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 22px; font-weight: 900; display: flex; align-items: center; justify-content: center; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="admin-main-wrapper">

    <div class="dashboard-header">
      <h1 class="page-main-title">
        👤 Usuário & Senha do Painel
      </h1>
      <p style="color: #64748b; font-size: 14px; margin-top: 4px;">
        Gerencie as credenciais de acesso ao Painel Administrativo Casas Bahia.
      </p>
    </div>

    <!-- MENSAGENS -->
    <?php if (!empty($message)): ?>
      <div class="alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- CARD RESUMO DO USUÁRIO LOGADO -->
    <div class="profile-badge-card">
      <div class="big-avatar">
        <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
      </div>
      <div>
        <div style="font-size: 16px; font-weight: 800; color: #1e293b;">
          <?= htmlspecialchars($user['name'] ?? 'Administrador') ?> 
          <span style="font-size: 12px; font-weight: 600; background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 12px; margin-left: 6px;">Ativo</span>
        </div>
        <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
          Login Atual: <strong style="color: #003399; font-family: monospace;"><?= htmlspecialchars($user['username'] ?? 'admin') ?></strong> &bull; E-mail: <?= htmlspecialchars($user['email'] ?? 'Não informado') ?>
        </div>
      </div>
    </div>

    <div class="grid-2col">

      <!-- COLUNA 1: ALTERAR DADOS DO USUÁRIO -->
      <div class="admin-card">
        <h2 class="card-title">✏️ Alterar Login / Usuário</h2>

        <form method="POST" action="usuario.php">
          <input type="hidden" name="action" value="update_profile">

          <div class="form-group">
            <label for="username">Nome de Usuário (Login):</label>
            <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? 'admin') ?>" required>
            <div class="help-text">Nome utilizado para entrar no painel administrativo.</div>
          </div>

          <div class="form-group">
            <label for="name">Nome Completo de Exibição:</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? 'Administrador') ?>" required>
            <div class="help-text">Nome exibido no topo e rodapé do painel.</div>
          </div>

          <div class="form-group">
            <label for="email">E-mail de Contato:</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="admin@seudominio.com">
            <div class="help-text">E-mail associado à conta administradora.</div>
          </div>

          <button type="submit" class="btn-submit">💾 Salvar Dados do Usuário</button>
        </form>
      </div>

      <!-- COLUNA 2: ALTERAR SENHA -->
      <div class="admin-card">
        <h2 class="card-title">🔒 Alterar Senha de Acesso</h2>

        <form method="POST" action="usuario.php">
          <input type="hidden" name="action" value="update_password">

          <div class="form-group">
            <label for="current_password">Senha Atual:</label>
            <div class="input-wrapper">
              <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Digite sua senha atual">
              <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('current_password')">👁️</button>
            </div>
            <div class="help-text">Informe a senha atual para confirmar a alteração.</div>
          </div>

          <div class="form-group">
            <label for="new_password">Nova Senha:</label>
            <div class="input-wrapper">
              <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Digite a nova senha" required minlength="4">
              <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('new_password')">👁️</button>
            </div>
            <div class="help-text">Mínimo de 4 caracteres.</div>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirmar Nova Senha:</label>
            <div class="input-wrapper">
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repita a nova senha" required minlength="4">
              <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('confirm_password')">👁️</button>
            </div>
            <div class="help-text">Digite a mesma senha novamente para confirmação.</div>
          </div>

          <button type="submit" class="btn-submit btn-submit-pass">🔑 Atualizar Senha de Acesso</button>
        </form>
      </div>

    </div>

  </div>

  <script>
    function togglePasswordVisibility(inputId) {
      const input = document.getElementById(inputId);
      if (input.type === 'password') {
        input.type = 'text';
      } else {
        input.type = 'password';
      }
    }
  </script>

</body>
</html>
