<?php

if (!defined('ABSPATH')) {
    exit;
}

function lktech_crm_register_roles(): void
{
    $caps = [
        'read'             => true,
        LKTECH_CRM_CAP     => true,
        'edit_posts'       => false,
        'upload_files'     => false,
    ];

    add_role('lk_manager', 'Менеджер CRM', $caps);

    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap(LKTECH_CRM_CAP)) {
        $admin->add_cap(LKTECH_CRM_CAP);
    }

    $manager = get_role('lk_manager');
    if ($manager && !$manager->has_cap(LKTECH_CRM_CAP)) {
        $manager->add_cap(LKTECH_CRM_CAP);
    }
}

add_action('init', 'lktech_crm_register_roles', 5);
