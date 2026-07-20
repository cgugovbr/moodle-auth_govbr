# Plugin de Autenticação Gov.br para Moodle

Este plugin integra o Moodle ao sistema de Login Único da [Conta gov.br](https://www.gov.br), permitindo a autenticação de usuários na plataforma via credenciais do governo federal brasileiro. A integração utiliza o protocolo OAuth 2.0 com o fluxo PKCE (OpenID Connect).

---

## Funcionalidades

- **Login Unificado**: Habilita o botão “Entrar com gov.br” na página de autenticação padrão do Moodle.
- **Provisionamento Automático**: Cria e atualiza contas de usuários no Moodle utilizando os dados originados pelo Gov.br (Nome, E-mail, CPF).
- **Logout Unificado (Single Sign-Out)**: O encerramento da sessão no Moodle invalida simultaneamente a sessão do usuário no portal Gov.br.
- **Segurança e Infraestrutura**: Implementa o fluxo PKCE contra interceptação de código e permite a alternância de ambientes (Homologação/Produção) diretamente via variáveis de ambiente.

---

## Processamento de CPF

O plugin processa o CPF retornado pela API do Gov.br e armazena a informação no banco de dados com a formatação padrão (`000.000.000-00`). 

**Nota técnica:** A atuação do plugin é restrita à camada de backend e autenticação. Não há provisão de scripts de frontend (JavaScript) para aplicação de máscaras visuais em campos de formulário durante a digitação.

---

## Pré-requisitos

- Moodle 4.4 ou superior.
- PHP 8.1 ou superior (exige extensões `curl`, `json` e `openssl`).
- Servidor web configurado obrigatoriamente com certificado SSL (HTTPS).
- Credenciais de integração Gov.br (`client_id` e `client_secret`) obtidas via registro de aplicação no portal oficial.

---

## Instalação e Dependências

É recomendada a criação prévia de um campo de perfil de usuário customizado no Moodle para o armazenamento do CPF. Pode-se utilizar o tipo nativo "Entrada de texto" ou plugins de terceiros compatíveis.

1. Copie o diretório `govbr` para a estrutura de pastas `auth/` do Moodle.
2. Efetue login como administrador de sistema.
3. Navegue até **Administração do site > Notificações** e proceda com a atualização do banco de dados para concluir a instalação do plugin.

---

## Configuração

A configuração do plugin é dividida em duas etapas para garantir a segurança das credenciais e do roteamento de ambiente (padrão *Stateless/Secretless* no banco de dados).

### 1. Gestão de Credenciais e Ambiente (Infraestrutura)
Por diretrizes de segurança, o **Client ID**, o **Client Secret** e o **Ambiente de Execução** não são armazenados no banco de dados do Moodle e não possuem campos na interface gráfica. Eles devem ser injetados de forma segura na infraestrutura via variáveis de ambiente (`.env`) ou cofres de senhas, sendo repassados ao Moodle através do arquivo global `config.php`:

```php
// Inserir no final do arquivo config.php da raiz do Moodle
$CFG->auth_gov_clientid     = getenv('GOVBR_CLIENT_ID'); // ID do sistema
$CFG->auth_gov_clientsecret = getenv('GOVBR_CLIENT_SECRET'); // Senha do sistema
$CFG->auth_gov_environment  = getenv('GOVBR_ENVIRONMENT'); // Aceita 'staging' ou 'production' (Padrão: staging se omitido)