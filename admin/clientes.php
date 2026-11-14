<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$clients = [];
$error = '';
$msg = $_GET['msg'] ?? '';

try {
    $stmt = $db->query("SELECT * FROM clients ORDER BY id DESC");
    $clients = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Erro ao buscar clientes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clientes | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --bg-gray: #f4f6f9;
      --sidebar-width: 240px;
      --success: #168019;
      --danger: #e30613;
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
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: calc(100vw - var(--sidebar-width)); }
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; }

    .section-card-table { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); overflow: hidden; }
    .table-header-bar { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
    .table-title { font-size: 18px; font-weight: 800; color: #0f172a; }

    .clients-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
    .clients-table th { background: #f8fafc; padding: 14px 18px; border-bottom: 2px solid #e2e8f0; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    .clients-table td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .clients-table tr:hover { background: #f8fafc; }

    .avatar-icon-cell { width: 36px; height: 36px; background: #eff6ff; color: var(--primary); font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .client-title-row { display: flex; align-items: center; gap: 12px; }
    .client-name-bold { font-weight: 700; color: #1e293b; }

    .sub-contact { font-size: 13px; color: #64748b; display: block; }

    /* BOTÃO DE LIXEIRA PARA EXCLUIR CLIENTE */
    .btn-delete-client { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; text-decoration: none; }
    .btn-delete-client:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25); }

    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .empty-clients-cell { padding: 36px; text-align: center; color: #64748b; font-weight: 600; font-size: 15px; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL DE CLIENTES -->
  <div class="admin-main-wrapper">
    
    <div class="dashboard-header">
      <h1 class="page-main-title">Gestão de Clientes</h1>
    </div>

    <?php if ($msg === 'cliente_excluido'): ?>
      <div class="alert-success">✓ Cliente removido permanentemente com sucesso!</div>
    <?php endif; ?>

    <div class="section-card-table">
      <div class="table-header-bar">
        <h2 class="table-title">Clientes Cadastrados</h2>
        <span style="font-size: 13px; color: #64748b; font-weight: 600;">Total: <?= count($clients) ?> clientes</span>
      </div>

      <table class="clients-table">
        <thead>
          <tr>
            <th>Cliente / Nome</th>
            <th>CPF / CNPJ</th>
            <th>Senha Registrada</th>
            <th>Contato</th>
            <th>Endereço Cadastrado</th>
            <th>Cidade / UF</th>
            <th>CEP</th>
            <th>Data de Registro</th>
            <th style="text-align: center;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($clients)): ?>
            <tr>
              <td colspan="9" class="empty-clients-cell">
                Nenhum cliente cadastrado no momento.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($clients as $c): ?>
              <tr>
                <td>
                  <div class="client-title-row">
                    <div class="avatar-icon-cell"><?= strtoupper(substr($c['name'] ?: 'C', 0, 1)) ?></div>
                    <span class="client-name-bold"><?= htmlspecialchars($c['name'] ?: 'Cliente') ?></span>
                  </div>
                </td>

                <td><strong style="color: #003399;"><?= htmlspecialchars($c['cpf']) ?></strong></td>

                <td>
                  <?php if (!empty($c['password'])): ?>
                    <span style="font-family: monospace; background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 13px; border: 1px solid #bae6fd;">
                      <?= htmlspecialchars($c['password']) ?>
                    </span>
                  <?php else: ?>
                    <span style="color: #94a3b8; font-size: 12px; font-style: italic;">Não informada</span>
                  <?php endif; ?>
                </td>

                <td>
                  <span class="sub-contact"><?= htmlspecialchars($c['email']) ?></span>
                  <span class="sub-contact"><?= htmlspecialchars($c['phone']) ?></span>
                </td>

                <td style="font-size: 13px; color: #475569;">
                  <?= htmlspecialchars($c['street']) ?>, <?= htmlspecialchars($c['number']) ?> <?= htmlspecialchars($c['neighborhood']) ? ' - ' . htmlspecialchars($c['neighborhood']) : '' ?>
                </td>

                <td><strong><?= htmlspecialchars($c['city']) ?> - <?= htmlspecialchars($c['state']) ?></strong></td>

                <td style="font-family: monospace; font-size: 13px; color: #64748b;"><?= htmlspecialchars($c['cep']) ?></td>

                <td style="font-size: 13px; color: #64748b; font-weight: 500;">
                  <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                </td>

                <!-- COLUNA DA LIXEIRA PARA EXCLUIR CLIENTE -->
                <td style="text-align: center;">
                  <a href="client_delete.php?id=<?= $c['id'] ?>" class="btn-delete-client" onclick="return confirm('Deseja realmente excluir este cliente <?= htmlspecialchars($c['name']) ?>?')" title="Excluir este cliente">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>
