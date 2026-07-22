<?php
/**
 * Plugin Name: Bo.ke 签到助手
 * Plugin URI: https://bo.ke
 * Description: 自动化每日签到 bo.ke 博客大联盟，支持WP-Cron定时任务和邮件通知
 * Version: 1.0.0
 * Author: 摸鱼大王
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: boke-checkin
 * Domain Path: /languages
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 插件常量
define('BOKE_CHECKIN_VERSION', '1.0.0');
define('BOKE_CHECKIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BOKE_CHECKIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BOKE_CHECKIN_OPTION_KEY', 'boke_checkin_settings');
define('BOKE_CHECKIN_CRON_HOOK', 'boke_checkin_daily_checkin');

// 直接加载文件（简单可靠）
require_once BOKE_CHECKIN_PLUGIN_DIR . 'includes/class-boke-checkin-core.php';
require_once BOKE_CHECKIN_PLUGIN_DIR . 'includes/class-boke-checkin-admin.php';
require_once BOKE_CHECKIN_PLUGIN_DIR . 'includes/class-boke-checkin-cron.php';
require_once BOKE_CHECKIN_PLUGIN_DIR . 'includes/class-boke-checkin-mail.php';

/**
 * 主插件类
 */
final class Boke_Checkin_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
    }

    /**
     * 插件激活
     */
    public function activate() {
        // 设置默认选项
        $defaults = [
            'boke_session'     => '',
            'boke_csrf'        => '',
            'cron_hour'        => 9,
            'cron_minute'      => 0,
            'admin_email'      => get_option('admin_email'),
            'enable_email'     => true,
            'last_checkin_time'=> '',
            'last_checkin_status' => '',
            'consecutive_days' => 0,
        ];

        if (!get_option(BOKE_CHECKIN_OPTION_KEY)) {
            add_option(BOKE_CHECKIN_OPTION_KEY, $defaults);
        }

        // 注册定时任务
        $this->schedule_cron();

        // 刷新重写规则
        flush_rewrite_rules();
    }

    /**
     * 插件停用
     */
    public function deactivate() {
        // 删除定时任务
        wp_clear_scheduled_hook(BOKE_CHECKIN_CRON_HOOK);

        // 刷新重写规则
        flush_rewrite_rules();
    }

    /**
     * 初始化
     */
    public function init() {
        // 加载文本域
        load_plugin_textdomain('boke-checkin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * 插件加载完成
     */
    public function plugins_loaded() {
        // 初始化各组件
        $admin = new Boke_Checkin_Admin();
        $cron = new Boke_Checkin_Cron();
        $mail = new Boke_Checkin_Mail();
    }

    /**
     * 安排定时任务
     */
    public function schedule_cron() {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        $hour = isset($settings['cron_hour']) ? intval($settings['cron_hour']) : 9;
        $minute = isset($settings['cron_minute']) ? intval($settings['cron_minute']) : 0;

        // 删除现有定时任务
        wp_clear_scheduled_hook(BOKE_CHECKIN_CRON_HOOK);

        // 使用WordPress时区计算下次执行时间
        $timezone = new DateTimeZone(wp_timezone_string());
        $now = new DateTime('now', $timezone);

        // 计算今天的目标时间
        $target = new DateTime('today', $timezone);
        $target->setTime($hour, $minute, 0);

        // 如果目标时间已过，设为明天
        if ($target <= $now) {
            $target->modify('+1 day');
        }

        // 转换为UTC时间戳
        $next_run = $target->getTimestamp();

        // 安排定时任务
        wp_schedule_event($next_run, 'daily', BOKE_CHECKIN_CRON_HOOK);
    }

    /**
     * 获取插件版本
     */
    public static function get_version() {
        return BOKE_CHECKIN_VERSION;
    }
}

// 启动插件
Boke_Checkin_Plugin::get_instance();
