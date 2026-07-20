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

/**
 * Classe de configuração do plugin.
 *
 * Centraliza a leitura dos parâmetros de configuração da autenticação.
 * Parâmetros sensíveis (credenciais e ambiente) são consumidos estritamente 
 * via config.php global (injetados pela infraestrutura), enquanto parâmetros 
 * de comportamento visual utilizam a tabela config_plugins.
 */
class config
{
    /**
     * Obtém as configurações consolidadas do plugin.
     *
     * @return \stdClass Objeto contendo os parâmetros configurados.
     */
    public static function get(): \stdClass
    {
        global $CFG;

        $showonloginpage = get_config('auth_govbr', 'showonloginpage');

        return (object) [
            // Configurações lidas exclusivamente da infraestrutura (cofre/env) via config.php
            'clientid'        => $CFG->auth_gov_clientid ?? '',
            'clientsecret'    => $CFG->auth_gov_clientsecret ?? '',
            'environment'     => $CFG->auth_gov_environment ?? 'staging', // 'staging' ou 'production'
            
            // Configurações lidas da interface do Moodle (banco de dados)
            'cpffield'        => get_config('auth_govbr', 'cpffield') ?: 'cpf',
            'showonloginpage' => $showonloginpage !== false ? $showonloginpage : 1,
        ];
    }
}