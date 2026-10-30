<?php
/**
 * Plugin Name: LK Tech CRM
 * Description: Личный кабинет CRM — сбор заявок с сайта и из Telegram-бота.
 * Version: 1.0.0
 * Author: LK Tech
 * Text Domain: lktech-crm
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LKTECH_CRM_VERSION', '1.1.0');
define('LKTECH_CRM_FILE', __FILE__);
define('LKTECH_CRM_DIR', plugin_dir_path(__FILE__));
define('LKTECH_CRM_URL', plugin_dir_url(__FILE__));
define('LKTECH_CRM_CAP', 'manage_lk_crm');
define('LKTECH_CRM_CPT', 'lk_lead');

require_once LKTECH_CRM_DIR . 'includes/helpers.php';
require_once LKTECH_CRM_DIR . 'includes/roles.php';
require_once LKTECH_CRM_DIR . 'includes/cpt.php';
require_once LKTECH_CRM_DIR . 'includes/api.php';
require_once LKTECH_CRM_DIR . 'includes/access.php';
require_once LKTECH_CRM_DIR . 'includes/admin.php';

register_activation_hook(__FILE__, 'lktech_crm_activate');
register_deactivation_hook(__FILE__, 'lktech_crm_deactivate');

function lktech_crm_activate(): void
{
    lktech_crm_register_roles();
    lktech_crm_register_cpt();
    if (!get_option('lktech_crm_api_secret')) {
        update_option('lktech_crm_api_secret', wp_generate_password(40, false, false));
    }
    flush_rewrite_rules();
}

function lktech_crm_deactivate(): void
{
    flush_rewrite_rules();
}

add_action('plugins_loaded', static function () {
    load_plugin_textdomain('lktech-crm', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('init', static function () {
    if (get_option('lktech_crm_rewrite_ver') === LKTECH_CRM_VERSION) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('lktech_crm_rewrite_ver', LKTECH_CRM_VERSION);
}, 99);
