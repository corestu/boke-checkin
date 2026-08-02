/**
 * Bo.ke 签到助手 - 管理界面脚本
 */

(function($) {
    'use strict';

    /**
     * 立即签到按钮点击事件
     */
    $(document).on('click', '#boke-checkin-now', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $result = $('#checkin-result');

        // 禁用按钮
        $button.prop('disabled', true).addClass('loading');

        // 显示加载状态
        $result.show().removeClass('success error').addClass('loading')
            .html('正在签到中，请稍候...');

        // 发送 AJAX 请求
        $.ajax({
            url: bokeCheckin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'boke_checkin_now',
                nonce: bokeCheckin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass('loading error').addClass('success')
                        .html('✅ ' + response.message);

                    // 刷新页面以更新状态显示
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $result.removeClass('loading success').addClass('error')
                        .html('❌ ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                $result.removeClass('loading success').addClass('error')
                    .html('❌ 请求失败: ' + error);
            },
            complete: function() {
                // 恢复按钮状态
                $button.prop('disabled', false).removeClass('loading');
            }
        });
    });

    /**
     * 表单验证
     */
    $('form').on('submit', function(e) {
        var $form = $(this);
        var fullCookie = $form.find('[name="boke_checkin_settings[full_cookie]"]').val();

        // 验证必填字段
        if (!fullCookie || fullCookie.trim() === '') {
            alert('请粘贴完整的 Cookie 信息');
            e.preventDefault();
            return false;
        }

        // 验证邮箱格式
        var adminEmail = $form.find('[name="boke_checkin_settings[admin_email]"]').val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (adminEmail && !emailRegex.test(adminEmail)) {
            alert('请输入有效的邮箱地址');
            e.preventDefault();
            return false;
        }

        return true;
    });

    /**
     * 显示提示信息
     */
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);

        // 自动消失
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);
