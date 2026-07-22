# 安装指南

## 快速安装（3分钟）

### 前置要求

- WordPress 5.0 或更高版本
- PHP 7.0 或更高版本
- 可写权限的 wp-content/uploads 目录

### 步骤 1: 上传插件

**方法 A: 通过 WordPress 后台**

1. 下载插件 ZIP 文件
2. 登录 WordPress 后台
3. 进入"插件" → "安装插件"
4. 点击"上传插件"
5. 选择下载的 ZIP 文件
6. 点击"安装"
7. 点击"激活"

**方法 B: 通过 FTP/SFTP**

1. 解压插件文件
2. 上传 `boke-checkin` 文件夹到 `/wp-content/plugins/`
3. 在WordPress后台"插件"页面激活

**方法 C: 通过 WP-CLI**

```bash
wp plugin install /path/to/boke-checkin --activate
```

### 步骤 2: 访问设置

1. 登录 WordPress 后台
2. 进入"设置" → "Bo.ke 签到"
3. 你会看到设置页面

### 步骤 3: 获取 Cookie

打开两个浏览器标签：

**标签 1: bo.ke**

1. 访问 https://bo.ke
2. 登录你的账号

**标签 2: 开发者工具**

1. 按 `F12` 打开开发者工具
2. 点击 **Network** 标签
3. 保持开发者工具打开

**继续标签 1:**

4. 访问 https://bo.ke/dashboard/
5. 在开发者工具中找到 `dashboard` 请求
6. 点击该请求
7. 在右侧找到 **Request Headers**
8. 找到 `cookie` 行
9. 复制整个值

### 步骤 4: 配置插件

在 WordPress 后台的插件设置页面：

**Cookie 配置**

1. 粘贴完整的 cookie 值到"Session Cookie"字段
2. 或者手动提取：
   - `boke_session=xxx` 的值填入"Session Cookie"
   - `boke_csrf=xxx` 的值填入"CSRF Cookie"

**定时任务配置**

1. 选择"执行小时"（例如：9 = 上午9点）
2. 选择"执行分钟"（例如：0 = 整点）

**邮件通知配置**

1. 输入接收通知的邮箱地址
2. 勾选"启用邮件通知"

### 步骤 5: 保存并测试

1. 点击"保存设置"
2. 点击"立即签到"按钮
3. 看到"✅ 签到成功"表示配置正确

## 验证安装

### 检查定时任务

在 WordPress 后台运行以下 WP-CLI 命令：

```bash
wp cron event list | grep boke
```

应该看到 `boke_checkin_daily_checkin` 事件。

### 检查日志目录

```bash
ls -la /wp-content/uploads/boke-checkin/
```

应该看到 `checkin.log` 文件。

## 常见安装问题

### 问题 1: 插件激活失败

**原因**: PHP版本过低或文件权限问题

**解决**:
1. 检查PHP版本：WordPress后台 → 工具 → 站点健康
2. 确保 `/wp-content/plugins/boke-checkin/` 目录可读
3. 确保 `/wp-content/uploads/` 目录可写

### 问题 2: 看不到设置菜单

**原因**: 权限不足

**解决**: 确保使用管理员账号登录

### 问题 3: 定时任务不执行

**原因**: WP-Cron被禁用

**解决**:
1. 检查 `wp-config.php` 是否有 `define('DISABLE_WP_CRON', true);`
2. 如果有，删除或注释掉这行
3. 或者使用系统Cron替代（见下文）

## 使用系统Cron（可选）

如果WP-Cron不可靠，可以使用系统Cron：

1. 在WordPress设置中禁用WP-Cron
2. 添加系统Cron任务：

```bash
# 编辑crontab
crontab -e

# 添加以下行（每天上午9点执行）
0 9 * * * wget -q -O - https://your-site.com/wp-cron.php?doing_wp_cron
```

## 卸载插件

1. 进入"设置" → "Bo.ke 签到"
2. 记录你的配置（如果需要）
3. 进入"插件"页面
4. 点击"停用"
5. 点击"删除"

插件数据会保留在数据库中，重新安装后可以恢复。

## 获取帮助

如果遇到问题：

1. 查看本文件的故障排除部分
2. 查看 README.md 中的FAQ
3. 在GitHub上提交Issue
4. 检查WordPress后台的"站点健康"页面

---

**安装完成！** 🎉

现在你的网站会每天自动签到 bo.ke 博客大联盟。
