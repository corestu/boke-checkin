<?php
// 仅允许由 WordPress 的插件卸载流程执行。
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

wp_clear_scheduled_hook('boke_checkin_daily_checkin');
delete_option('boke_checkin_settings');
delete_option('boke_checkin_status');
