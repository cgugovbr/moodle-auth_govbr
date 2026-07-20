<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

// Interrompe o fluxo e redireciona para a página inicial se o usuário já estiver autenticado.
if (isloggedin() && !isguestuser()) {
    redirect(new \moodle_url('/'));
}

$oidc = new \auth_govbr\oidc();
$loginurl = $oidc->get_login_url();

redirect($loginurl);