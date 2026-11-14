<?php
/**
 * Instalador Automático do Banco de Dados para Hostinger
 * Acesse seudominio.com/setup.php para popular o banco de dados.
 */

require_once __DIR__ . '/config/database.php';

$message = '';
$status = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDBConnection();
        $sqlContent = file_get_contents(__DIR__ . '/database.sql');
        
        if (!$sqlContent) {
            throw new Exception("Arquivo database.sql não foi encontrado.");
        }

        // Executar SQL
        $db->exec($sqlContent);
        $message = "✅ Banco de dados configurado e populado com sucesso no servidor Hostinger!";
        $status = "success";
    } catch (Exception $e) {
        $message = "❌ Erro ao configurar banco de dados: " . $e->getMessage();
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Instalador Hostinger | E-Commerce Database</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .card { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 500px; width: 100%; }
    h1 { font-size: 20px; color: #003399; margin-top: 0; }
    p { font-size: 14px; color: #555; line-height: 1.5; }
    .btn { background: #168019; color: white; border: none; padding: 14px 24px; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; width: 100%; margin-top: 16px; }
    .alert { padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 16px; }
    .alert.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
  </style>
</head>
<body>
  <div class="card">
    <h1>🚀 Setup do Banco de Dados (Hostinger)</h1>
    <p>Este utilitário irá criar as tabelas e popular o banco de dados MySQL com as informações da Smart TV AOC 43" estilo Casas Bahia.</p>
    
    <?php if ($message): ?>
      <div class="alert <?= $status ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <button type="submit" class="btn">Instalar / Criar Tabelas MySQL</button>
    </form>

    <p style="margin-top: 20px; font-size: 12px; color: #888;">
      💡 Dica: Após concluir o setup, remova ou renomeie o arquivo <code>setup.php</code> por segurança.
    </p>
  </div>
</body>
</html>
