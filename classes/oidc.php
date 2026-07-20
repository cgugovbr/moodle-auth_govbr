<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_govbr;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Gerencia a integração OAuth2/OIDC com o sistema Gov.br.
 */
class oidc
{
    /** @var \stdClass Configurações do plugin auth_govbr. */
    private $config;

    /**
     * Inicializa a instância carregando as configurações do plugin.
     */
    public function __construct()
    {
        $this->config = \auth_govbr\config::get();
    }

    /**
     * Obtém os URIs dos endpoints baseados no ambiente configurado.
     *
     * @return array Endpoints de autorização, token e userinfo.
     */
    private function get_endpoints(): array
    {
        if ($this->config->environment === 'production') {
            return [
                'authorization' => 'https://sso.acesso.gov.br/authorize',
                'token'         => 'https://sso.acesso.gov.br/token',
                'userinfo'      => 'https://sso.acesso.gov.br/userinfo',
            ];
        }
        return [
            'authorization' => 'https://sso.staging.acesso.gov.br/authorize',
            'token'         => 'https://sso.staging.acesso.gov.br/token',
            'userinfo'      => 'https://sso.staging.acesso.gov.br/userinfo',
        ];
    }

    /**
     * Constrói a URL de autorização para o redirecionamento inicial.
     * Registra os parâmetros de estado e PKCE na sessão do usuário.
     *
     * @return \moodle_url URL completa para redirecionamento.
     */
    public function get_login_url()
    {
        global $CFG, $SESSION;

        $endpoints = $this->get_endpoints();

        $code_verifier = $this->generate_code_verifier();
        $code_challenge = $this->generate_code_challenge($code_verifier);
        $state = $this->generate_state();

        $SESSION->govbr_code_verifier = $code_verifier;
        $SESSION->govbr_oauth2state = $state;

        $params = [
            'response_type'         => 'code',
            'client_id'             => $this->config->clientid,
            'scope'                 => 'openid profile email',
            'redirect_uri'          => $CFG->wwwroot . '/auth/govbr/callback.php',
            'state'                 => $state,
            'code_challenge'        => $code_challenge,
            'code_challenge_method' => 'S256'
        ];

        return new \moodle_url($endpoints['authorization'], $params);
    }

    /**
     * Processa a resposta do fluxo de autorização.
     * Valida o estado, negocia o token de acesso e executa o login.
     *
     * @throws \moodle_exception Falha na validação ou comunicação.
     */
    public static function handle_callback()
    {
        global $SESSION;

        $code  = \required_param('code', PARAM_RAW);
        $state = \required_param('state', PARAM_RAW);

        if (empty($state) || empty($SESSION->govbr_oauth2state) || $state !== $SESSION->govbr_oauth2state) {
            unset($SESSION->govbr_oauth2state);
            throw new \moodle_exception('error_invalid_state', 'auth_govbr');
        }

        unset($SESSION->govbr_oauth2state);

        try {
            $instance = new self();
            $endpoints = $instance->get_endpoints();
            $token = $instance->get_token($code, $endpoints['token']);
            $userinfo = $instance->get_userinfo($token->access_token, $endpoints['userinfo']);
        } catch (\Exception $e) {
            throw new \moodle_exception('error_provider_communication', 'auth_govbr', '', null, $e->getMessage());
        }

        self::process_user_login($userinfo);
    }

    /**
     * Solicita o token de acesso utilizando o código de autorização e o code_verifier.
     *
     * @param string $code Código retornado na autorização.
     * @param string $token_endpoint URI do endpoint de token.
     * @return \stdClass Objeto contendo os dados do token.
     * @throws \moodle_exception Falha na requisição HTTP.
     */
    private function get_token($code, $token_endpoint)
    {
        global $CFG, $SESSION;

        if (empty($SESSION->govbr_code_verifier)) {
            throw new \moodle_exception('error_invalid_state', 'auth_govbr');
        }

        $code_verifier = $SESSION->govbr_code_verifier;
        unset($SESSION->govbr_code_verifier);

        $postdata = http_build_query([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $CFG->wwwroot . '/auth/govbr/callback.php',
            'code_verifier' => $code_verifier,
            'client_id'     => $this->config->clientid,
            'client_secret' => $this->config->clientsecret,
        ], '', '&', PHP_QUERY_RFC3986);

        $ch = curl_init($token_endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postdata,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode !== 200) {
            throw new \moodle_exception('error_provider_communication', 'auth_govbr', '', null, $response);
        }

        return json_decode($response);
    }

    /**
     * Consulta os dados do usuário no endpoint de perfil.
     *
     * @param string $accesstoken Token JWT de acesso.
     * @param string $userinfo_endpoint URI do endpoint userinfo.
     * @return \stdClass Estrutura contendo o identificador e os dados do usuário.
     * @throws \moodle_exception Falha na requisição HTTP.
     */
    private function get_userinfo($accesstoken, $userinfo_endpoint)
    {
        $headers = ['Authorization: Bearer ' . $accesstoken];

        $ch = curl_init($userinfo_endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode !== 200) {
            throw new \moodle_exception('error_provider_communication', 'auth_govbr', '', null, $response);
        }

        return json_decode($response);
    }

    /**
     * Resolve a identidade do usuário no banco de dados local.
     * Atualiza o cadastro existente ou provisiona uma nova conta.
     *
     * @param \stdClass $userinfo Objeto contendo CPF (sub), nome e e-mail.
     * @throws \moodle_exception Omissão de dados críticos ou falha no provisionamento.
     */
    public static function process_user_login($userinfo)
    {
        global $DB, $CFG, $SESSION;
        $cfg = \auth_govbr\config::get();
        
        $cpffield = clean_param($cfg->cpffield, PARAM_ALPHANUMEXT);

        if (empty($cpffield) || !preg_match('/^[a-zA-Z0-9_]+$/', $cpffield)) {
            throw new \moodle_exception('error_invalid_config', 'auth_govbr');
        }

        $cpf      = $userinfo->sub ?? null;
        $email    = $userinfo->email ?? null;
        $fullname = $userinfo->name ?? null;

        if (empty($cpf) || empty($email) || empty($fullname)) {
            throw new \moodle_exception('error_missing_data', 'auth_govbr');
        }

        $cpf = self::format_cpf($cpf);
        $nameparts = explode(' ', $fullname, 2);
        $firstname = $nameparts[0];
        $lastname  = $nameparts[1] ?? ' ';

        $sql = "SELECT u.*
                  FROM {user} u
            INNER JOIN {user_info_data} uid ON uid.userid = u.id
            INNER JOIN {user_info_field} uif ON uif.id = uid.fieldid
                 WHERE uif.shortname = :cpffield
                   AND uid.data = :cpf
                   AND u.deleted = 0
                   AND u.suspended = 0";

        $user = $DB->get_record_sql($sql, ['cpf' => $cpf, 'cpffield' => $cpffield]);

        if ($user) {
            $user->auth      = 'govbr';
            $user->firstname = $firstname;
            $user->lastname  = $lastname;
            $user->idnumber  = $cpf;

            \user_update_user($user);
        } else {
            $user = $DB->get_record('user', [
                'idnumber'   => $cpf,
                'mnethostid' => $CFG->mnet_localhost_id,
                'suspended'  => 0,
                'deleted'    => 0
            ], '*', IGNORE_MULTIPLE);

            if ($user) {
                $user->auth      = 'govbr';
                $user->email     = $email;
                $user->firstname = $firstname;
                $user->lastname  = $lastname;

                \user_update_user($user);
            } else {
                $newuser               = new \stdClass();
                $newuser->username     = $cpf;
                $newuser->auth         = 'govbr';
                $newuser->idnumber     = $cpf;
                $newuser->password     = hash_internal_user_password(random_string(14));
                $newuser->firstname    = $firstname;
                $newuser->lastname     = $lastname;
                $newuser->email        = $email;
                $newuser->confirmed    = 1;
                $newuser->mnethostid   = $CFG->mnet_localhost_id;

                \user_create_user($newuser, false, false);
                $user = \get_complete_user_data('username', $newuser->username);
            }
        }

        if (!$user) {
            throw new \moodle_exception('error_creating_user', 'auth_govbr');
        }

        if (self::cpf_custom_field_exists($cpffield)) {
            try {
                profile_load_data($user);
                $fieldname = 'profile_field_' . $cpffield;
                
                if (preg_match('/^profile_field_[a-zA-Z0-9_]+$/', $fieldname)) {
                    $user->{$fieldname} = $cpf;
                    profile_save_data($user);
                } else {
                    throw new \coding_exception('Invalid dynamic field name.');
                }
            } catch (\Throwable $e) {
                debugging('Failed to save dynamic CPF field: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        \complete_user_login($user);

        $urltogo = $SESSION->wantsurl ?? $CFG->wwwroot;
        unset($SESSION->wantsurl);

        \redirect($urltogo);
    }

    /**
     * Valida a existência estrutural do campo de perfil.
     *
     * @param string $cpffield Nome curto (shortname) do campo.
     * @return bool Retorna verdadeiro se o campo estiver registrado no sistema.
     */
    private static function cpf_custom_field_exists($cpffield)
    {
        $customfields = profile_get_custom_fields();
        foreach ($customfields as $field) {
            if ($field->shortname === $cpffield) {
                return true;
            }
        }
        return false;
    }

    /**
     * Padroniza o identificador numérico na formatação oficial de CPF.
     *
     * @param string $cpf Sequência numérica de 11 dígitos.
     * @return string Identificador formatado (000.000.000-00).
     */
    private static function format_cpf(string $cpf): string
    {
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf);
    }

    /**
     * Gera uma string aleatória para o fluxo PKCE.
     *
     * @return string Code Verifier codificado em Base64URL.
     */
    private function generate_code_verifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    /**
     * Computa o desafio criptográfico a partir do verificador.
     *
     * @param string $code_verifier Sequência gerada na etapa inicial do PKCE.
     * @return string Code Challenge codificado em Base64URL.
     */
    private function generate_code_challenge(string $code_verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Cria um valor de estado aleatório para mitigação de CSRF.
     *
     * @return string Hexadecimal aleatório.
     */
    private function generate_state(): string
    {
        return bin2hex(random_bytes(16));
    }
}