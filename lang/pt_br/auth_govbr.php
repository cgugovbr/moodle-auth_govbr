<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']                   = 'Autenticação Gov.br';
$string['auth_govbrdescription']        = 'Autenticação OIDC via Gov.br.';
$string['login_button_text']            = 'Entrar com';
$string['signup_button_text']           = 'Criar conta com o';

$string['error_invalid_state']          = 'Estado de sessão inválido (possível CSRF).';
$string['error_provider_communication'] = 'Erro de comunicação com o Gov.br: {$a}';
$string['error_missing_data']           = 'Gov.br não retornou CPF, nome ou email.';
$string['error_creating_user']          = 'Não foi possível criar a conta de usuário.';
$string['error_invalid_config']         = 'Configuração do plugin inválida. Por favor, verifique as definições do campo de perfil e as variáveis de ambiente.';

$string['cpffield']                     = 'Campo de Perfil do CPF';
$string['cpffield_desc']                = 'Nome breve (shortname) do campo de perfil de usuário usado para armazenar o CPF. O plugin preencherá ou buscará o usuário por este campo.';
$string['showonloginpage']              = 'Exibir botão na tela de login';
$string['showonloginpage_desc']         = 'Habilita a exibição do botão "Entrar com gov.br" na página de login padrão do Moodle.';