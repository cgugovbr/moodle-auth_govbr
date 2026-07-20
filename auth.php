<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Plugin de autenticação Gov.br via OIDC.
 */
class auth_plugin_govbr extends auth_plugin_base
{
    /**
     * Inicializa a instância do plugin e carrega as configurações.
     */
    public function __construct()
    {
        $this->authtype = 'govbr';
        $this->config = \auth_govbr\config::get();
    }

    /**
     * Provê os dados para exibição do botão de login na interface padrão do Moodle.
     *
     * @param string $wantsurl URL de destino solicitada pelo usuário.
     * @return array Estrutura contendo os parâmetros visuais e de roteamento do provedor de identidade.
     */
    public function loginpage_idp_list($wantsurl)
    {
        if (empty($this->config->showonloginpage) || empty($this->config->clientid) || empty($this->config->clientsecret)) {
            return [];
        }

        $oidc = new \auth_govbr\oidc();

        return [[
            'url'     => $oidc->get_login_url(),
            'iconurl' => new moodle_url('/auth/govbr/pix/logo_gov.png'),
            'name'    => get_string('login_button_text', 'auth_govbr')
        ]];
    }

    /**
     * Intercepta a tentativa de login via formulário nativo.
     *
     * @param string $username Identificador fornecido no formulário.
     * @param string $password Senha fornecida no formulário.
     * @return bool Retorna falso para bloquear a autenticação local e exigir o fluxo OIDC.
     */
    public function user_login($username, $password): bool
    {
        return false;
    }

    /**
     * Hook executado durante o processo de saída (logout) do Moodle.
     *
     * Valida a origem da autenticação, encerra a sessão local e redireciona 
     * a requisição para o endpoint de logout do provedor (Single Sign-Out).
     *
     * @return void
     */
    public function logoutpage_hook()
    {
        global $USER, $CFG;

        if (!isset($USER->auth) || $USER->auth !== 'govbr') {
            return;
        }

        \core\session\manager::kill_user_sessions($USER->id);
        \core\session\manager::terminate_current();

        $isproduction = ($this->config->environment === 'production');
        $govbrlogout = $isproduction
            ? 'https://sso.acesso.gov.br/logout'
            : 'https://sso.staging.acesso.gov.br/logout';

        $client_id = $this->config->clientid;
        $final_redirect = rtrim($CFG->wwwroot, '/') . '/';

        $logout_url = $govbrlogout
            . '?client_id=' . urlencode($client_id)
            . '&post_logout_redirect_uri=' . urlencode($final_redirect);

        redirect($logout_url);
    }
}