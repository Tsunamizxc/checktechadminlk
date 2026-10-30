<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function () {
    add_theme_support('title-tag');
});

add_filter('body_class', static function ($classes) {
    $classes[] = 'lk-body';
    return $classes;
});

add_action('wp_enqueue_scripts', static function () {
    wp_enqueue_style(
        'lktech-fonts',
        'https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'lktech-crm',
        get_template_directory_uri() . '/assets/css/crm.css',
        ['lktech-fonts'],
        '3.2.0'
    );

    // Скрываем WP admin bar на фронте CRM.
    wp_add_inline_style(
        'lktech-crm',
        '#wpadminbar{display:none!important}html.admin-bar{margin-top:0!important}body.admin-bar{padding-top:0!important}'
    );

    if (!is_user_logged_in() || !function_exists('lktech_crm_user_can_access') || !lktech_crm_user_can_access()) {
        return;
    }

    wp_enqueue_script(
        'lktech-crm',
        get_template_directory_uri() . '/assets/js/crm.js',
        [],
        '3.2.0',
        true
    );

    wp_localize_script('lktech-crm', 'LKTECH', [
        'rest'     => esc_url_raw(rest_url('lktech/v1')),
        'nonce'    => wp_create_nonce('wp_rest'),
        'home'     => home_url('/'),
        'settings' => home_url('/settings/'),
        'user'     => [
            'id'   => get_current_user_id(),
            'name' => wp_get_current_user()->display_name,
        ],
        'leadId'   => (int) get_query_var('lk_lead_id'),
        'page'     => (int) get_query_var('lk_settings') === 1 ? 'settings' : 'dashboard',
    ]);
});

add_filter('template_include', static function ($template) {
    if ((int) get_query_var('lk_login') === 1) {
        $login = get_template_directory() . '/templates/login.php';
        if (is_file($login)) {
            return $login;
        }
    }

    $is_settings = (int) get_query_var('lk_settings') === 1
        || (isset($_SERVER['REQUEST_URI']) && preg_match('#/settings/?(\?|$)#', (string) $_SERVER['REQUEST_URI']));
    if ($is_settings) {
        $settings = get_template_directory() . '/templates/settings.php';
        if (is_file($settings)) {
            return $settings;
        }
    }

    $lead_id = (int) get_query_var('lk_lead_id');
    if ($lead_id > 0) {
        $single = get_template_directory() . '/templates/lead.php';
        if (is_file($single)) {
            return $single;
        }
    }

    if (is_front_page() || is_home()) {
        $dash = get_template_directory() . '/templates/dashboard.php';
        if (is_file($dash)) {
            return $dash;
        }
    }

    return $template;
});

/**
 * Обработка формы входа на фронте.
 */
add_action('init', static function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    if (empty($_POST['lktech_login'])) {
        return;
    }
    if (!wp_verify_nonce((string) ($_POST['_wpnonce'] ?? ''), 'lktech_login')) {
        return;
    }

    $login = sanitize_text_field(wp_unslash((string) ($_POST['log'] ?? '')));
    $pass = (string) ($_POST['pwd'] ?? '');
    $remember = !empty($_POST['rememberme']);

    $user = wp_signon([
        'user_login'    => $login,
        'user_password' => $pass,
        'remember'      => $remember,
    ], false);

    if (is_wp_error($user)) {
        wp_safe_redirect(add_query_arg('error', '1', home_url('/login/')));
        exit;
    }

    if (!function_exists('lktech_crm_user_can_access') || !lktech_crm_user_can_access((int) $user->ID)) {
        wp_logout();
        wp_safe_redirect(add_query_arg('denied', '1', home_url('/login/')));
        exit;
    }

    wp_safe_redirect(home_url('/'));
    exit;
});
