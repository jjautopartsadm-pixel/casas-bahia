<?php
/**
 * Vercel Serverless Entrypoint & Front Controller
 * Redireciona e executa com segurança as rotas PHP do projeto na Vercel.
 */

// Silenciar warnings e avisos de versão (ex: Deprecated do PHP 8.4/8.5) para não quebrar o layout
@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// Diretório raiz do projeto
$rootDir = realpath(__DIR__ . '/..');

// 1. Obter e sanitizar o caminho da requisição
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$path = trim($parsedPath, '/');

// 2. Proteção contra Path Traversal
if (strpos($path, '..') !== false || strpos($path, "\0") !== false) {
    http_response_code(403);
    echo "403 Forbidden";
    exit;
}

// 3. Bloqueio de arquivos sensíveis ou não-executáveis diretamente
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if (in_array($extension, ['sql', 'sqlite', 'log', 'ini', 'env', 'md', 'json', 'lock', 'yml', 'yaml'])) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

// 4. Mapeamento de rotas
$targetFile = null;

if ($path === '' || $path === 'index.php') {
    $targetFile = $rootDir . DIRECTORY_SEPARATOR . 'index.php';
} elseif ($path === 'admin' || $path === 'admin/') {
    $targetFile = $rootDir . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'index.php';
} else {
    // Candidatos a testar
    $candidates = [
        $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path),
    ];
    if (empty($extension)) {
        $candidates[] = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path) . '.php';
        $candidates[] = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR . 'index.php';
    }

    foreach ($candidates as $candidate) {
        $realCandidate = realpath($candidate);
        // Validar que o arquivo existe, é arquivo comum e está dentro da raiz
        if ($realCandidate && is_file($realCandidate) && strpos($realCandidate, $rootDir) === 0) {
            // Evitar que o router chame a si mesmo
            if ($realCandidate === __FILE__) {
                continue;
            }
            $targetFile = $realCandidate;
            break;
        }
    }
}

// 5. Se o arquivo foi encontrado, executa no contexto correto
if ($targetFile && file_exists($targetFile)) {
    $relPath = '/' . ltrim(str_replace(['\\', $rootDir], ['/', ''], $targetFile), '/');
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['SCRIPT_NAME']     = $relPath;
    $_SERVER['PHP_SELF']        = $relPath;

    // Altera o diretório de trabalho para a pasta do arquivo alvo
    chdir(dirname($targetFile));

    require $targetFile;
    exit;
}

// 6. Arquivo não encontrado
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - Página não encontrada</h1><p>O arquivo ou rota solicitada não existe.</p></body></html>";
exit;
