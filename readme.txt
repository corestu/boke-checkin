=== Bo.ke 签到助手 ===
Contributors: 摸鱼大王
Tags: boke, checkin, 签到, automation, cron
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

自动化每日签到 bo.ke 博客大联盟，支持WP-Cron定时任务和邮件通知。

== Description ==

Bo.ke 签到助手是一款WordPress插件，帮助您自动化完成 bo.ke 博客大联盟的每日签到。

**功能特点：**

* ✅ 一键签到 - 在WordPress后台即可快速签到
* ✅ 定时签到 - 使用WP-Cron自动定时执行
* ✅ 邮件通知 - 签到失败时自动发送邮件通知
* ✅ 签到记录 - 完整的日志记录
* ✅ 安全可靠 - 使用Nonce验证和权限检查

**为什么需要这个插件？**

* 忘记签到？插件会自动完成
* 担心签到失败？失败时会收到邮件通知
* 想要追踪签到记录？完整的日志功能

== Installation ==

1. 上传插件文件到 `/wp-content/plugins/boke-checkin/` 目录
2. 在WordPress后台"插件"页面激活本插件
3. 进入"设置" → "Bo.ke 签到"进行配置
4. 填入从浏览器获取的Cookie信息
5. 设置定时签到时间
6. 点击"保存设置"

== Frequently Asked Questions ==

= 如何获取Cookie？ =

1. 在浏览器中登录 https://bo.ke
2. 按 F12 打开开发者工具
3. 点击 Network 标签
4. 刷新 https://bo.ke/dashboard/
5. 找到 dashboard 请求
6. 在 Request Headers 中找到 cookie 行
7. 复制 boke_session 和 boke_csrf 的值

= Cookie多长时间过期？ =

Cookie的有效期通常较长（几周到几个月）。如果签到失败，可能需要重新获取Cookie。

= 为什么签到失败？ =

常见原因：
* Cookie已过期
* 网络连接问题
* CSRF Token失效

= 如何查看签到日志？ =

签到日志保存在 `/wp-content/uploads/boke-checkin/checkin.log`。

= 可以同时在多个站点使用吗？ =

可以，但每个站点需要单独配置Cookie。Cookie是绑定到IP地址的。

= 签到时间可以自定义吗？ =

是的，可以在设置页面选择任意小时和分钟组合。

= 邮件通知收不到怎么办？ =

请检查：
1. WordPress邮件功能是否正常
2. 垃圾邮件文件夹
3. 邮箱地址是否正确

== Screenshots ==

1. 插件设置页面
2. 签到状态卡片
3. 使用说明部分

== Changelog ==

= 1.0.0 (2024-01-15) =
* 初始版本发布
* 支持Cookie配置
* 支持定时签到
* 支持邮件通知
* 支持签到日志

== Upgrade Notice ==

= 1.0.0 =
首次发布

== Technical Details ==

* 使用WP-Cron进行定时任务
* 使用wp_remote_post发送HTTP请求
* 使用wp_mail发送邮件通知
* 支持多语言（可扩展）
* 符合WordPress编码标准
