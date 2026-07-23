<?php
// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 签到核心逻辑
 */
class Boke_Checkin_Core {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * 执行签到
     *
     * @return array 签到结果 ['success' => bool, 'message' => string]
     */
    public function do_checkin() {
        $settings = $this->get_settings();

        // 验证配置
        if (empty($settings['boke_session']) || empty($settings['boke_csrf'])) {
            return [
                'success' => false,
                'message' => '请先配置 Cookie 信息',
            ];
        }

        // 构建请求
        $url = 'https://bo.ke/dashboard/checkin/';
        $headers = [
            'accept'       => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'content-type' => 'application/x-www-form-urlencoded',
            'cookie'       => 'boke_session=' . $settings['boke_session'] . '; boke_csrf=' . $settings['boke_csrf'],
            'origin'       => 'https://bo.ke',
            'referer'      => 'https://bo.ke/dashboard/',
            'user-agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        $body = '_csrf=' . $settings['boke_csrf'];

        // 发送请求
        $response = wp_remote_post($url, [
            'headers'     => $headers,
            'body'        => $body,
            'timeout'     => 30,
            'redirection' => 0,
            'sslverify'   => true,
        ]);

        // 处理响应
        if (is_wp_error($response)) {
            $this->update_status(false, $response->get_error_message());
            return [
                'success' => false,
                'message' => '请求失败: ' . $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 302) {
            $this->update_status(true);
            return [
                'success' => true,
                'message' => '签到成功',
            ];
        } else {
            $this->update_status(false, "HTTP {$status_code}");
            return [
                'success' => false,
                'message' => "签到失败 (HTTP {$status_code})",
            ];
        }
    }

    /**
     * 获取设置
     */
    public function get_settings() {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        return wp_parse_args($settings, [
            'boke_session'         => '',
            'boke_csrf'            => '',
            'cron_hour'            => 9,
            'cron_minute'          => 0,
            'admin_email'          => get_option('admin_email'),
            'enable_email'         => true,
            'last_checkin_time'    => '',
            'last_checkin_status'  => '',
            'consecutive_days'     => 0,
        ]);
    }

    /**
     * 更新签到状态
     *
     * @param bool   $success  是否成功
     * @param string $message  错误信息
     */
    private function update_status($success, $message = '') {
        $status = get_option(BOKE_CHECKIN_STATUS_OPTION_KEY, []);
        if (!is_array($status)) {
            $status = [];
        }

        $status['last_checkin_time']   = current_time('mysql');
        $status['last_checkin_status'] = $success ? 'success' : 'failed';

        if ($success) {
            $status['consecutive_days'] = intval($status['consecutive_days'] ?? 0) + 1;
        } else {
            $status['consecutive_days'] = 0;
        }

        update_option(BOKE_CHECKIN_STATUS_OPTION_KEY, $status);
    }

    /**
     * 保存设置
     *
     * @param array $new_settings 新设置
     */
    public function save_settings($new_settings) {
        $settings = $this->get_settings();

        // 合并设置
        $settings = array_merge($settings, $new_settings);

        // 清理数据
        $settings['boke_session'] = sanitize_text_field($settings['boke_session']);
        $settings['boke_csrf']    = sanitize_text_field($settings['boke_csrf']);
        $settings['cron_hour']    = intval($settings['cron_hour']);
        $settings['cron_minute']  = intval($settings['cron_minute']);
        $settings['admin_email']  = sanitize_email($settings['admin_email']);
        $settings['enable_email'] = boolval($settings['enable_email']);

        update_option(BOKE_CHECKIN_OPTION_KEY, $settings);

        // 更新定时任务
        $plugin = Boke_Checkin_Plugin::get_instance();
        $plugin->schedule_cron();

        return $settings;
    }

    /**
     * 获取上次签到信息
     */
    public function get_last_checkin_info() {
        $settings = $this->get_settings();
        $status = get_option(BOKE_CHECKIN_STATUS_OPTION_KEY, []);

        // 兼容插件升级前保存在设置选项中的状态数据。
        if (!is_array($status) || empty($status['last_checkin_status'])) {
            $status = $settings;
        }

        return [
            'time'    => !empty($status['last_checkin_time']) ? $status['last_checkin_time'] : '从未签到',
            'status'  => !empty($status['last_checkin_status']) ? $status['last_checkin_status'] : 'unknown',
            'days'    => $status['consecutive_days'] ?? 0,
        ];
    }
}
