# 🛒 Projeto E-Commerce (Casas Bahia Style) com Painel Admin & MySQL para Hostinger

Este projeto contém a estrutura completa de uma página de produto (PDP) responsiva estilo Casas Bahia, integrada a um **Painel de Controle Administrativo completo** e banco de dados MySQL pronta para publicação direta na **Hostinger**.

---

## 📁 Estrutura do Projeto

```text
quirky-hopper/
├── admin/                 # PAINEL ADMINISTRATIVO
│   ├── auth.php           # Middleware de autenticação de sessão
│   ├── login.php          # Tela de Login do Admin (admin / admin123)
│   ├── logout.php         # Script para encerrar sessão
│   ├── index.php          # Dashboard com lista de produtos e ações
│   ├── product_form.php   # Formulário para Adicionar e Editar Produtos
│   └── product_delete.php # Exclusão de produtos no MySQL
├── config/
│   └── database.php       # Conexão PDO segura com MySQL (Hostinger)
├── docs/
│   └── PAYBERRY_API.md    # 🍓 Documentação Oficial da API Payberry (Pix In, Cartão, Webhooks)
├── includes/
│   ├── payberry_gateway.php # Classe PHP auxiliar para a API Payberry
│   └── pixel_tracker.php
├── assets/
│   ├── css/
│   │   └── style.css      # Estilização Mobile-First fiel às telas Casas Bahia
│   └── js/
│       └── app.js         # Interatividade (Galeria, Garantia, CEP, Sticky Bar)
├── api/
│   ├── cep.php            # Endpoint proxy para consulta de frete (ViaCEP)
│   └── cart.php           # Manipulação do carrinho
├── database.sql           # Script SQL com tabelas de produtos e usuários admin
├── index.php              # Página principal dinâmica integrada ao banco
├── setup.php              # Utilitário de 1-Clique para popular o MySQL
├── .htaccess              # Otimização Apache/Hostinger, compressão e segurança
└── README.md              # Este guia completo
```

---

## 🔑 Acesso ao Painel Admin

- **URL do Painel**: `https://seu-dominio.com/admin/login.php`
- **Usuário Padrão**: `admin`
- **Senha Padrão**: `admin123`

### Recursos do Painel Admin:
1. **Adicionar Novos Produtos**: Cadastre título, marca, SKU, código do item, valores à vista no Pix, parcelado no cartão, descontos em % e notas/avaliações.
2. **Gerenciar Galeria de Fotos**: Adicione múltiplos links de imagens por linha. A primeira imagem é definida automaticamente como principal.
3. **Editar Produtos Existentes**: Altere títulos, preços, especificações ou imagens com atualização instantânea na loja.
4. **Excluir Produtos**: Remova produtos do catálogo com confirmação de segurança.
5. **Visualizar na Loja**: Atalho direto para ver a página pública do produto (`index.php?id=X`).

---

## 🚀 Passo a Passo de Deploy na Hostinger

### 1. Criar o Banco de Dados MySQL na Hostinger
1. Acesse o painel **hPanel** da Hostinger.
2. Vá em **Bancos de Dados** -> **Bancos de Dados MySQL**.
3. Crie um novo banco de dados (ex: `u123456789_casasbahia`) e defina usuário e senha.

---

### 2. Configurar a Conexão no Projeto
Edite o arquivo `config/database.php` com as credenciais do hPanel:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_casasbahia');
define('DB_USER', 'u123456789_user');
define('DB_PASS', 'SuaSenhaSegura123!');
```

---

### 3. Enviar os Arquivos para a Hostinger
Suba a pasta `quirky-hopper` (ou seu conteúdo) para a pasta `public_html` da Hostinger via **FTP (FileZilla)** ou **Gerenciador de Arquivos do hPanel**.

---

### 4. Popular o Banco de Dados (1-Clique)
Acesse no seu navegador: `https://seu-dominio.com/setup.php` e clique no botão **Instalar / Criar Tabelas MySQL**.

Pronto! Seu site e seu painel administrativo estarão 100% no ar!
