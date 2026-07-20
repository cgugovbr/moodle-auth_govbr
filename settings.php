<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('auth_govbr/cpffield',
        get_string('cpffield', 'auth_govbr'),
        get_string('cpffield_desc', 'auth_govbr'), 'cpf', PARAM_ALPHANUMEXT));

    $settings->add(new admin_setting_configcheckbox('auth_govbr/showonloginpage',
        get_string('showonloginpage', 'auth_govbr'),
        get_string('showonloginpage_desc', 'auth_govbr'), 1));
}