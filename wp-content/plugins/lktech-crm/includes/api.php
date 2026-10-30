<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', static function () {
    register_rest_route('lktech/v1', '/leads', [
        [
            'methods'             => 'POST',
            'callback'            => 'lktech_crm_rest_create_lead',
            'permission_callback' => 'lktech_crm_rest_secret_ok',
        ],
        [
            'methods'             => 'GET',
            'callback'            => 'lktech_crm_rest_list_leads',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
    ]);

    register_rest_route('lktech/v1', '/leads/(?P<id>\d+)', [
        [
            'methods'             => 'GET',
            'callback'            => 'lktech_crm_rest_get_lead',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
        [
            'methods'             => 'DELETE',
            'callback'            => 'lktech_crm_rest_delete_lead',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
    ]);

    register_rest_route('lktech/v1', '/leads/(?P<id>\d+)/status', [
        [
            'methods'             => 'POST',
            'callback'            => 'lktech_crm_rest_update_status',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
    ]);

    register_rest_route('lktech/v1', '/leads/(?P<id>\d+)/notes', [
        [
            'methods'             => 'POST',
            'callback'            => 'lktech_crm_rest_update_notes',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
    ]);

    register_rest_route('lktech/v1', '/leads/(?P<id>\d+)/comments', [
        [
            'methods'             => 'GET',
            'callback'            => 'lktech_crm_rest_list_comments',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
        [
            'methods'             => 'POST',
            'callback'            => 'lktech_crm_rest_add_comment',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
    ]);

    register_rest_route('lktech/v1', '/stats', [
        [
            'methods'             => 'GET',
            'callback'            => static function () {
                return rest_ensure_response(lktech_crm_stats());
            },
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
    ]);

    register_rest_route('lktech/v1', '/feed', [
        [
            'methods'             => 'GET',
            'callback'            => 'lktech_crm_rest_feed',
            'permission_callback' => 'lktech_crm_rest_user_ok',
        ],
    ]);

    register_rest_route('lktech/v1', '/health', [
        [
            'methods'             => 'GET',
            'callback'            => static function () {
                return rest_ensure_response(['ok' => true, 'version' => LKTECH_CRM_VERSION]);
            },
            'permission_callback' => '__return_true',
        ],
    ]);
});

function lktech_crm_rest_secret_ok(WP_REST_Request $request): bool
{
    $expected = lktech_crm_api_secret();
    $got = (string) $request->get_header('x-crm-secret');
    if ($got === '') {
        $got = (string) $request->get_param('secret');
    }

    return $expected !== '' && hash_equals($expected, $got);
}

function lktech_crm_rest_user_ok(): bool
{
    return is_user_logged_in() && lktech_crm_user_can_access();
}

function lktech_crm_rest_create_lead(WP_REST_Request $request)
{
    $data = $request->get_json_params();
    if (!is_array($data)) {
        $data = $request->get_params();
    }

    $lead_id = lktech_crm_store_lead(is_array($data) ? $data : []);
    if ($lead_id <= 0) {
        return new WP_Error('lead_failed', 'Не удалось сохранить заявку', ['status' => 500]);
    }

    return rest_ensure_response([
        'ok'      => true,
        'id'      => $lead_id,
        'lead'    => lktech_crm_lead_to_array($lead_id),
    ]);
}

function lktech_crm_rest_list_leads(WP_REST_Request $request)
{
    $status = sanitize_key((string) $request->get_param('status'));
    $source = sanitize_key((string) $request->get_param('source'));
    $type = sanitize_key((string) $request->get_param('type'));
    $search = sanitize_text_field((string) $request->get_param('s'));
    $page = max(1, (int) $request->get_param('page'));
    $per_page = min(100, max(1, (int) ($request->get_param('per_page') ?: 30)));

    $meta_query = ['relation' => 'AND'];
    if ($status && isset(lktech_crm_status_labels()[$status])) {
        $meta_query[] = [
            'key'   => '_lk_status',
            'value' => $status,
        ];
    }
    if ($source && isset(lktech_crm_source_labels()[$source])) {
        $meta_query[] = [
            'key'   => '_lk_source',
            'value' => $source,
        ];
    }
    if ($type && isset(lktech_crm_type_labels()[$type])) {
        $meta_query[] = [
            'key'   => '_lk_type',
            'value' => $type,
        ];
    }

    $args = [
        'post_type'      => LKTECH_CRM_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        's'              => $search,
    ];
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    $q = new WP_Query($args);
    $items = [];
    foreach ($q->posts as $post) {
        $items[] = lktech_crm_lead_to_array((int) $post->ID);
    }

    return rest_ensure_response([
        'items'       => $items,
        'total'       => (int) $q->found_posts,
        'page'        => $page,
        'per_page'    => $per_page,
        'total_pages' => (int) $q->max_num_pages,
    ]);
}

function lktech_crm_rest_get_lead(WP_REST_Request $request)
{
    $lead = lktech_crm_lead_to_array((int) $request['id']);
    if (!$lead) {
        return new WP_Error('not_found', 'Заявка не найдена', ['status' => 404]);
    }

    return rest_ensure_response($lead);
}

function lktech_crm_rest_feed(WP_REST_Request $request)
{
    $since_id = max(0, (int) $request->get_param('since_id'));
    $status = sanitize_key((string) $request->get_param('status'));
    $source = sanitize_key((string) $request->get_param('source'));
    $type = sanitize_key((string) $request->get_param('type'));
    $search = sanitize_text_field((string) $request->get_param('s'));

    $list_req = new WP_REST_Request('GET');
    $list_req->set_param('status', $status);
    $list_req->set_param('source', $source);
    $list_req->set_param('type', $type);
    $list_req->set_param('s', $search);
    $list_req->set_param('per_page', 50);
    $list_req->set_param('page', 1);
    $list = lktech_crm_rest_list_leads($list_req);
    $data = $list->get_data();
    $items = $data['items'] ?? [];

    $max_id = 0;
    $new_ids = [];
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        if ($id > $max_id) {
            $max_id = $id;
        }
        if ($since_id > 0 && $id > $since_id) {
            $new_ids[] = $id;
        }
    }

    return rest_ensure_response([
        'items'   => $items,
        'stats'   => lktech_crm_stats(),
        'max_id'  => $max_id,
        'new_ids' => $new_ids,
        'server'  => time(),
    ]);
}

function lktech_crm_rest_update_status(WP_REST_Request $request)
{
    $lead_id = (int) $request['id'];
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== LKTECH_CRM_CPT) {
        return new WP_Error('not_found', 'Заявка не найдена', ['status' => 404]);
    }

    $body = $request->get_json_params();
    $status = sanitize_key((string) (($body['status'] ?? $request->get_param('status')) ?: ''));
    if (!isset(lktech_crm_status_labels()[$status])) {
        return new WP_Error('bad_status', 'Некорректный статус', ['status' => 400]);
    }

    update_post_meta($lead_id, '_lk_status', $status);

    $assigned = (int) ($body['assigned'] ?? 0);
    if ($assigned > 0 && lktech_crm_user_can_access($assigned)) {
        update_post_meta($lead_id, '_lk_assigned', $assigned);
    } elseif (($body['take'] ?? false) && get_current_user_id()) {
        update_post_meta($lead_id, '_lk_assigned', get_current_user_id());
    }

    return rest_ensure_response(lktech_crm_lead_to_array($lead_id));
}

function lktech_crm_rest_update_notes(WP_REST_Request $request)
{
    $lead_id = (int) $request['id'];
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== LKTECH_CRM_CPT) {
        return new WP_Error('not_found', 'Заявка не найдена', ['status' => 404]);
    }

    $body = $request->get_json_params();
    $notes = sanitize_textarea_field((string) ($body['notes'] ?? ''));
    update_post_meta($lead_id, '_lk_notes', $notes);

    return rest_ensure_response(lktech_crm_lead_to_array($lead_id));
}

function lktech_crm_rest_delete_lead(WP_REST_Request $request)
{
    $lead_id = (int) $request['id'];
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== LKTECH_CRM_CPT) {
        return new WP_Error('not_found', 'Заявка не найдена', ['status' => 404]);
    }

    $deleted = wp_delete_post($lead_id, true);
    if (!$deleted) {
        return new WP_Error('delete_failed', 'Не удалось удалить заявку', ['status' => 500]);
    }

    return rest_ensure_response(['ok' => true, 'id' => $lead_id]);
}

function lktech_crm_rest_list_comments(WP_REST_Request $request)
{
    $lead_id = (int) $request['id'];
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== LKTECH_CRM_CPT) {
        return new WP_Error('not_found', 'Заявка не найдена', ['status' => 404]);
    }

    return rest_ensure_response(['items' => lktech_crm_get_comments($lead_id)]);
}

function lktech_crm_rest_add_comment(WP_REST_Request $request)
{
    $lead_id = (int) $request['id'];
    $lead = get_post($lead_id);
    if (!$lead || $lead->post_type !== LKTECH_CRM_CPT) {
        return new WP_Error('not_found', 'Заявка не найдена', ['status' => 404]);
    }

    $body = $request->get_json_params();
    $item = lktech_crm_add_comment($lead_id, (string) ($body['text'] ?? ''));
    if (is_wp_error($item)) {
        return $item;
    }

    return rest_ensure_response([
        'ok'       => true,
        'comment'  => $item,
        'comments' => lktech_crm_get_comments($lead_id),
        'lead'     => lktech_crm_lead_to_array($lead_id),
    ]);
}
