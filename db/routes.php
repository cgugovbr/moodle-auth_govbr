<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Definição de rotas customizadas do plugin.
 *
 * Mapeia as URIs relativas para os métodos controladores correspondentes e 
 * define as restrições de acesso (necessidade de sessão ativa).
 */
$routes = [
    'callback' => [
        'pattern'        => '/callback.php',
        'controller'     => '\auth_govbr\oidc::handle_callback',
        'loginrequired'  => false,
    ],
];