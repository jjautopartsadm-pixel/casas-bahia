# 🚀 Guia de Deploy na Vercel (PHP E-Commerce)

Este projeto foi configurado com suporte completo para rodar na **Vercel** usando Serverless Functions com o runtime PHP comunitário oficial (`vercel-php`).

---

## ⚠️ Pontos Críticos Importantes na Vercel

1. **Ambiente Serverless & Stateless (Sem disco persistente):**
   - Na Vercel, o sistema de arquivos é somente-leitura e efêmero.
   - **O SQLite local (`database.sqlite`) NÃO funciona na Vercel** para salvar pedidos, cadastros ou dados persistentes.
   - Você **precisa** conectar um banco de dados **MySQL em nuvem**.
   - As sessões de login do painel Admin foram automaticamente configuradas para serem salvas na tabela `php_sessions` do MySQL, garantindo que você nunca seja deslogado aleatoriamente entre requisições.

2. **Uploads de Imagens:**
   - Como o disco é somente-leitura, no Painel Admin use **links/URLs externas de imagens** para produtos e banners.

---

## 🗄️ 1. Configurar o Banco de Dados MySQL na Nuvem

Você pode usar qualquer banco MySQL remoto. Algumas opções gratuitas ou acessíveis:
- **TiDB Cloud (Serverless)**: Gratuito, rápido e 100% compatível com MySQL.
- **Aiven for MySQL**: Oferece tier gratuito.
- **Railway**: Provisiona MySQL com 1 clique.
- **Hostinger / cPanel**: Se já tiver hospedagem, basta liberar o IP remoto (ou `%`) e usar as credenciais.

### Importar o Banco de Dados:
No painel do seu MySQL (via phpMyAdmin, DBeaver, HeidiSQL ou console do banco), importe o arquivo **`database.sql`** (ou `test (3).sql`) que já está na raiz do projeto.

---

## ⚙️ 2. Variáveis de Ambiente na Vercel

No painel da Vercel (**Project Settings** > **Environment Variables**), adicione as seguintes variáveis:

| Variável | Descrição | Exemplo |
| :--- | :--- | :--- |
| `DB_HOST` | Host do MySQL | `gateway01.us-east-1.prod.aws.tidbcloud.com` ou seu IP |
| `DB_PORT` | Porta do MySQL | `3306` (ou `4000` no TiDB) |
| `DB_NAME` | Nome do Banco de Dados | `loja_bahia` |
| `DB_USER` | Usuário do MySQL | `root` ou `seu_usuario` |
| `DB_PASS` | Senha do MySQL | `sua_senha_secreta` |
| `DB_SSL` | Ativar SSL (recomendado p/ nuvem) | `true` |

---

## 🚀 3. Como Fazer o Deploy

### Opção A: Via GitHub (Mais Fácil e Recomendado)
1. Suba este projeto para um repositório no seu GitHub (`git push`).
2. Acesse [vercel.com](https://vercel.com) e clique em **Add New** > **Project**.
3. Importe o repositório.
4. Em **Environment Variables**, cadastre as 6 variáveis do banco MySQL listadas acima.
5. Clique em **Deploy**!

### Opção B: Via Vercel CLI no Terminal
Se tiver a CLI da Vercel instalada:
```bash
npm install -g vercel
vercel
```
Siga as instruções na tela e informe as variáveis de ambiente quando solicitado.

---

## 📁 Arquivos Criados para Suporte na Vercel

- **`vercel.json`**: Configura o runtime comunitário `vercel-php@0.9.0`, roteamento de assets estáticos direto pelo CDN e proteção de arquivos sensíveis.
- **`api/index.php`**: Front Controller / Router serverless que despacha todas as rotas (`/`, `/carrinho`, `/admin/login`, `/api/generate_pix.php`, etc.).
- **`includes/session_handler.php`**: Salva sessões de autenticação do Admin no MySQL, eliminando o problema de desconexão em contêineres Lambda efêmeros.
- **`.vercelignore`**: Exclui dumps pesados e arquivos temporários do pacote de deploy.
