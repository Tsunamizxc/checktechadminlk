<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, string>
 */
function lktech_crm_type_labels(): array
{
    return [
        'contact'   => 'Обращение',
        'callback'  => 'Обратный звонок',
        'osago'     => 'ОСАГО',
        'kasko'     => 'КАСКО',
        'tehosmotr' => 'Техосмотр',
        'dublikat'  => 'Дубликат ДК',
        'check'     => 'Проверка техосмотра',
        'dkp'       => 'Договор купли-продажи',
        'site'      => 'Заявка с сайта',
        'other'     => 'Другое',
    ];
}

/**
 * @return array<string, string>
 */
function lktech_crm_status_labels(): array
{
    return [
        'new'         => 'Новая',
        'in_progress' => 'В работе',
        'done'        => 'Закрыта',
        'cancelled'   => 'Отменена',
    ];
}

/**
 * @return array<string, string>
 */
function lktech_crm_source_labels(): array
{
    return [
        'site'     => 'Сайт',
        'telegram' => 'Telegram',
        'manual'   => 'Вручную',
    ];
}

function lktech_crm_type_label(string $type): string
{
    $labels = lktech_crm_type_labels();

    return $labels[$type] ?? $type;
}

function lktech_crm_status_label(string $status): string
{
    $labels = lktech_crm_status_labels();

    return $labels[$status] ?? $status;
}

function lktech_crm_source_label(string $source): string
{
    $labels = lktech_crm_source_labels();

    return $labels[$source] ?? $source;
}

/**
 * Кастомный select на разметке (не native).
 *
 * @param array<string, string> $options value => label
 */
function lktech_crm_render_select(string $name, array $options, string $placeholder = '', string $selected = '', string $extra_class = ''): void
{
    $current_label = $placeholder !== '' ? $placeholder : 'Выберите';
    if ($selected !== '' && isset($options[$selected])) {
        $current_label = $options[$selected];
    } elseif ($selected === '' && $placeholder !== '') {
        $current_label = $placeholder;
    }

    $class = trim('lk-select ' . $extra_class);
    ?>
    <div class="<?php echo esc_attr($class); ?>" data-lk-select data-name="<?php echo esc_attr($name); ?>">
      <button type="button" class="lk-select-trigger" aria-haspopup="listbox" aria-expanded="false">
        <span class="lk-select-value" data-lk-select-label><?php echo esc_html($current_label); ?></span>
        <span class="lk-select-caret" aria-hidden="true"></span>
      </button>
      <ul class="lk-select-menu" role="listbox" hidden>
        <?php if ($placeholder !== '') : ?>
          <li role="option" data-value="" <?php echo $selected === '' ? 'aria-selected="true"' : ''; ?>><?php echo esc_html($placeholder); ?></li>
        <?php endif; ?>
        <?php foreach ($options as $value => $label) : ?>
          <li role="option" data-value="<?php echo esc_attr((string) $value); ?>" <?php echo (string) $value === $selected ? 'aria-selected="true"' : ''; ?>><?php echo esc_html($label); ?></li>
        <?php endforeach; ?>
      </ul>
      <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($selected); ?>" data-lk-select-input <?php echo str_starts_with($name, 'filter_') ? 'data-filter="' . esc_attr(substr($name, 7)) . '"' : ''; ?>>
    </div>
    <?php
}

function lktech_crm_api_secret(): string
{
    $secret = (string) get_option('lktech_crm_api_secret', '');
    if ($secret === '') {
        $secret = wp_generate_password(40, false, false);
        update_option('lktech_crm_api_secret', $secret);
    }

    return $secret;
}

function lktech_crm_user_can_access(?int $user_id = null): bool
{
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) {
        return false;
    }

    return user_can($user_id, LKTECH_CRM_CAP) || user_can($user_id, 'manage_options');
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function lktech_crm_normalize_payload(array $data): array
{
    $type = sanitize_key((string) ($data['type'] ?? $data['form_type'] ?? $data['service'] ?? 'contact'));
    if (!isset(lktech_crm_type_labels()[$type])) {
        $type = 'other';
    }

    $source = sanitize_key((string) ($data['source'] ?? 'site'));
    if (!isset(lktech_crm_source_labels()[$source])) {
        $source = 'site';
    }

    $fields = [];
    if (!empty($data['fields']) && is_array($data['fields'])) {
        foreach ($data['fields'] as $key => $value) {
            $label = sanitize_text_field((string) $key);
            $text = sanitize_textarea_field(wp_strip_all_tags((string) $value));
            if ($label !== '' && $text !== '') {
                $fields[$label] = $text;
            }
        }
    }

    $name = sanitize_text_field((string) ($data['name'] ?? $fields['Имя'] ?? $fields['name'] ?? ''));
    $phone = sanitize_text_field((string) ($data['phone'] ?? $fields['Телефон'] ?? $fields['phone'] ?? ''));
    $email = sanitize_email((string) ($data['email'] ?? $fields['Email'] ?? $fields['email'] ?? ''));
    $city = sanitize_text_field((string) ($data['city'] ?? $fields['Город'] ?? $fields['city'] ?? ''));
    $telegram = sanitize_text_field(ltrim((string) ($data['telegram'] ?? $data['username'] ?? ''), '@'));

    return [
        'type'         => $type,
        'source'       => $source,
        'status'       => 'new',
        'subject'      => sanitize_text_field((string) ($data['subject'] ?? '')),
        'name'         => $name,
        'phone'        => $phone,
        'email'        => $email,
        'city'         => $city,
        'telegram'     => $telegram,
        'telegram_id'  => preg_replace('/\D+/', '', (string) ($data['telegram_id'] ?? $data['chatId'] ?? '')),
        'car_brand'    => sanitize_text_field((string) ($data['car_brand'] ?? $data['brand'] ?? $data['vehicle_make'] ?? '')),
        'car_model'    => sanitize_text_field((string) ($data['car_model'] ?? $data['model'] ?? $data['vehicle_model'] ?? '')),
        'car_year'     => sanitize_text_field((string) ($data['car_year'] ?? $data['vehicle_year'] ?? '')),
        'plate'        => sanitize_text_field((string) ($data['plate'] ?? $data['vehicle_plate'] ?? '')),
        'vin'          => sanitize_text_field((string) ($data['vin'] ?? $data['vehicle_vin'] ?? $data['query'] ?? '')),
        'message'      => sanitize_textarea_field((string) ($data['message'] ?? '')),
        'page_url'     => esc_url_raw((string) ($data['page'] ?? $data['page_url'] ?? '')),
        'external_id'  => sanitize_text_field((string) ($data['external_id'] ?? $data['id'] ?? $data['order_id'] ?? '')),
        'amount'       => (int) ($data['amount'] ?? 0),
        'order_status' => sanitize_key((string) ($data['order_status'] ?? $data['status'] ?? '')),
        'fields'       => $fields,
        'raw'          => $data,
    ];
}

/**
 * @param array<string, mixed> $data
 */
function lktech_crm_store_lead(array $data): int
{
    $payload = lktech_crm_normalize_payload($data);

    if ($payload['external_id'] !== '') {
        $existing = get_posts([
            'post_type'      => LKTECH_CRM_CPT,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_lk_external_id',
            'meta_value'     => $payload['external_id'],
        ]);
        if ($existing) {
            return (int) $existing[0];
        }
    }

    $type_label = lktech_crm_type_label($payload['type']);
    $title_parts = array_filter([
        $payload['subject'] ?: $type_label,
        $payload['name'] ?: null,
        $payload['phone'] ?: null,
        $payload['telegram'] ? '@' . $payload['telegram'] : null,
    ]);
    $title = implode(' — ', $title_parts);
    if ($title === '') {
        $title = 'Заявка ' . wp_date('d.m.Y H:i');
    }

    $post_id = wp_insert_post([
        'post_type'    => LKTECH_CRM_CPT,
        'post_status'  => 'publish',
        'post_title'   => $title,
        'post_content' => $payload['message'],
    ], true);

    if (is_wp_error($post_id) || !$post_id) {
        return 0;
    }

    $meta = [
        '_lk_type'         => $payload['type'],
        '_lk_source'       => $payload['source'],
        '_lk_status'       => 'new',
        '_lk_subject'      => $payload['subject'],
        '_lk_name'         => $payload['name'],
        '_lk_phone'        => $payload['phone'],
        '_lk_email'        => $payload['email'],
        '_lk_city'         => $payload['city'],
        '_lk_telegram'     => $payload['telegram'],
        '_lk_telegram_id'  => $payload['telegram_id'],
        '_lk_car_brand'    => $payload['car_brand'],
        '_lk_car_model'    => $payload['car_model'],
        '_lk_car_year'     => $payload['car_year'],
        '_lk_plate'        => $payload['plate'],
        '_lk_vin'          => $payload['vin'],
        '_lk_page_url'     => $payload['page_url'],
        '_lk_external_id'  => $payload['external_id'],
        '_lk_amount'       => $payload['amount'],
        '_lk_order_status' => $payload['order_status'],
        '_lk_fields'       => $payload['fields'],
        '_lk_notes'        => '',
        '_lk_assigned'     => 0,
    ];

    foreach ($meta as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    return (int) $post_id;
}

/**
 * @return array<string, mixed>
 */
function lktech_crm_lead_to_array(int $lead_id): array
{
    $post = get_post($lead_id);
    if (!$post || $post->post_type !== LKTECH_CRM_CPT) {
        return [];
    }

    $status = (string) get_post_meta($lead_id, '_lk_status', true);
    $type = (string) get_post_meta($lead_id, '_lk_type', true);
    $source = (string) get_post_meta($lead_id, '_lk_source', true);
    $assigned = (int) get_post_meta($lead_id, '_lk_assigned', true);
    $fields = get_post_meta($lead_id, '_lk_fields', true);
    if (!is_array($fields)) {
        $fields = [];
    }
    $comments_list = lktech_crm_get_comments($lead_id);

    return [
        'id'            => $lead_id,
        'title'         => get_the_title($lead_id),
        'type'          => $type,
        'type_label'    => lktech_crm_type_label($type),
        'source'        => $source,
        'source_label'  => lktech_crm_source_label($source),
        'status'        => $status,
        'status_label'  => lktech_crm_status_label($status),
        'subject'       => (string) get_post_meta($lead_id, '_lk_subject', true),
        'name'          => (string) get_post_meta($lead_id, '_lk_name', true),
        'phone'         => (string) get_post_meta($lead_id, '_lk_phone', true),
        'email'         => (string) get_post_meta($lead_id, '_lk_email', true),
        'city'          => (string) get_post_meta($lead_id, '_lk_city', true),
        'telegram'      => (string) get_post_meta($lead_id, '_lk_telegram', true),
        'telegram_id'   => (string) get_post_meta($lead_id, '_lk_telegram_id', true),
        'car_brand'     => (string) get_post_meta($lead_id, '_lk_car_brand', true),
        'car_model'     => (string) get_post_meta($lead_id, '_lk_car_model', true),
        'car_year'      => (string) get_post_meta($lead_id, '_lk_car_year', true),
        'plate'         => (string) get_post_meta($lead_id, '_lk_plate', true),
        'vin'           => (string) get_post_meta($lead_id, '_lk_vin', true),
        'message'       => (string) $post->post_content,
        'page_url'      => (string) get_post_meta($lead_id, '_lk_page_url', true),
        'external_id'   => (string) get_post_meta($lead_id, '_lk_external_id', true),
        'amount'        => (int) get_post_meta($lead_id, '_lk_amount', true),
        'order_status'  => (string) get_post_meta($lead_id, '_lk_order_status', true),
        'fields'         => $fields,
        'notes'          => (string) get_post_meta($lead_id, '_lk_notes', true),
        'comments'       => $comments_list,
        'comments_count' => count($comments_list),
        'assigned'       => $assigned,
        'assigned_name'  => $assigned ? (string) get_userdata($assigned)?->display_name : '',
        'created_at'     => get_post_time('c', true, $lead_id),
        'created_human'  => get_the_date('d.m.Y H:i', $lead_id),
        'permalink'      => home_url('/lead/' . $lead_id . '/'),
        'preset_message' => lktech_crm_preset_message($lead_id),
        'contacts'       => lktech_crm_contact_links($lead_id),
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function lktech_crm_get_comments(int $lead_id): array
{
    $comments = get_post_meta($lead_id, '_lk_comments', true);
    if (!is_array($comments)) {
        return [];
    }

    return array_values(array_filter($comments, static function ($item) {
        return is_array($item) && trim((string) ($item['text'] ?? '')) !== '';
    }));
}

/**
 * @return array<string, mixed>|WP_Error
 */
function lktech_crm_add_comment(int $lead_id, string $text)
{
    $text = sanitize_textarea_field(trim($text));
    if ($text === '') {
        return new WP_Error('empty_comment', 'Напишите текст комментария', ['status' => 400]);
    }

    $user = wp_get_current_user();
    $comments = lktech_crm_get_comments($lead_id);
    $item = [
        'id'         => uniqid('c_', true),
        'text'       => $text,
        'user_id'    => (int) $user->ID,
        'user_name'  => (string) ($user->display_name ?: $user->user_login),
        'created_at' => gmdate('c'),
        'created_human' => wp_date('d.m.Y H:i'),
    ];
    $comments[] = $item;
    update_post_meta($lead_id, '_lk_comments', $comments);

    return $item;
}

function lktech_crm_preset_message(int $lead_id): string
{
    $name = trim((string) get_post_meta($lead_id, '_lk_name', true));
    $type = lktech_crm_type_label((string) get_post_meta($lead_id, '_lk_type', true));
    $hello = $name !== '' ? (', ' . $name) : '';

    return sprintf(
        'Здравствуйте%s! Это LK Tech по вашей заявке «%s». Чем можем помочь?',
        $hello,
        $type !== '' ? $type : 'с сайта'
    );
}

/**
 * @return array<string, array<string, mixed>>
 */
function lktech_crm_contact_links(int $lead_id): array
{
    $phone_raw = (string) get_post_meta($lead_id, '_lk_phone', true);
    $phone = preg_replace('/\D+/', '', $phone_raw) ?: '';
    if (strlen($phone) === 11 && $phone[0] === '8') {
        $phone = '7' . substr($phone, 1);
    }
    $telegram = ltrim((string) get_post_meta($lead_id, '_lk_telegram', true), '@');
    $telegram = preg_replace('/[^a-zA-Z0-9_]/', '', $telegram) ?: '';
    $message = lktech_crm_preset_message($lead_id);
    $encoded = rawurlencode($message);

    $tel = $phone !== '' ? 'tel:+' . $phone : '';
    $sms = $phone !== '' ? 'sms:+' . $phone . '?body=' . $encoded : '';
    $tg = '';
    if ($telegram !== '') {
        $tg = 'https://t.me/' . $telegram;
    } elseif ($phone !== '') {
        $tg = 'https://t.me/+' . $phone;
    }
    $tg_share = 'https://t.me/share/url?url=&text=' . $encoded;
    $max = 'https://max.ru/:share?text=' . $encoded;

    return [
        'phone' => [
            'available' => $phone !== '',
            'value'     => $phone_raw,
            'href'      => $tel,
            'sms'       => $sms,
            'label'     => 'Телефон',
        ],
        'telegram' => [
            'available' => $tg !== '',
            'value'     => $telegram !== '' ? '@' . $telegram : $phone_raw,
            'href'      => $tg,
            'share'     => $tg_share,
            'label'     => 'Telegram',
        ],
        'max' => [
            'available' => true,
            'value'     => $phone_raw ?: ($telegram !== '' ? '@' . $telegram : ''),
            'href'      => $max,
            'label'     => 'MAX',
        ],
        'message' => $message,
    ];
}

/**
 * @return array{new:int,in_progress:int,done:int,cancelled:int,total:int,today:int}
 */
function lktech_crm_stats(): array
{
    $counts = [
        'new'         => 0,
        'in_progress' => 0,
        'done'        => 0,
        'cancelled'   => 0,
        'total'       => 0,
        'today'       => 0,
    ];

    foreach (['new', 'in_progress', 'done', 'cancelled'] as $status) {
        $q = new WP_Query([
            'post_type'      => LKTECH_CRM_CPT,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_lk_status',
            'meta_value'     => $status,
            'no_found_rows'  => false,
        ]);
        $counts[$status] = (int) $q->found_posts;
    }

    $total = new WP_Query([
        'post_type'      => LKTECH_CRM_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ]);
    $counts['total'] = (int) $total->found_posts;

    $today = new WP_Query([
        'post_type'      => LKTECH_CRM_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'date_query'     => [
            [
                'after'     => 'today',
                'inclusive' => true,
            ],
        ],
        'no_found_rows'  => false,
    ]);
    $counts['today'] = (int) $today->found_posts;

    return $counts;
}
