<?php
/**
 * Configuração e Conexão com o Banco de Dados MySQL / Hostinger
 */

// FORÇAR FUSO HORÁRIO OFICIAL DE BRASÍLIA BRASIL (UTC-3)
date_default_timezone_set('America/Sao_Paulo');

// Credenciais do Banco de Dados (Suporta MySQL e Supabase / PostgreSQL)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'test');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_DRIVER', getenv('DB_DRIVER') ?: ((DB_PORT == '5432' || DB_PORT == '6543' || strpos(DB_HOST, 'supabase') !== false || strpos(DB_HOST, 'neon') !== false || strpos(DB_HOST, 'postgres') !== false) ? 'pgsql' : 'mysql'));

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true,
            ];

            if (DB_DRIVER === 'pgsql') {
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                try { $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'"); } catch (Exception $eTz) {}
                ensureExtraTablesExist($pdo, 'pgsql');
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                // Suporte a SSL para bancos MySQL em nuvem (ex: TiDB Cloud, Aiven, PlanetScale, AWS RDS)
                if (getenv('DB_SSL') === 'true' || getenv('DB_SSL') === '1' || getenv('MYSQL_ATTR_SSL_CA')) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                try { $pdo->exec("SET time_zone = '-03:00'"); } catch (Exception $eTz) {}
                ensureExtraTablesExist($pdo, 'mysql');
            }
        } catch (PDOException $e) {
            // Em ambiente local sem MySQL ativo, usa fallback SQLite para testes
            $sqlitePath = __DIR__ . '/../database.sqlite';
            try {
                $pdo = new PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                ensureExtraTablesExist($pdo, 'sqlite');
            } catch (Exception $ex) {
                throw new Exception("Erro de Conexão com Banco de Dados: " . $e->getMessage());
            }
        }
    }
    
    return $pdo;
}

function ensureExtraTablesExist($pdo, $driver = 'mysql') {
    try {
        if ($driver === 'pgsql') {
            $pkType = 'SERIAL PRIMARY KEY';
        } elseif ($driver === 'sqlite') {
            $pkType = 'INTEGER PRIMARY KEY AUTOINCREMENT';
        } else {
            $pkType = 'INT AUTO_INCREMENT PRIMARY KEY';
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                id $pkType,
                sku VARCHAR(100) DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                brand VARCHAR(100) DEFAULT NULL,
                item_code VARCHAR(100) DEFAULT NULL,
                badge_1 VARCHAR(100) DEFAULT 'BRASIL',
                badge_2 VARCHAR(100) DEFAULT 'LANÇAMENTO★',
                discount_percentage INT DEFAULT 7,
                regular_price DECIMAL(10,2) DEFAULT 0.00,
                pix_price DECIMAL(10,2) DEFAULT 0.00,
                pix_discount_percentage INT DEFAULT 7,
                max_installments INT DEFAULT 10,
                installment_value DECIMAL(10,2) DEFAULT 0.00,
                carne_max_installments INT DEFAULT 24,
                rating DECIMAL(3,1) DEFAULT 4.9,
                reviews_count INT DEFAULT 240,
                questions_count INT DEFAULT 14,
                description TEXT DEFAULT NULL,
                variation_name VARCHAR(100) DEFAULT NULL,
                variation_options TEXT DEFAULT NULL,
                variation_default VARCHAR(100) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `sku` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `item_code` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `badge_1` VARCHAR(100) DEFAULT 'BRASIL'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `badge_2` VARCHAR(100) DEFAULT 'LANÇAMENTO★'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `discount_percentage` INT DEFAULT 7"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `regular_price` DECIMAL(10,2) DEFAULT 0.00"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `pix_price` DECIMAL(10,2) DEFAULT 0.00"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `pix_discount_percentage` INT DEFAULT 7"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `max_installments` INT DEFAULT 10"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `installment_value` DECIMAL(10,2) DEFAULT 0.00"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `carne_max_installments` INT DEFAULT 24"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `rating` DECIMAL(3,1) DEFAULT 4.9"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `reviews_count` INT DEFAULT 240"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `questions_count` INT DEFAULT 14"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `description` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `variation_name` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `variation_options` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `variation_default` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `products` ADD COLUMN `category` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id $pkType,
                name VARCHAR(255),
                cpf VARCHAR(50),
                email VARCHAR(255),
                phone VARCHAR(50),
                cep VARCHAR(20),
                street VARCHAR(255),
                number VARCHAR(50),
                complement VARCHAR(100),
                neighborhood VARCHAR(100),
                city VARCHAR(100),
                state VARCHAR(50),
                password VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE `clients` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id $pkType,
                product_id INT,
                product_title VARCHAR(255),
                product_price DECIMAL(10,2),
                client_name VARCHAR(255),
                client_cpf VARCHAR(50),
                client_phone VARCHAR(50),
                client_email VARCHAR(255),
                shipping_address TEXT,
                city_state VARCHAR(100),
                payment_method VARCHAR(50),
                payment_status VARCHAR(50) DEFAULT 'Aguardando Pagamento',
                pix_code TEXT,
                transaction_id VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE `orders` AUTO_INCREMENT = 522837993"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `orders` ADD COLUMN `transaction_id` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gateway_settings (
                id INT PRIMARY KEY,
                gateway_name VARCHAR(50) DEFAULT 'Payberry Gateway',
                active_gateway VARCHAR(50) DEFAULT 'payberry',
                api_key TEXT,
                secret_key TEXT,
                payberry_public_key TEXT,
                payberry_secret_key TEXT,
                velana_api_key TEXT,
                velana_secret_key TEXT,
                pix_key VARCHAR(255),
                active_status INT DEFAULT 1
            );
        ");

        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN active_gateway VARCHAR(50) DEFAULT 'payberry'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN payberry_public_key TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN payberry_secret_key TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN velana_api_key TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN velana_secret_key TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN manual_pix_type VARCHAR(50) DEFAULT 'cpf'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN manual_pix_key VARCHAR(255) DEFAULT ''"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN manual_pix_name VARCHAR(100) DEFAULT 'CASAS BAHIA'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN manual_pix_city VARCHAR(100) DEFAULT 'SAO PAULO'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN sigilopay_api_key TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN sigilopay_secret_key TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN unipay_public_key TEXT"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE gateway_settings ADD COLUMN unipay_secret_key TEXT"); } catch (Exception $e) {}

        // Inserir credenciais iniciais para Payberry, Velana, SigiloPay e UniPay SOMENTE se a linha id=1 ainda não existir
        $insertIgnore = ($driver === 'sqlite') ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
        $pdo->exec("
            $insertIgnore INTO gateway_settings (
                id, gateway_name, active_gateway, api_key, secret_key, 
                payberry_public_key, payberry_secret_key, 
                velana_api_key, velana_secret_key, 
                sigilopay_api_key, sigilopay_secret_key, 
                unipay_public_key, unipay_secret_key,
                pix_key, active_status
            )
            VALUES (
                1, 
                'UniPay BR Gateway', 
                'unipay', 
                '', 
                '', 
                '', 
                '', 
                '', 
                '', 
                'jonasmgtd2_1qgte20cj4igtcb8',
                'mx68p6qujgsetcgfe2z2ui22zqv1c67k6xi5sh4nodkh6njcfgd12as149s5k6xs',
                '',
                '',
                '', 
                1
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS site_activity (
                id $pkType,
                session_id VARCHAR(100),
                page_name VARCHAR(100),
                is_checkout INT DEFAULT 0,
                last_active DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pixel_settings (
                id INT PRIMARY KEY,
                pixel_id VARCHAR(100),
                access_token TEXT,
                test_event_code VARCHAR(100) DEFAULT NULL,
                active_status INT DEFAULT 1
            );
        ");

        try { $pdo->exec("ALTER TABLE pixel_settings ADD COLUMN test_event_code VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS product_images (
                id $pkType,
                product_id INT NOT NULL,
                image_url TEXT NOT NULL,
                is_main INT DEFAULT 0,
                sort_order INT DEFAULT 1
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS specifications (
                id $pkType,
                product_id INT NOT NULL,
                spec_name VARCHAR(255) NOT NULL,
                spec_value TEXT NOT NULL
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS warranties (
                id $pkType,
                product_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                subtitle VARCHAR(255) DEFAULT NULL,
                monthly_price DECIMAL(10,2) DEFAULT 0.00,
                is_default INT DEFAULT 0
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id $pkType,
                username VARCHAR(100) DEFAULT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(150) DEFAULT NULL"); } catch (Exception $e) {}

        try {
            $userCheck = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
            if ($userCheck == 0) {
                $hash = password_hash('admin123', PASSWORD_DEFAULT);
                $pdo->exec("INSERT INTO `users` (`username`, `name`, `email`, `password`) VALUES ('admin', 'Administrador', 'admin@casasbahia.com', '$hash')");
            }
        } catch (Exception $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS product_reviews (
                id $pkType,
                product_id INT NOT NULL,
                author VARCHAR(100) DEFAULT NULL,
                reviewer_name VARCHAR(100) DEFAULT NULL,
                rating INT DEFAULT 5,
                rating_stars INT DEFAULT 5,
                review_date VARCHAR(100) DEFAULT NULL,
                content TEXT DEFAULT NULL,
                review_text TEXT DEFAULT NULL,
                photo_url TEXT DEFAULT NULL,
                gender VARCHAR(20) DEFAULT 'ambos',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `author` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `reviewer_name` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `rating` INT DEFAULT 5"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `rating_stars` INT DEFAULT 5"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `review_date` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `content` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `review_text` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `photo_url` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `product_reviews` ADD COLUMN `gender` VARCHAR(20) DEFAULT 'ambos'"); } catch (Exception $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS review_images (
                id $pkType,
                review_id INT NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                is_video INT DEFAULT 0
            );
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS home_banners (
                id $pkType,
                image_url TEXT NOT NULL,
                link_url VARCHAR(500) DEFAULT '#',
                sort_order INT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS home_categories (
                id $pkType,
                slug VARCHAR(50) UNIQUE,
                title VARCHAR(100) NOT NULL,
                image_url TEXT NOT NULL,
                sort_order INT DEFAULT 1
            );
        ");

        try {
            $catCount = $pdo->query("SELECT COUNT(*) FROM home_categories")->fetchColumn();
            if ($catCount == 0) {
                $pdo->exec("INSERT INTO home_categories (slug, title, image_url, sort_order) VALUES
                    ('invasao', 'USE: DESCONTO', '', 1),
                    ('tvs', 'TV\'s', 'assets/images/cat_tvs.jpg', 2),
                    ('iphone', 'iPhone', 'assets/images/cat_iphone.jpg', 3),
                    ('smartphones', 'Smartphones', 'assets/images/cat_smartphones.jpg', 4),
                    ('eletros', 'Eletros', 'assets/images/cat_eletros.jpg', 5)
                ");
            }
        } catch (Exception $e) {}

        // Tabela de Configurações de Bloqueio de Domínio Seco
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS access_control_settings (
                id INT PRIMARY KEY,
                is_enabled INT DEFAULT 1,
                block_action VARCHAR(50) DEFAULT 'redirect',
                redirect_url VARCHAR(500) DEFAULT 'https://www.casasbahia.com.br',
                param_name VARCHAR(50) DEFAULT 'src',
                master_token VARCHAR(100) DEFAULT 'promo',
                session_duration_hours INT DEFAULT 24
            );
        ");

        try { $pdo->exec("ALTER TABLE access_control_settings ADD COLUMN is_enabled INT DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_control_settings ADD COLUMN block_action VARCHAR(50) DEFAULT 'redirect'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_control_settings ADD COLUMN redirect_url VARCHAR(500) DEFAULT 'https://www.casasbahia.com.br'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_control_settings ADD COLUMN param_name VARCHAR(50) DEFAULT 'src'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_control_settings ADD COLUMN master_token VARCHAR(100) DEFAULT 'promo'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_control_settings ADD COLUMN session_duration_hours INT DEFAULT 24"); } catch (Exception $e) {}

        $pdo->exec("
            $insertIgnore INTO access_control_settings (
                id, is_enabled, block_action, redirect_url, param_name, master_token, session_duration_hours
            ) VALUES (
                1, 1, 'redirect', 'https://www.casasbahia.com.br', 'src', 'promo', 24
            );
        ");

        // Tabela de Links de Campanha Gerados
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS access_links (
                id $pkType,
                name VARCHAR(255) NOT NULL,
                target_type VARCHAR(50) DEFAULT 'home',
                target_id INT DEFAULT NULL,
                token VARCHAR(100) NOT NULL,
                clicks_count INT DEFAULT 0,
                is_active INT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE access_links ADD COLUMN target_type VARCHAR(50) DEFAULT 'home'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_links ADD COLUMN target_id INT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_links ADD COLUMN clicks_count INT DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE access_links ADD COLUMN is_active INT DEFAULT 1"); } catch (Exception $e) {}

        // Tabela de Configurações Z-API (WhatsApp)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS zapi_settings (
                id INT PRIMARY KEY,
                instance_id VARCHAR(100) DEFAULT '',
                instance_token VARCHAR(150) DEFAULT '',
                client_token VARCHAR(150) DEFAULT '',
                is_active INT DEFAULT 1,
                send_image INT DEFAULT 1,
                message_template TEXT DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE zapi_settings ADD COLUMN instance_id VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_settings ADD COLUMN instance_token VARCHAR(150) DEFAULT ''"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_settings ADD COLUMN client_token VARCHAR(150) DEFAULT ''"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_settings ADD COLUMN is_active INT DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_settings ADD COLUMN send_image INT DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_settings ADD COLUMN message_template TEXT DEFAULT NULL"); } catch (Exception $e) {}

        $defaultTemplate = "Olá, *{nome}*! 👋\nRecebemos o seu pedido na *Casas Bahia*!\n\n📦 *Pedido:* #{pedido_id}\n🛒 *Produto:* {produto}\n💰 *Valor:* {valor}\n\n🔑 *PIX COPIA E COLA:*\n{pix_copia_cola}\n\n📱 Ou pague escaneando o QR Code que enviamos acima.\n\nAgradecemos pela preferência! Caso precise de ajuda, estamos à disposição.";

        try {
            $stmtZapiCheck = $pdo->query("SELECT COUNT(*) FROM zapi_settings WHERE id = 1")->fetchColumn();
            if ($stmtZapiCheck == 0) {
                $stmtInit = $pdo->prepare("
                    INSERT INTO zapi_settings (id, instance_id, instance_token, client_token, is_active, send_image, message_template)
                    VALUES (1, '', '', '', 1, 1, :template)
                ");
                $stmtInit->execute(['template' => $defaultTemplate]);
            }
        } catch (Exception $eZInit) {}

        // Tabela de Logs de Envios WhatsApp (Z-API)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS zapi_logs (
                id $pkType,
                order_id VARCHAR(50) DEFAULT NULL,
                client_name VARCHAR(255) DEFAULT NULL,
                phone VARCHAR(50) NOT NULL,
                message_type VARCHAR(50) DEFAULT 'pix_created',
                status VARCHAR(50) DEFAULT 'pending',
                response_payload TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        try { $pdo->exec("ALTER TABLE zapi_logs ADD COLUMN order_id VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_logs ADD COLUMN client_name VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_logs ADD COLUMN phone VARCHAR(50) NOT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_logs ADD COLUMN message_type VARCHAR(50) DEFAULT 'pix_created'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_logs ADD COLUMN status VARCHAR(50) DEFAULT 'pending'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE zapi_logs ADD COLUMN response_payload TEXT DEFAULT NULL"); } catch (Exception $e) {}
    } catch (Exception $e) {}
}
