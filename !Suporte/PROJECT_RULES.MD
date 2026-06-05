# Guia de Regras e Padrões de Desenvolvimento - SETAS-WEB

**Documento:** Regras técnicas e arquiteturais obrigatórias  
**Projeto:** SETAS-WEB (Vanilla MVC)  
**Stack:** PHP Puro, HTML5, CSS, Tailwind, Bootstrap, Chart.js, MySQL/PDO  

---

## 1. Arquitetura Geral

### 1.1 Padrão MVC Manual
O sistema utiliza o padrão **MVC (Model-View-Controller)** manual, sem frameworks externos.

| Camada | Tecnologia | Responsabilidade |
|--------|------------|------------------|
| **Back-end** | PHP 8.1+ (Vanilla) | Lógica, API REST, PDO |
| **Front-end** | HTML5 + Bootstrap 3 (Estrutura) + Tailwind CSS (UI/UX) | Interfaces, interatividade |
| **Banco de Dados** | MySQL remoto via PDO | Persistência, segurança |
| **Servidor** | Apache com mod_rewrite | URLs amigáveis, redirecionamento |

### 1.2 Estrutura de Diretórios

```
/setas-web
├── /app
│   ├── /Controllers     → Lógica de controle (PascalCase)
│   ├── /Models          → Acesso ao banco via PDO
│   ├── /Views           → Templates PHP/HTML
│   ├── /Core            → Classes base (App, Controller, Database)
│   └── /template        → Componentes reutilizáveis (sidebar, navbar, header, footer)
├── /config
│   └── config.php       → Credenciais e constantes
├── /database
│   └── migrations.sql   → Histórico de alterações de banco
├── /assets
│   ├── /css             → Tailwind, Custom
│   ├── /js              → Chart.js, Scripts
│   ├── /img
│   ├── /audio
│   ├── /video
│   └── /logo_sistema
├── /cron                → Scripts para Cron Job (nome deve conter "Cron")
├── /temp                → Chaves de API e arquivos sensíveis (gitignore)
├── /!Suporte
│   └── PROJECT_RULES.MD
└── index.php            → Único ponto de entrada (Front Controller)
```

---

## 2. Padrões de Código e Nomenclatura

### 2.1 Nomenclatura
- **Classes:** PascalCase (`UsuarioController.php`, `ColaboradorModel.php`)
- **Métodos e variáveis:** camelCase (`calcularResultado()`, `$dataNascimento`)
- **Tabelas do banco:** snake_case (`resultados_disc`, `colaboradores`)
- **Constantes:** UPPER_SNAKE_CASE (`DB_HOST`, `MAX_TENTATIVAS`)

### 2.2 Segurança
- **PDO com Prepared Statements** obrigatório para evitar SQL Injection
- **Validação de entrada** em todo endpoint que recebe dados do usuário
- **Sanitização** de saída com `htmlspecialchars()` quando exibir dados
- **Sessões** baseadas em `session_start()` nativo do PHP
- **Senhas** sempre com `password_hash()` e `password_verify()`

---

## 3. Banco de Dados e Migrations

### 3.1 Regras
- Toda alteração de banco **deve** ser registrada em `/database/migrations.sql` com timestamp e descrição
- É **proibido** alterar o banco sem atualizar o arquivo de migrations
- Criar um arquivo `.php` para executar a migração no banco remoto
- Solicitar ao usuário a execução do script PHP de migração após alterações

### 3.2 Conexão
- Uso de PDO exclusivamente
- Credenciais em `/config/config.php`
- Charset UTF-8 obrigatório

---

## 4. Permissões e Funcionalidades

### 4.1 Permissão Superadministrador
- A permissão `superadministrador` **perpassa qualquer restrição**
- Esta permissão **não é cadastrável** pelo sistema (apenas hardcode)
- Usuários com essa permissão acessam todas as páginas

### 4.2 Cadastro de Funcionalidades
- As páginas do sistema devem ser cadastradas na tabela de funcionalidades
- Permissões definem quais funcionalidades cada perfil pode acessar

---

## 5. UI/UX e Front-end

### 5.1 Design
- **Minimalista e clean** com excelência em UI/UX
- **Referência visual:** YouTube (menus, padrão de usabilidade)
- **Cor de destaque:** `#3F9F6E` (substituindo o vermelho do YouTube)
- **Tipografia:** Inter
- **Sombras suaves** para profundidade

### 5.2 Componentes
- Navbar fixa na parte superior
- Menu lateral (sidebar) estilo YouTube
- Cards para conteúdo
- Gráficos com Chart.js para dashboard e relatórios

### 5.3 Modo Light/Dark
- Toggle único na barra superior
- **Lembrar última preferência** do usuário (localStorage/cookie)
- Transição suave entre temas

---

## 6. Autenticação e Sessões

### 6.1 Login
- CPF e senha como credenciais
- Campo senha com ícone de "olho" para alternar visibilidade
- Opção "Manter logado" → cookie por 30 dias, renovável a cada login
- Opção "Lembrar senha" → fluxo: confirmar CPF + Data Nascimento → cadastrar nova senha

### 6.2 Perfil de Colaborador
- Dados completos: nome, CPF, email, senha (padrão: 6 primeiros dígitos do CPF)
- Foto de perfil
- Tipo de permissão vinculado

---

## 7. API REST

### 7.1 Comunicação Back-Front
- Todo backend comunica com front via **REST API**
- Respostas em JSON
- Padrão de status HTTP: 200 (OK), 201 (Created), 400 (Bad Request), 401 (Unauthorized), 403 (Forbidden), 404 (Not Found), 500 (Server Error)

### 7.2 Chaves de API
- Arquivos com chaves de API devem ficar em `/temp/`
- Configurar `.gitignore` para a pasta `/temp/`

---

## 8. Bibliotecas e Assets

### 8.1 Uso de Vendor
- Bibliotecas devem ser copiáveis na pasta `vendor` sem necessidade de instalação no servidor
- Tailwind, Bootstrap, Chart.js podem ser usados via CDN ou pasta local em `/assets/`

### 8.2 Assets
- CSS em `/assets/css/`
- JS em `/assets/js/`
- Imagens em `/assets/img/`
- Logos em `/assets/logo_sistema/`
- Áudio e vídeo em suas respectivas subpastas

---

## 9. Cron Jobs

### 9.1 Regras
- Arquivos que rodam via Cron **devem** ter a palavra "Cron" no nome
- Devem ficar em `/cron/`
- Exemplo: `EnviarEmailCron.php`, `LimparSessoesCron.php`

---

## 10. Ambiente e Deploy

### 10.1 Detecção de Ambiente
- O PHP deve detectar automaticamente se a chamada veio de:
  - **Desenvolvimento:** `http://localhost/setas-web`
  - **Produção:** `https://iteva.com.br/setas-web/`
- A pasta raiz do projeto será uma dessas duas URLs

### 10.2 Processo de Deploy
- Deploy simplificado: usuário faz upload das pastas necessárias
- Sistema deve funcionar online após o upload
- Nunca incluir pasta `/temp/` no deploy (contém chaves sensíveis)

---

## 11. Checklist Obrigatório ao Final de Cada Prompt

1. **Responder em pt-br**
2. **Revisar as regras** do PROJECT_RULES antes de finalizar
3. **Indicar quais arquivos foram modificados** para deploy
4. **Garantir** detecção automática de ambiente (localhost vs iteva.com.br)
5. **Documentar** se houve alteração em migrations ou banco

---

## 12. Instruções Finais (Sempre Incluir)

1. **Sempre responda em pt-br**
2. **Sempre ao executar um prompt, reveja as regras do project rules**
3. **Sempre ao final do prompt indique quais arquivos foram modificados para ser feito o deploy**
4. O ambiente de produção ou de desenvolvimento devem ser configurados automaticamente, onde o php irá perceber se a chamada veio do `http://localhost/setas-web` ou do `https://iteva.com.br/setas-web/` (a pasta raiz sempre será uma dessas duas)
5. O deploy seguirá o processo simplificado, onde o usuário fará o upload das pastas necessárias e o sistema já irá funcionar online
