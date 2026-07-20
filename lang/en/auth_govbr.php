<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']                   = 'Authentication Gov.br';
$string['auth_govbrdescription']        = 'OIDC authentication via Gov.br.';
$string['login_button_text']            = 'Log in with';
$string['signup_button_text']           = 'Create account with';

$string['error_invalid_state']          = 'Invalid session state (possible CSRF).';
$string['error_provider_communication'] = 'Error communicating with Gov.br: {$a}';
$string['error_missing_data']           = 'Gov.br did not return CPF, name or email.';
$string['error_creating_user']          = 'Unable to create the user account.';
$string['error_invalid_config']         = 'Invalid plugin configuration. Please verify the profile field settings and environment variables.';

$string['cpffield']                     = 'CPF Profile Field';
$string['cpffield_desc']                = 'Shortname of the user profile field used to store the CPF. The plugin will populate or search for the user by this field.';
$string['showonloginpage']              = 'Show button on login page';
$string['showonloginpage_desc']         = 'Enables the display of the "Login with gov.br" button on the standard Moodle login page.';