<?php

/**
 * Plugin de Autenticação Gov.br
 *
 * @package    auth_govbr
 * @copyright  2025 Desenvolvido pela Controladoria Geral da União - (DISOL/CGSIS/DTI/SE/CGU)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

/**
 * Ponto de entrada para o callback do provedor de identidade Gov.br.
 */
\auth_govbr\oidc::handle_callback();