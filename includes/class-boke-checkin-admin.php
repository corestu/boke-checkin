<?php
// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 管理界面
 */
class Boke_Checkin_Admin {

    private $plugin_name = 'boke-checkin';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_boke_checkin_now', [$this, 'ajax_checkin']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        add_options_page(
            'Bo.ke 签到助手',
            'Bo.ke 签到',
            'manage_options',
            $this->plugin_name,
            [$this, 'render_settings_page']
        );
    }

    /**
     * 注册设置
     */
    public function register_settings() {
        register_setting(BOKE_CHECKIN_OPTION_KEY, BOKE_CHECKIN_OPTION_KEY, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        // Cookie 设置部分 - 支持完整Cookie粘贴
        add_settings_section(
            'boke_cookie_section',
            'Cookie 配置（复制浏览器完整Cookie）',
            null,
            $this->plugin_name
        );

        add_settings_field(
            'full_cookie',
            '完整Cookie字符串',
            [$this, 'render_full_cookie_field'],
            $this->plugin_name,
            'boke_cookie_section',
            [
                'id'          => 'full_cookie',
                'description' => '从浏览器开发者工具复制完整的Cookie值（包含boke_session和boke_csrf）',
            ]
        );

        add_settings_field(
            'boke_session',
            'Session Cookie（可选）',
            [$this, 'render_text_field'],
            $this->plugin_name,
            'boke_cookie_section',
            [
                'id'          => 'boke_session',
                'description' => '如果上方自动解析失败，可手动填写',
            ]
        );

        add_settings_field(
            'boke_csrf',
            'CSRF Cookie（可选）',
            [$this, 'render_text_field'],
            $this->plugin_name,
            'boke_cookie_section',
            [
                'id'          => 'boke_csrf',
                'description' => '如果上方自动解析失败，可手动填写',
            ]
        );

        // 定时任务设置部分
        add_settings_section(
            'boke_cron_section',
            '定时任务配置',
            null,
            $this->plugin_name
        );

        add_settings_field(
            'cron_hour',
            '执行小时',
            [$this, 'render_select_field'],
            $this->plugin_name,
            'boke_cron_section',
            [
                'id'      => 'cron_hour',
                'options' => $this->get_hour_options(),
            ]
        );

        add_settings_field(
            'cron_minute',
            '执行分钟',
            [$this, 'render_select_field'],
            $this->plugin_name,
            'boke_cron_section',
            [
                'id'      => 'cron_minute',
                'options' => $this->get_minute_options(),
            ]
        );

        // 邮件通知设置部分
        add_settings_section(
            'boke_email_section',
            '邮件通知配置',
            null,
            $this->plugin_name
        );

        add_settings_field(
            'admin_email',
            '通知邮箱',
            [$this, 'render_email_field'],
            $this->plugin_name,
            'boke_email_section',
            [
                'id'          => 'admin_email',
                'description' => '签到失败时发送通知的邮箱地址',
            ]
        );

        add_settings_field(
            'enable_email',
            '启用邮件通知',
            [$this, 'render_checkbox_field'],
            $this->plugin_name,
            'boke_email_section',
            [
                'id'          => 'enable_email',
                'description' => '签到失败时发送邮件通知',
            ]
        );
    }

    /**
     * 清理设置
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        // 处理完整Cookie自动解析
        $full_cookie = sanitize_text_field($input['full_cookie'] ?? '');
        if (!empty($full_cookie)) {
            // 自动解析boke_session
            if (preg_match('/boke_session=([^;]+)/', $full_cookie, $matches)) {
                $sanitized['boke_session'] = $matches[1];
            }

            // 自动解析boke_csrf
            if (preg_match('/boke_csrf=([^;]+)/', $full_cookie, $matches)) {
                $sanitized['boke_csrf'] = $matches[1];
            }
        }

        // 如果手动填写了，使用手动值（优先级更高）
        if (!empty($input['boke_session'])) {
            $sanitized['boke_session'] = sanitize_text_field($input['boke_session']);
        }
        if (!empty($input['boke_csrf'])) {
            $sanitized['boke_csrf'] = sanitize_text_field($input['boke_csrf']);
        }

        // 保存完整Cookie供显示
        $sanitized['full_cookie'] = $full_cookie;

        $sanitized['cron_hour']    = intval($input['cron_hour'] ?? 9);
        $sanitized['cron_minute']  = intval($input['cron_minute'] ?? 0);
        $sanitized['admin_email']  = sanitize_email($input['admin_email'] ?? get_option('admin_email'));
        $sanitized['enable_email'] = isset($input['enable_email']) ? 1 : 0;

        // 保留其他字段
        $existing = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        $sanitized['last_checkin_time']    = $existing['last_checkin_time'] ?? '';
        $sanitized['last_checkin_status']  = $existing['last_checkin_status'] ?? '';
        $sanitized['consecutive_days']     = $existing['consecutive_days'] ?? 0;

        return $sanitized;
    }

    /**
     * 加载管理脚本和样式
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_' . $this->plugin_name) {
            return;
        }

        wp_enqueue_style(
            'boke-checkin-admin',
            BOKE_CHECKIN_PLUGIN_URL . 'admin/css/admin.css',
            [],
            BOKE_CHECKIN_VERSION
        );

        wp_enqueue_script(
            'boke-checkin-admin',
            BOKE_CHECKIN_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery'],
            BOKE_CHECKIN_VERSION,
            true
        );

        wp_localize_script('boke-checkin-admin', 'bokeCheckin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('boke_checkin_nonce'),
        ]);
    }

    /**
     * 渲染设置页面
     */
    public function render_settings_page() {
        $core = Boke_Checkin_Core::get_instance();
        $info = $core->get_last_checkin_info();
        ?>
        <div class="wrap">
            <h1>Bo.ke 签到助手</h1>
            <p>自动化每日签到 <a href="https://bo.ke" target="_blank">bo.ke 博客大联盟</a></p>

            <!-- 签到状态卡片 -->
            <div class="boke-status-card">
                <div class="status-header">
                    <h2>签到状态</h2>
                    <button type="button" id="boke-checkin-now" class="button button-primary">
                        立即签到
                    </button>
                </div>
                <div class="status-body">
                    <div class="status-item">
                        <span class="label">上次签到时间：</span>
                        <span class="value"><?php echo esc_html($info['time']); ?></span>
                    </div>
                    <div class="status-item">
                        <span class="label">上次签到状态：</span>
                        <span class="value status-<?php echo esc_attr($info['status']); ?>">
                            <?php
                            $status_labels = [
                                'success' => '✅ 成功',
                                'failed'  => '❌ 失败',
                                'unknown' => '⚪ 未知',
                            ];
                            echo esc_html($status_labels[$info['status']] ?? '未知');
                            ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="label">连续签到天数：</span>
                        <span class="value"><?php echo esc_html($info['days']); ?> 天</span>
                    </div>
                </div>
                <div id="checkin-result" class="checkin-result" style="display: none;"></div>
            </div>

            <!-- 设置表单 -->
            <form method="post" action="options.php">
                <?php
                settings_fields(BOKE_CHECKIN_OPTION_KEY);
                do_settings_sections($this->plugin_name);
                submit_button('保存设置');
                ?>
            </form>

            <!-- 使用说明 -->
            <div class="boke-help-section">
                <h2>使用说明</h2>
                <div class="help-content">
                    <h3>获取Cookie（超简单）</h3>
                    <ol>
                        <li>在浏览器中登录 <a href="https://bo.ke" target="_blank">https://bo.ke</a></li>
                        <li>按 <code>F12</code> 打开开发者工具</li>
                        <li>点击 <strong>Network</strong> 标签</li>
                        <li>刷新 <a href="https://bo.ke/dashboard/" target="_blank">https://bo.ke/dashboard/</a></li>
                        <li>找到 <code>dashboard</code> 请求</li>
                        <li>在 <strong>Request Headers</strong> 中找到 <code>cookie</code> 行</li>
                        <li><strong>复制整个cookie值</strong>，粘贴到上方的"完整Cookie字符串"框中</li>
                        <li>点击保存，插件会自动解析出需要的值</li>
                    </ol>

                    <h3>示例格式</h3>
                    <pre>boke_session=eyJ1aWQiOjE3MS...; boke_csrf=095f7cbab72675e0a97f5ce7fb9cb66d; other_cookie=xxx</pre>

                    <h3>定时任务说明</h3>
                    <p>插件使用 WordPress 内置的 WP-Cron 系统进行定时签到。请确保您的主机支持 WP-Cron 功能。</p>
                    <p>建议设置在流量较少的时间段执行，例如凌晨或早晨。</p>

                    <h3>邮件通知</h3>
                    <p>当签到失败时，系统会自动发送邮件通知到指定邮箱。请确保 WordPress 的邮件功能正常。</p>
                </div>
            </div>

            <!-- 关于插件 -->
            <div class="boke-about-section">
                <h2>关于插件</h2>
                <table class="boke-about-table">
                    <tr>
                        <th>插件名称</th>
                        <td>Bo.ke 签到助手</td>
                    </tr>
                    <tr>
                        <th>当前版本</th>
                        <td><?php echo BOKE_CHECKIN_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th>作者</th>
                        <td>摸鱼大王</td>
                    </tr>
                    <tr>
                        <th>插件主页</th>
                        <td><a href="https://blog.aistu.cn" target="_blank">摸鱼小窝 - blog.aistu.cn</a></td>
                    </tr>
                    <tr>
                        <th>简介</th>
                        <td>自动化每日签到 bo.ke 博客大联盟，支持WP-Cron定时任务和邮件通知。一键配置，轻松管理。</td>
                    </tr>
                    <tr>
                        <th>环境要求</th>
                        <td>
                            <ul>
                                <li>WordPress 5.0 或更高版本</li>
                                <li>PHP 7.0 或更高版本</li>
                                <li>支持 WP-Cron 的主机环境</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th>功能特点</th>
                        <td>
                            <ul>
                                <li>支持完整Cookie粘贴，自动解析</li>
                                <li>灵活的定时任务配置</li>
                                <li>签到失败邮件通知</li>
                                <li>完整的签到日志记录</li>
                                <li>一键立即签到</li>
                            </ul>
                        </td>
                    </tr>
                </table>
                <p class="boke-about-footer">
                    如果这个插件对您有帮助，欢迎访问 <a href="https://blog.aistu.cn" target="_blank">摸鱼小窝</a> 支持作者。
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX 签到处理
     */
    public function ajax_checkin() {
        // 验证 nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'boke_checkin_nonce')) {
            wp_send_json_error(['message' => '安全验证失败']);
        }

        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '权限不足']);
        }

        $core = Boke_Checkin_Core::get_instance();
        $result = $core->do_checkin();

        wp_send_json($result);
    }

    /**
     * 显示管理通知
     */
    public function show_admin_notices() {
        $screen = get_current_screen();
        if ($screen->id !== 'settings_page_' . $this->plugin_name) {
            return;
        }

        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);

        if (empty($settings['boke_session']) || empty($settings['boke_csrf'])) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Bo.ke 签到助手：</strong>请先配置 Cookie 信息才能开始自动签到。';
            echo '</p></div>';
        }
    }

    /**
     * 渲染完整Cookie字段
     */
    public function render_full_cookie_field($args) {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        $value = $settings[$args['id']] ?? '';
        ?>
        <textarea
            id="<?php echo esc_attr($args['id']); ?>"
            name="<?php echo BOKE_CHECKIN_OPTION_KEY; ?>[<?php echo esc_attr($args['id']); ?>]"
            class="large-text"
            rows="4"
            placeholder="boke_session=xxx; boke_csrf=xxx; ..."
        ><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php echo esc_html($args['description']); ?>
        </p>
        <p class="description">
            <strong>如何获取：</strong>浏览器登录bo.ke → F12 → Network → 刷新页面 → 找到dashboard请求 → Request Headers → cookie → 复制完整值
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo esc_attr($args['id']); ?>').on('blur', function() {
                var fullCookie = $(this).val();
                if (!fullCookie) return;

                // 自动解析boke_session
                var sessionMatch = fullCookie.match(/boke_session=([^;]+)/);
                if (sessionMatch) {
                    $('[name="<?php echo BOKE_CHECKIN_OPTION_KEY; ?>[boke_session]"]').val(sessionMatch[1]);
                }

                // 自动解析boke_csrf
                var csrfMatch = fullCookie.match(/boke_csrf=([^;]+)/);
                if (csrfMatch) {
                    $('[name="<?php echo BOKE_CHECKIN_OPTION_KEY; ?>[boke_csrf]"]').val(csrfMatch[1]);
                }

                // 显示提示
                if (sessionMatch && csrfMatch) {
                    alert('✅ 已自动解析出 boke_session 和 boke_csrf');
                } else {
                    alert('⚠️ 未能自动解析，请检查Cookie格式');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * 渲染文本字段
     */
    public function render_text_field($args) {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        $value = $settings[$args['id']] ?? '';
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
            esc_attr($args['id']),
            BOKE_CHECKIN_OPTION_KEY,
            esc_attr($args['id']),
            esc_attr($value)
        );
        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * 渲染邮箱字段
     */
    public function render_email_field($args) {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        $value = $settings[$args['id']] ?? '';
        printf(
            '<input type="email" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
            esc_attr($args['id']),
            BOKE_CHECKIN_OPTION_KEY,
            esc_attr($args['id']),
            esc_attr($value)
        );
        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * 渲染复选框字段
     */
    public function render_checkbox_field($args) {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        $checked = !empty($settings[$args['id']]);
        printf(
            '<input type="checkbox" id="%s" name="%s[%s]" value="1" %s />',
            esc_attr($args['id']),
            BOKE_CHECKIN_OPTION_KEY,
            esc_attr($args['id']),
            checked($checked, true, false)
        );
        if (!empty($args['description'])) {
            printf('<label for="%s">%s</label>', esc_attr($args['id']), esc_html($args['description']));
        }
    }

    /**
     * 渲染选择字段
     */
    public function render_select_field($args) {
        $settings = get_option(BOKE_CHECKIN_OPTION_KEY, []);
        $value = $settings[$args['id']] ?? 0;

        printf(
            '<select id="%s" name="%s[%s]">',
            esc_attr($args['id']),
            BOKE_CHECKIN_OPTION_KEY,
            esc_attr($args['id'])
        );

        foreach ($args['options'] as $option_value => $option_label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                selected($value, $option_value, false),
                esc_html($option_label)
            );
        }

        echo '</select>';
    }

    /**
     * 获取小时选项
     */
    private function get_hour_options() {
        $options = [];
        for ($i = 0; $i < 24; $i++) {
            $options[$i] = sprintf('%02d:00', $i);
        }
        return $options;
    }

    /**
     * 获取分钟选项
     */
    private function get_minute_options() {
        return [
            0  => '00 分',
            15 => '15 分',
            30 => '30 分',
            45 => '45 分',
        ];
    }
}
