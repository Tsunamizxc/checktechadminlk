<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', static function () {
    add_options_page(
        'LK Tech CRM',
        'LK Tech CRM',
        'manage_options',
        'lktech-crm-settings',
        'lktech_crm_render_settings_page'
    );
});

function lktech_crm_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['lktech_crm_regenerate']) && check_admin_referer('lktech_crm_settings')) {
        update_option('lktech_crm_api_secret', wp_generate_password(40, false, false));
        echo '<div class="notice notice-success"><p>Секрет API обновлён.</p></div>';
    }

    $secret = lktech_crm_api_secret();
    $endpoint = rest_url('lktech/v1/leads');
    ?>
    <div class="wrap">
        <h1>LK Tech CRM — интеграции</h1>
        <p>Сюда сайт и Telegram-бот отправляют заявки.</p>
        <table class="form-table" role="presentation">
            <tr>
                <th>Endpoint</th>
                <td><code><?php echo esc_html($endpoint); ?></code></td>
            </tr>
            <tr>
                <th>Заголовок</th>
                <td><code>X-CRM-Secret</code></td>
            </tr>
            <tr>
                <th>Секрет</th>
                <td><code><?php echo esc_html($secret); ?></code></td>
            </tr>
            <tr>
                <th>Кабинет</th>
                <td><a href="<?php echo esc_url(home_url('/')); ?>" target="_blank"><?php echo esc_html(home_url('/')); ?></a></td>
            </tr>
        </table>
        <form method="post">
            <?php wp_nonce_field('lktech_crm_settings'); ?>
            <button type="submit" name="lktech_crm_regenerate" class="button" value="1">Сгенерировать новый секрет</button>
        </form>
    </div>
    <?php
}
