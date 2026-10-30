<?php

if (!defined('ABSPATH')) {
    exit;
}

function lktech_crm_register_cpt(): void
{
    register_post_type(LKTECH_CRM_CPT, [
        'labels' => [
            'name'          => 'Заявки',
            'singular_name' => 'Заявка',
            'menu_name'     => 'Заявки CRM',
            'all_items'     => 'Все заявки',
            'edit_item'     => 'Заявка',
            'search_items'  => 'Искать заявки',
            'not_found'     => 'Заявок нет',
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-groups',
        'menu_position'       => 3,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'capabilities'        => [
            'create_posts' => 'do_not_allow',
        ],
        'supports'            => ['title', 'editor'],
        'has_archive'         => false,
        'rewrite'             => false,
        'show_in_rest'        => false,
    ]);
}

add_action('init', 'lktech_crm_register_cpt');

add_action('init', static function () {
    add_rewrite_rule('^lead/([0-9]+)/?$', 'index.php?lk_lead_id=$matches[1]', 'top');
    add_rewrite_rule('^login/?$', 'index.php?lk_login=1', 'top');
});

add_filter('query_vars', static function ($vars) {
    $vars[] = 'lk_lead_id';
    $vars[] = 'lk_login';
    return $vars;
});
