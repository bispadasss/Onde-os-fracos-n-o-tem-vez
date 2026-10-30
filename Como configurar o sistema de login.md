# QUIZ! com login social — Guia de instalação e configuração

Este pacote adiciona ao seu projeto de quizzes o **login com Google, Facebook e X (Twitter)**, com sessão, avatar, logout e vínculo do quiz ao autor.

---

## O que foi adicionado

| Arquivo | Para que serve |
|---|---|
| `config.php` | Configuração central: banco de dados + chaves do login social + funções usadas por todas as páginas |
| `login.php` | Tela de login com os botões do Google, Facebook e X |
| `auth/google.php` | Inicia o login com o Google |
| `auth/callback_google.php` | Recebe a resposta do Google e loga o usuário |
| `auth/facebook.php` | Inicia o login com o Facebook |
| `auth/callback_facebook.php` | Recebe a resposta do Facebook e loga o usuário |
| `auth/twitter.php` | Inicia o login com o X (OAuth 2.0 + PKCE) |
| `auth/callback_twitter.php` | Recebe a resposta do X e loga o usuário |
| `auth/logout.php` | Sai da conta |
| `sql/atualizar_banco.sql` | Cria a tabela `usuarios` e a coluna `usuario_id` em `quizzes` |
| `index.php` | Sua home antiga (`index.html`), agora mostrando o usuário logado |
| `catalogo.php` / `criar_quiz.php` / `jogar.php` | Atualizados: cabeçalho com avatar/nome/Sair, autor do quiz, etc. |

---

## 1. Atualizar o banco de dados (obrigatório)

1. Abra o **phpMyAdmin** → clique no banco **quiz_db** → aba **SQL**
2. Abra o arquivo `sql/atualizar_banco.sql`, copie TODO o conteúdo, cole e clique em **Executar**

Isso cria a tabela `usuarios` e a coluna `usuario_id` na tabela `quizzes`.

## 2. Colocar no servidor

Copie a pasta `quiz-com-login` inteira para dentro de `htdocs` (XAMPP), `www` (WAMP) ou `htdocs` (Laragon):

```
C:\xampp\htdocs\quiz-com-login\
```

Acesse: `http://localhost/quiz-com-login/`

> **Atenção:** apague o `index.html` antigo do seu projeto (ou ele vai "brigar" com o novo `index.php`).

## 3. Testar agora — Modo demonstração

Enquanto as chaves em `config.php` estiverem **vazias**, os botões de login funcionam em **modo demonstração**: cadastrar e logar com uma conta de teste (ex.: "Demo Google") sem sair do seu computador.

Isso é proposital para você já conseguir **apresentar o projeto funcionando** sem precisar criar conta em nenhum portal de desenvolvedor. Um aviso amarelo aparece na tela de login quando o modo demonstração está ativo.

## 4. Ativar o login REAL (opcional, mas recomendado)

Primeiro descubra a **URL base** do seu site, por exemplo:

```
http://localhost/quiz-com-login
```

As **URLs de retorno (callback)** que você vai cadastrar nos portais são:

| Provedor | URL de retorno |
|---|---|
| Google | `http://localhost/quiz-com-login/auth/callback_google.php` |
| Facebook | `http://localhost/quiz-com-login/auth/callback_facebook.php` |
| X (Twitter) | `http://localhost/quiz-com-login/auth/callback_twitter.php` |

> Se o site estiver online, troque por `https://seusite.com/...`.

### 4.1 Google

1. Acesse <https://console.cloud.google.com> e crie um projeto (ou use um existente)
2. Menu **APIs e serviços** → **Tela de permissão OAuth** → escolha **Externo** → preencha o nome do app e salve
3. **Credenciais** → **Criar credenciais** → **ID do cliente OAuth** → Tipo: **Aplicativo da Web**
4. Em **URIs de redirecionamento autorizados**, cole a URL de retorno do Google (tabela acima)
5. Copie o **Client ID** e o **Client Secret** e cole em `$OAUTH['google']` no `config.php`

### 4.2 Facebook

1. Acesse <https://developers.facebook.com> → **Meus apps** → **Criar app** (tipo **Consumidor**)
2. Adicione o produto **Login do Facebook**
3. Em **Login do Facebook → Configurações**, no campo **URIs de redirecionamento OAuth válidos**, cole a URL de retorno do Facebook (tabela acima)
4. Copie o **App ID** e o **App Secret** e cole em `$OAUTH['facebook']` no `config.php`
5. **Importante:** com o app em modo *Desenvolvimento*, só a sua conta e os testadores conseguem logar. Para todo mundo logar, publique o app (chave no topo da página do app)

### 4.3 X (Twitter)

1. Acesse <https://developer.x.com> → Portal do desenvolvedor → crie um **Projeto** e um **App**
2. No app → **User authentication settings** → **Set up**:
   - App permissions: **Read**
   - Type of App: **Web App, Automated App or Bot**
   - **Callback URI / Redirect URL**: a URL de retorno do X (tabela acima)
   - **Website URL**: a URL do seu site
3. Gere o **OAuth 2.0 Client ID** e o **Client Secret** e cole em `$OAUTH['twitter']` no `config.php`
4. **Observação:** o X não entrega o e-mail do usuário em nenhum fluxo — por isso a coluna de e-mail fica vazia para contas do X (o sistema já trata isso)

Depois de preencher as chaves, o aviso de "modo demonstração" desaparece da tela de login e os botões passam a fazer o login real.

---

## Requisitos do servidor

- PHP **7.4 ou superior** com a extensão **cURL** habilitada (no XAMPP ela já vem ligada por padrão)
- MySQL/MariaDB com o banco `quiz_db` já criado (você já tinha)
- HTTPS quando o site estiver no ar (os provedores exigem para callback em produção)

## Perguntas rápidas

**Erro ao logar de verdade ("redirect_uri_mismatch")?**
A URL de retorno cadastrada no portal do provedor precisa ser **exatamente igual** à que o site usa (inclusive `http` vs `https` e a pasta). Confira byte a byte.

**Como sei qual é a minha URL base?**
Abra o site no navegador e olhe o endereço. O que vem antes de `/login.php` é a sua URL base.

**Quero exigir login para criar quiz:**
Abra `criar_quiz.php` e descomente a linha `// exigirLogin();` no topo.

**Quero trocar senhas de banco:**
Edite as constantes `DB_HOST`, `DB_NOME`, `DB_USUARIO` e `DB_SENHA` no topo do `config.php` — as outras páginas agora usam essa configuração central.

---

## Estrutura final

```
quiz-com-login/
├── config.php                  ← banco + chaves OAuth + funções
├── index.php                   ← home (ex-index.html)
├── login.php                   ← tela de login social
├── catalogo.php                ← catálogo (mostra o autor do quiz)
├── criar_quiz.php              ← criação de quiz (vincula ao usuário)
├── jogar.php                   ← jogo (não exige login)
├── COMO-CONFIGURAR.md          ← este guia
├── sql/
│   └── atualizar_banco.sql     ← rode no phpMyAdmin
└── auth/
    ├── google.php              ├── callback_google.php
    ├── facebook.php            ├── callback_facebook.php
    ├── twitter.php             ├── callback_twitter.php
    └── logout.php
```
