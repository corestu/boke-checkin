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
     * @param bool $is_cron 是否是定时任务调用
     * @return array 签到结果 ['success' => bool, 'message' => string]
     */
    public function do_checkin($is_cron = false) {
        $settings = $this->get_settings();
        $status = get_option(BOKE_CHECKIN_STATUS_OPTION_KEY, []);

        // 验证配置
        if (empty($settings['boke_session']) || empty($settings['boke_csrf'])) {
            return [
                'success' => false,
                'message' => '请先配置 Cookie 信息',
            ];
        }

        // 定时任务时检查是否需要跳过签到
        if ($is_cron) {
            $skip_reason = $this->should_skip_checkin($settings, $status);
            if ($skip_reason) {
                $this->update_status('skipped', $skip_reason);
                return [
                    'success' => true,
                    'message' => "跳过签到：{$skip_reason}",
                ];
            }
        }

        // 检查上次签到失败的遗留状态：若失败当天未补签，则重置连续天数
        $this->maybe_reset_for_missed_recovery();

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
        $redirect_url = wp_remote_retrieve_header($response, 'location');

        // 成功签到会重定向到 /dashboard/，失败则重定向到 /login/
        if ($status_code === 302 && strpos($redirect_url, '/dashboard/') !== false) {
            $this->update_status(true);
            return [
                'success' => true,
                'message' => '签到成功',
            ];
        } else {
            $error_msg = $status_code === 302 ? 'Cookie已失效' : "HTTP {$status_code}";
            $this->update_status(false, $error_msg);
            return [
                'success' => false,
                'message' => "签到失败 ({$error_msg})",
            ];
        }
    }

    /**
     * 检查是否应该跳过签到
     *
     * @param array $settings 设置
     * @param array $status 签到状态
     * @return string|false 跳过原因，不跳过返回false
     */
    private function should_skip_checkin($settings, $status) {
        // 检查自动跳过（连续7天隔一天）
        if (!empty($settings['skip_after_7days'])) {
            $consecutive_days = intval($status['consecutive_days'] ?? 0);
            // 当连续签到天数是7的倍数（7, 14, 21...）时跳过
            if ($consecutive_days > 0 && $consecutive_days % 7 === 0) {
                return "连续签到{$consecutive_days}天，休息一天";
            }
        }

        return false;
    }

    /**
     * 获取设置
     */
    public function get_settings() {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        return wp_parse_args($settings, [
            'boke_session'        => '',
            'boke_csrf'           => '',
            'cron_hour'           => 9,
            'cron_minute'         => 0,
            'admin_email'         => get_option('admin_email'),
            'enable_email'        => true,
            'skip_after_7days'    => 0,
        ]);
    }

    /**
     * 更新签到状态
     *
     * @param bool|string $success 是否成功，或 'skipped' 表示跳过
     * @param string $message 信息
     */
    private function update_status($success, $message = '') {
        $status = get_option(BOKE_CHECKIN_STATUS_OPTION_KEY, []);
        if (!is_array($status)) {
            $status = [];
        }

        $status['last_checkin_time'] = current_time('mysql');

        if ($success === 'skipped') {
            $status['last_checkin_status'] = 'skipped';
            $status['last_checkin_message'] = $message;
            // 跳过休息后重置连续天数，下一周期从 1 重新累加，
            // 否则 consecutive_days 会一直卡在 7 的倍数导致每天都跳过
            $status['consecutive_days'] = 0;
        } elseif ($success) {
            $status['last_checkin_status'] = 'success';
            $status['last_checkin_message'] = '';
            $status['consecutive_days'] = intval($status['consecutive_days'] ?? 0) + 1;
        } else {
            $status['last_checkin_status'] = 'failed';
            $status['last_checkin_message'] = $message;
            // 不立即重置 consecutive_days，给用户当天"立即签到"补签的机会
            // 若当天未补签，下次签到时由 maybe_reset_for_missed_recovery 重置
        }

        update_option(BOKE_CHECKIN_STATUS_OPTION_KEY, $status);
    }

    /**
     * 检查上次签到失败的遗留状态
     *
     * 签到失败时不立即重置连续天数，给用户当天"立即签到"补签的机会。
     * 若上次失败日期不是今天（即当天未补签），则重置连续天数为 0。
     */
    private function maybe_reset_for_missed_recovery() {
        $status = get_option(BOKE_CHECKIN_STATUS_OPTION_KEY, []);
        if (!is_array($status)) {
            return;
        }
        if (($status['last_checkin_status'] ?? '') !== 'failed') {
            return;
        }
        if (empty($status['last_checkin_time'])) {
            return;
        }

        $last_date = substr($status['last_checkin_time'], 0, 10); // 'Y-m-d'
        $today     = current_time('Y-m-d');

        if ($last_date !== $today) {
            $status['consecutive_days'] = 0;
            update_option(BOKE_CHECKIN_STATUS_OPTION_KEY, $status);
        }
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
            'message' => $status['last_checkin_message'] ?? '',
        ];
    }
}
