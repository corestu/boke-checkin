<?php
// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP-Cron 定时任务处理
 */
class Boke_Checkin_Cron {

    public function __construct() {
        add_action(BOKE_CHECKIN_CRON_HOOK, [$this, 'run_checkin']);
    }

    /**
     * 执行定时签到
     */
    public function run_checkin() {
        $core    = Boke_Checkin_Core::get_instance();
        $mail    = new Boke_Checkin_Mail();
        $result  = $core->do_checkin(true);  // 传递 is_cron=true

        // 记录日志
        $this->log_checkin($result);

        // 如果失败且启用了邮件通知，发送邮件
        if (!$result['success'] && $this->is_email_enabled()) {
            $settings   = Boke_Checkin_Core::get_instance()->get_settings();
            $admin_email = $settings['admin_email'];

            if (!empty($admin_email)) {
                $mail->send_failure_notification($admin_email, $result['message']);
            }
        }

        return $result;
    }

    /**
     * 记录签到日志
     */
    private function log_checkin($result) {
        $status = $result['success'] ? 'SUCCESS' : 'FAILED';

        // 检查是否是跳过签到
        if (strpos($result['message'], '跳过签到') === 0) {
            $status = 'SKIPPED';
        }

        $log_entry = sprintf(
            "[%s] %s - %s\n",
            current_time('mysql'),
            $status,
            $result['message']
        );

        $log_file = $this->get_log_file_path();

        // 确保目录存在
        $log_dir = dirname($log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // 写入日志
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

        // 限制日志文件大小（最多保留 1000 条）
        $this->trim_log($log_file, 1000);
    }

    /**
     * 获取日志文件路径
     */
    private function get_log_file_path() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/boke-checkin/checkin.log';
    }

    /**
     * 裁剪日志文件
     */
    private function trim_log($file, $max_lines) {
        if (!file_exists($file)) {
            return;
        }

        $lines = file($file);
        if (count($lines) > $max_lines) {
            $lines = array_slice($lines, -$max_lines);
            file_put_contents($file, implode('', $lines));
        }
    }

    /**
     * 检查是否启用邮件通知
     */
    private function is_email_enabled() {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        return !empty($settings['enable_email']);
    }

    /**
     * 获取下次执行时间
     */
    public function get_next_scheduled_time() {
        $timestamp = wp_next_scheduled(BOKE_CHECKIN_CRON_HOOK);
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        return '未安排';
    }

    /**
     * 获取日志内容
     *
     * @param int $lines 返回的行数
     */
    public function get_log($lines = 50) {
        $log_file = $this->get_log_file_path();

        if (!file_exists($log_file)) {
            return '暂无日志';
        }

        $all_lines = file($log_file);
        $recent_lines = array_slice($all_lines, -$lines);

        return implode('', $recent_lines);
    }

    /**
     * 清空日志
     */
    public function clear_log() {
        $log_file = $this->get_log_file_path();
        if (file_exists($log_file)) {
            unlink($log_file);
        }
    }
}

/**
 * 获取单例
 */
function cron() {
    return Cron::get_instance();
}
