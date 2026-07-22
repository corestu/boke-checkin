<?php
// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 邮件通知
 */
class Boke_Checkin_Mail {

    /**
     * 发送失败通知
     *
     * @param string $to      收件人邮箱
     * @param string $message 错误信息
     */
    public function send_failure_notification($to, $message) {
        $site_name  = get_bloginfo('name');
        $admin_url  = admin_url('options-general.php?page=boke-checkin');
        $timestamp  = current_time('mysql');

        $subject = sprintf('[%s] Bo.ke 签到失败通知', $site_name);

        $body = $this->get_email_template([
            'site_name'  => $site_name,
            'message'    => $message,
            'timestamp'  => $timestamp,
            'admin_url'  => $admin_url,
        ]);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, get_option('admin_email')),
        ];

        $sent = wp_mail($to, $subject, $body, $headers);

        // 记录邮件发送日志
        $this->log_email($to, $subject, $sent);

        return $sent;
    }

    /**
     * 获取邮件模板
     */
    private function get_email_template($data) {
        $template = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #c64545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #eee; padding: 10px 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
        .btn { display: inline-block; background-color: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; margin-top: 15px; }
        .error-box { background-color: #ffebee; border-left: 4px solid #c64545; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bo.ke 签到失败通知</h1>
        </div>
        <div class="content">
            <p>您好，</p>
            <p>您的网站 <strong>{$data['site_name']}</strong> 的 Bo.ke 每日签到任务执行失败。</p>

            <div class="error-box">
                <strong>错误信息：</strong><br>
                {$data['message']}
            </div>

            <p><strong>发生时间：</strong> {$data['timestamp']}</p>

            <p>请及时检查并更新 Cookie 配置。</p>

            <a href="{$data['admin_url']}" class="btn">前往设置</a>
        </div>
        <div class="footer">
            <p>此邮件由 Bo.ke 签到助手自动发送</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $template;
    }

    /**
     * 记录邮件发送日志
     */
    private function log_email($to, $subject, $success) {
        $log_entry = sprintf(
            "[%s] Email to: %s | Subject: %s | Status: %s\n",
            current_time('mysql'),
            $to,
            $subject,
            $success ? 'SENT' : 'FAILED'
        );

        $upload_dir = wp_upload_dir();
        $log_file   = $upload_dir['basedir'] . '/boke-checkin/email.log';

        $log_dir = dirname($log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * 发送测试邮件
     *
     * @param string $to 收件人邮箱
     */
    public function send_test_email($to) {
        $site_name = get_bloginfo('name');

        $subject = sprintf('[%s] Bo.ke 签到助手 - 测试邮件', $site_name);

        $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #5db872; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background-color: #eee; padding: 10px 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>测试邮件</h1>
        </div>
        <div class="content">
            <p>您好，</p>
            <p>这是来自 <strong>{$site_name}</strong> 的 Bo.ke 签到助手的测试邮件。</p>
            <p>如果您收到此邮件，说明邮件通知功能正常工作。</p>
            <p><strong>发送时间：</strong> {$data['timestamp']}</p>
        </div>
        <div class="footer">
            <p>此邮件由 Bo.ke 签到助手自动发送</p>
        </div>
    </div>
</body>
</html>
HTML;

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, get_option('admin_email')),
        ];

        return wp_mail($to, $subject, $body, $headers);
    }
}
