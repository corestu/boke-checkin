# Bo.ke 签到助手 - WordPress 插件

自动化每日签到 [bo.ke 博客大联盟](https://bo.ke)，支持 WP-Cron 定时任务和邮件通知。

## ✨ 功能特点

- 🎯 **一键签到** - 在WordPress后台即可快速签到
- ⏰ **定时签到** - 使用WP-Cron自动定时执行
- 📧 **邮件通知** - 签到失败时自动发送邮件通知
- 📝 **签到记录** - 完整的日志记录
- 🔐 **安全可靠** - 使用Nonce验证和权限检查
- 🍪 **智能解析** - 支持完整Cookie粘贴，自动解析

## 📦 安装

### 方法一：上传安装

1. 下载 [最新版本](https://github.com/corestu/boke-checkin/releases)
2. 登录WordPress后台
3. 进入"插件" → "安装插件"
4. 点击"上传插件"
5. 选择下载的ZIP文件
6. 点击"安装"并激活

### 方法二：手动安装

```bash
cd /wp-content/plugins/
git clone https://github.com/corestu/boke-checkin.git
```

然后在WordPress后台激活插件。

## ⚙️ 配置

### 1. 获取Cookie

1. 在浏览器中登录 https://bo.ke
2. 按 `F12` 打开开发者工具
3. 点击 **Network** 标签
4. 刷新 https://bo.ke/dashboard/
5. 找到 `dashboard` 请求
6. 在 **Request Headers** 中找到 `cookie` 行
7. **复制整个cookie值**

### 2. 插件设置

进入 WordPress 后台 → 设置 → Bo.ke 签到

**Cookie配置**
- 粘贴完整的Cookie字符串到文本框
- 插件会自动解析出 `boke_session` 和 `boke_csrf`

**定时任务配置**
- 选择签到执行的时间（小时 + 分钟）

**邮件通知配置**
- 输入接收通知的邮箱地址
- 启用邮件通知

### 3. 保存并测试

点击"保存设置"，然后点击"立即签到"测试。

## 📁 文件结构

```
boke-checkin/
├── boke-checkin.php                # 主插件文件
├── includes/
│   ├── class-boke-checkin-core.php # 签到核心逻辑
│   ├── class-boke-checkin-admin.php# 管理界面
│   ├── class-boke-checkin-cron.php # WP-Cron处理
│   └── class-boke-checkin-mail.php # 邮件通知
├── admin/
│   ├── css/admin.css              # 管理界面样式
│   └── js/admin.js                # 管理界面脚本
└── readme.txt                     # WordPress插件说明
```

## 🔧 技术栈

- PHP 7.0+
- WordPress 5.0+
- WP-Cron (定时任务)
- wp_remote_post() (HTTP请求)
- wp_mail() (邮件发送)
- jQuery (管理界面)

## 🐛 故障排除

### Q: 签到失败 (HTTP 403)

**原因**: Cookie已过期

**解决**: 重新登录bo.ke获取新的Cookie

### Q: 定时任务不执行

**原因**: WP-Cron可能被禁用

**解决**:
```php
// 在 wp-config.php 中确保没有这行
// define('DISABLE_WP_CRON', true);
```

### Q: 邮件通知收不到

**解决**:
1. 安装 [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) 插件
2. 配置SMTP服务器
3. 发送测试邮件验证

### Q: 时区不对

**解决**: 在WordPress后台 → 设置 → 常规 → 时区，选择正确的时区

## 📝 日志

签到日志保存在：
```
/wp-content/uploads/boke-checkin/checkin.log
```

## 📄 许可证

GPL v2 或更高版本 - 详见 [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)

## 🤝 贡献

欢迎提交Issue和Pull Request！

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

## 📊 更新日志

### 1.0.0 (2024-01-22)

- 初始版本发布
- 支持Cookie自动解析
- 支持定时签到
- 支持邮件通知
- 支持签到日志

## 📞 支持

如有问题，请：

1. 查看本文档的故障排除部分
2. 在 [GitHub Issues](https://github.com/corestu/boke-checkin/issues) 提交问题
3. 访问 [摸鱼小窝](https://blog.aistu.cn) 联系作者

---

## 👨‍💻 关于作者

**摸鱼大王** - 独立博客爱好者，喜欢折腾各种自动化工具。

- 🌐 **博客**: [摸鱼小窝 - blog.aistu.cn](https://blog.aistu.cn)
- 💼 **GitHub**: [github.com/摸鱼大王](https://github.com/摸鱼大王)
- 📧 **邮箱**: 通过博客联系

### 支持作者

如果这个插件对您有帮助，欢迎：

- ⭐ 给个Star
- 🍴 Fork项目
- 📝 提交Issue和PR
- ☕ 访问博客支持

---

**如果这个插件帮到了你，请给个 ⭐ Star 支持一下！**
