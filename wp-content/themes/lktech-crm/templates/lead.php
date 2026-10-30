<?php
if (!defined('ABSPATH')) {
    exit;
}
$lead_id = (int) get_query_var('lk_lead_id');
wp_safe_redirect(add_query_arg('lead', $lead_id, home_url('/')));
exit;
