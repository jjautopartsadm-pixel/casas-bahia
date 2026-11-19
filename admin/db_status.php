<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = getDBConnection();
    $activeDriver = $GLOBALS['DB_ACTIVE_DRIVER'] ?? $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $activeHost = $GLOBALS['DB_CONNECTED_HOST'] ?? DB_HOST;
    $lastError = $GLOBALS['DB_LAST_REMOTE_ERROR'] ?? null;
    
    // Contagem de produtos
    $prodCount = 0;
    try {
        $prodCount = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    } catch (Exception $eP) {
        $prodCount = 'Erro: ' . $eP->getMessage();
    }
} catch (Exception $e) {
    $activeDriver = 'Falha total';
    $activeHost = 'N/A';
    $lastError = $e->getMessage();
    $prodCount = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Conexão - Casas Bahia</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px 20px; }
        .card { max-width: 650px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .badge { padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 13px; text-transform: uppercase; }
        .badge-success { background: #15803d; color: #fff; }
        .badge-warning { background: #b45309; color: #fff; }
        .badge-danger { background: #b91c1c; color: #fff; }
        .row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #334155; font-size: 14px; }
        .row:last-child { border-bottom: none; }
        .label { color: #94a3b8; }
        .val { font-weight: 600; font-family: monospace; }
        .alert { margin-top: 20px; padding: 15px; border-radius: 8px; font-size: 13px; line-height: 1.5; }
        .alert-warning { background: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #fbbf24; }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #4ade80; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 18px; background: #0086ff; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <h1>
        Diagnóstico de Banco de Dados
        <?php if ($activeDriver === 'pgsql'): ?>
            <span class="badge badge-success">Supabase PostgreSQL Ativo</span>
        <?php elseif ($activeDriver === 'mysql'): ?>
            <span class="badge badge-success">MySQL Ativo</span>
        <?php else: ?>
            <span class="badge badge-warning">SQLite Provisório (/tmp)</span>
        <?php endif; ?>
    </h1>

    <div class="row">
        <span class="label">Driver em Execução:</span>
        <span class="val"><?= htmlspecialchars($activeDriver) ?></span>
    </div>
    <div class="row">
        <span class="label">Host Configurado:</span>
        <span class="val"><?= htmlspecialchars(DB_HOST) ?></span>
    </div>
    <div class="row">
        <span class="label">Porta:</span>
        <span class="val"><?= htmlspecialchars(DB_PORT) ?></span>
    </div>
    <div class="row">
        <span class="label">Banco (DB_NAME):</span>
        <span class="val"><?= htmlspecialchars(DB_NAME) ?></span>
    </div>
    <div class="row">
        <span class="label">Total de Produtos Gravados:</span>
        <span class="val"><?= htmlspecialchars($prodCount) ?></span>
    </div>

    <?php if ($activeDriver === 'sqlite'): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Atenção:</strong> A Vercel está operando no banco SQLite provisório porque não conseguiu conectar ao PostgreSQL remoto.<br>
            <?php if (!empty($lastError)): ?>
                <br><strong>Motivo do erro:</strong><br>
                <code><?= htmlspecialchars($lastError) ?></code>
            <?php endif; ?>
            <br><br>
            <strong>Como resolver:</strong> Verifique a variável <code>DATABASE_URL</code> na Vercel. Certifique-se de usar a URL do <strong>Connection Pooler</strong> do Supabase (porta 6543 ou 5432).
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>✅ Perfeito!</strong> Sua aplicação está conectada diretamente ao banco de dados definitivo (<?= htmlspecialchars($activeDriver) ?>). Todos os produtos cadastrados e importados estão salvos permanentemente.
        </div>
    <?php endif; ?>

    <a href="products.php" class="btn">Voltar para Produtos</a>
</div>
</body>
</html>
