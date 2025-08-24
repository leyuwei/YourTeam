[English](#english) | [中文](#中文)

# English

## Research Team Management Website
A responsive web application for managing research team members, projects, tasks and workload reports. Built with PHP, MySQL and Bootstrap 5.

### Features
- Manager and member login
- Manage members, projects and research directions
- Track tasks with member-reported affairs
- Manager must confirm affairs before they appear in workload reports
- Pending affairs can still be joined, edited or deleted by members
- Workload report generation and Excel export
- Language toggle (Chinese by default)

### Requirements
- PHP 8+
- MySQL 5.7+/MariaDB
- Apache with PHP support

### Installation
1. Deploy the code to your web server.
2. Import the database schema:
   ```bash
   mysql -u root -p < database.sql
   ```
3. Edit `config.php` for database credentials if needed.
4. Access `login.php` to sign in.

### Database Upgrade
If upgrading from a previous version, run `update_db.sql` after backing up your database to add the new `status` field to `task_affairs`.

---

# 中文

## 研究团队管理网站
一个用于管理科研团队成员、项目、任务与工作量统计的响应式网站，基于 PHP、MySQL 与 Bootstrap 5 开发。

### 功能特性
- 管理员与成员登录
- 管理成员、项目和研究方向
- 跟踪任务及成员申报的具体事务
- 成员申报的事务需经管理员确认后方可计入工作量报表
- 待确认事务仍可被其他成员加入、编辑或删除
- 生成工作量报表并支持导出为 Excel
- 默认中文界面，可切换中英语言

### 环境要求
- PHP 8+
- MySQL 5.7+/MariaDB
- 支持 PHP 的 Apache 服务器

### 安装步骤
1. 将代码部署到 Web 服务器。
2. 导入数据库结构：
   ```bash
   mysql -u root -p < database.sql
   ```
3. 如有需要，修改 `config.php` 中的数据库配置。
4. 访问 `login.php` 登录系统。

### 数据库升级
从旧版本升级时，请先备份数据库，再运行 `update_db.sql` 为 `task_affairs` 表添加新的 `status` 字段。
