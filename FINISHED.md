# Bo.ke 签到助手 - WordPress 插件

完成！✅ 这是一个功能完整的WordPress插件。

## 📦 项目结构

```
boke-checkin-wordpress/
├── boke-checkin.php                    # 主插件文件（入口）
├── includes/
│   ├── class-boke-checkin-core.php     # 核心签到逻辑
│   ├── class-boke-checkin-admin.php    # 管理界面和设置
│   ├── class-boke-checkin-cron.php     # WP-Cron定时任务
│   └── class-boke-checkin-mail.php     # 邮件通知系统
├── admin/
│   ├── css/
│   │   └── admin.css                   # 管理界面样式
│   └── js/
│       └── admin.js                    # 管理界面脚本
├── languages/                          # 多语言支持（可扩展）
├── .gitignore                          # Git忽略规则
├── readme.txt                          # WordPress官方格式说明
├── README.md                           # 详细说明文档
└── INSTALL.md                          # 安装指南
```

## ✨ 功能特点

### 1. 后台管理界面

- ✅ 友好的设置页面
- ✅ Cookie配置（Session + CSRF）
- ✅ 定时任务时间设置
- ✅ 邮件通知配置
- ✅ 签到状态显示
- ✅ 立即签到按钮

### 2. 自动签到

- ✅ 使用WP-Cron定时执行
- ✅ 可自定义执行时间（小时+分钟）
- ✅ 自动验证签到结果
- ✅ HTTP 302 = 成功

### 3. 邮件通知

- ✅ 签到失败时自动发送邮件
- ✅ 美观的HTML邮件模板
- ✅ 包含错误信息和后台链接
- ✅ 可选启用/禁用

### 4. 日志记录

- ✅ 完整的签到日志
- ✅ 包含时间戳和状态
- ✅ 自动清理旧日志（最多1000条）
- ✅ 保存在 wp-content/uploads/

### 5. 安全性

- ✅ Nonce验证所有AJAX请求
- ✅ 权限检查（只有管理员能操作）
- ✅ 输入数据清理和验证
- ✅ 防止直接访问PHP文件

## 🚀 安装步骤

### 方法一：打包上传

```bash
# 创建ZIP包
cd D:/win/desktop/cowork/
zip -r boke-checkin.zip boke-checkin-wordpress/
```

然后在WordPress后台上传这个ZIP文件。

### 方法二：直接复制

1. 复制 `boke-checkin-wordpress` 文件夹
2. 重命名为 `boke-checkin`
3. 上传到 `/wp-content/plugins/`
4. 在WordPress后台激活插件

## ⚙️ 配置说明

### 获取Cookie

1. 登录 https://bo.ke
2. F12 打开开发者工具
3. Network → 找到 dashboard 请求
4. 复制 Request Headers 中的 cookie 值
5. 提取 `boke_session=xxx` 和 `boke_csrf=xxx`

### 设置定时任务

插件使用 WP-Cron，建议设置在：
- 凌晨 2-5 点（网站流量最少）
- 或早晨 6-8 点（签到高峰期前）

### 邮件通知

- 支持任何有效的邮箱地址
- 使用WordPress内置邮件功能
- 建议安装 WP Mail SMTP 提高邮件送达率

## 🔧 技术实现

### 核心流程

```
用户配置Cookie
    ↓
WP-Cron定时触发
    ↓
调用bo.ke签到API
    ↓
检查HTTP响应码
    ↓
302 = 成功 → 记录日志
其他 = 失败 → 发送邮件通知
```

### API请求详情

- **URL**: `https://bo.ke/dashboard/checkin/`
- **方法**: POST
- **Content-Type**: application/x-www-form-urlencoded
- **Body**: `_csrf={csrf_token}`
- **成功标志**: HTTP 302 重定向到 /dashboard/

### 文件说明

| 文件 | 职责 |
|------|------|
| boke-checkin.php | 插件入口、激活/停用钩子、自动加载 |
| Core.php | 签到逻辑、设置管理、状态跟踪 |
| Admin.php | 后台界面、表单处理、AJAX |
| Cron.php | 定时任务调度、日志管理 |
| Mail.php | 邮件模板、发送、日志 |

## 📝 使用示例

### 1. 首次安装

```
安装插件 → 激活 → 设置页面 → 填写Cookie → 保存 → 点击"立即签到" → 成功！
```

### 2. 日常使用

```
每天自动签到 → 查看日志 → 如果失败收到邮件 → 重新配置Cookie
```

### 3. 故障排除

```
签到失败 → 检查日志 → 重新获取Cookie → 更新配置 → 再次测试
```

## 🐛 故障排除

### 问题1: 签到返回403

**原因**: Cookie过期

**解决**: 重新登录bo.ke获取新Cookie

### 问题2: 定时任务不执行

**原因**: WP-Cron被禁用

**解决**: 
```php
// 在 wp-config.php 中确保没有这行
// define('DISABLE_WP_CRON', true);
```

### 问题3: 邮件收不到

**解决**:
1. 安装 WP Mail SMTP 插件
2. 配置SMTP服务器
3. 发送测试邮件验证

## 📊 日志示例

```
[2024-01-15 09:00:15] SUCCESS - 签到成功
[2024-01-16 09:00:18] FAILED - 签到失败 (HTTP 403)
[2024-01-17 09:00:12] SUCCESS - 签到成功
```

## 🔄 更新和维护

### 更新插件

1. 下载新版本
2. 备份旧版本
3. 上传新版本
4. 检查数据库迁移（如有）

### 备份数据

插件数据保存在：
- 数据库: `wp_options` 表中的 `boke_checkin_settings`
- 日志: `/wp-content/uploads/boke-checkin/`

## 📚 相关资源

- [WP-Cron 文档](https://developer.wordpress.org/plugins/cron/)
- [wp_mail() 函数](https://developer.wordpress.org/reference/functions/wp_mail/)
- [WordPress 插件开发](https://developer.wordpress.org/plugins/)

## 🤝 贡献

欢迎提交：
- Bug报告
- 功能建议
- 代码改进
- 文档完善

## 📄 许可证

GPL v2 或更高版本

## 👨‍💻 作者

**摸鱼大王** - https://bo.ke

---

**一切就绪！** 🎉

将 `boke-checkin-wordpress` 文件夹上传到你的WordPress站点，就可以开始自动签到了。
