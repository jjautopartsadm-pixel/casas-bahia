<?php
/**
 * includes/session_handler.php
 * Gerenciador de Sessões compatível com Vercel Serverless (Suporta MySQL e Supabase/PostgreSQL)
 */

function startSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_only_cookies', '1');
    if ($isHttps) {
        @ini_set('session.cookie_secure', '1');
    }

    if (function_exists('getDBConnection')) {
        try {
            $db = getDBConnection();
            if ($db) {
                $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver === 'mysql' || $driver === 'pgsql') {
                    registerDBSessionHandler($db, $driver);
                }
            }
        } catch (Exception $e) {
            // Em caso de falha, segue com o session_start nativo
        }
    }

    @session_start();
}

function registerDBSessionHandler($db, $driver = 'mysql') {
    try {
        if ($driver === 'pgsql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS php_sessions (
                    id VARCHAR(128) NOT NULL PRIMARY KEY,
                    data TEXT NOT NULL,
                    last_activity INT NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_php_sessions_activity ON php_sessions (last_activity);
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `php_sessions` (
                    `id` VARCHAR(128) NOT NULL PRIMARY KEY,
                    `data` MEDIUMTEXT NOT NULL,
                    `last_activity` INT UNSIGNED NOT NULL,
                    INDEX (`last_activity`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }

        $handler = new class($db, $driver) implements SessionHandlerInterface {
            private $pdo;
            private $driver;

            public function __construct($pdo, $driver) {
                $this->pdo = $pdo;
                $this->driver = $driver;
            }

            public function open(string $path, string $name): bool {
                return true;
            }

            public function close(): bool {
                return true;
            }

            public function read(string $id): string|false {
                try {
                    $stmt = $this->pdo->prepare("SELECT data FROM php_sessions WHERE id = :id AND last_activity > :time LIMIT 1");
                    $lifetime = intval(ini_get('session.gc_maxlifetime')) ?: 86400;
                    $stmt->execute(['id' => $id, 'time' => time() - $lifetime]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $row ? (string)$row['data'] : '';
                } catch (Exception $e) {
                    return '';
                }
            }

            public function write(string $id, string $data): bool {
                try {
                    if ($this->driver === 'pgsql') {
                        $stmt = $this->pdo->prepare("
                            INSERT INTO php_sessions (id, data, last_activity) 
                            VALUES (:id, :data, :last_activity)
                            ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity
                        ");
                    } else {
                        $stmt = $this->pdo->prepare("
                            REPLACE INTO php_sessions (id, data, last_activity) 
                            VALUES (:id, :data, :last_activity)
                        ");
                    }
                    return $stmt->execute([
                        'id' => $id,
                        'data' => $data,
                        'last_activity' => time()
                    ]);
                } catch (Exception $e) {
                    return false;
                }
            }

            public function destroy(string $id): bool {
                try {
                    $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE id = :id");
                    return $stmt->execute(['id' => $id]);
                } catch (Exception $e) {
                    return false;
                }
            }

            public function gc(int $max_lifetime): int|false {
                try {
                    $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE last_activity < :expire");
                    $stmt->execute(['expire' => time() - $max_lifetime]);
                    return $stmt->rowCount();
                } catch (Exception $e) {
                    return 0;
                }
            }
        };

        session_set_save_handler($handler, true);
    } catch (Exception $e) {
        // Se falhar criar a tabela, mantém o padrão
    }
}
