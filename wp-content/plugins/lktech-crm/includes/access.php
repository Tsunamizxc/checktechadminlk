<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Сайт закрыт: только логин и REST для интеграций.
 */
add_action('template_redirect', static function () {
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    if (is_admin()) {
        return;
    }

    $is_login = (int) get_query_var('lk_login') === 1
        || (isset($_SERVER['REQUEST_URI']) && preg_match('#/login/?(\?|$)#', (string) $_SERVER['REQUEST_URI']));

    if ($is_login) {
        if (is_user_logged_in() && lktech_crm_user_can_access()) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
        return;
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/login/'));
        exit;
    }

    if (!lktech_crm_user_can_access()) {
        wp_logout();
        wp_safe_redirect(add_query_arg('denied', '1', home_url('/login/')));
        exit;
    }
}, 1);

add_action('wp_login', static function ($user_login, $user) {
    if (!lktech_crm_user_can_access((int) $user->ID)) {
        wp_logout();
        wp_safe_redirect(add_query_arg('denied', '1', home_url('/login/')));
        exit;
    }
}, 10, 2);

add_filter('login_redirect', static function ($redirect_to, $requested, $user) {
    if ($user instanceof WP_User && lktech_crm_user_can_access((int) $user->ID)) {
        return home_url('/');
    }
    return $redirect_to;
}, 10, 3);

add_action('after_setup_theme', static function () {
    show_admin_bar(current_user_can('manage_options'));
});

add_action('admin_init', static function () {
    if (wp_doing_ajax()) {
        return;
    }
    if (!current_user_can('manage_options') && lktech_crm_user_can_access()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
});
