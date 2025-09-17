[English](#english) | [中文](#中文)

<img width="2706" height="1395" alt="mainpage" src="https://github.com/user-attachments/assets/f64c2de2-1b69-43ae-b723-7407f0b09940" />

# 中文

## 超轻量级研究团队（牛马）管理网站
一个用于管理科研团队成员、项目、任务与工作量统计的响应式网站，基于 PHP、MySQL 与 Bootstrap 5 开发，丐版云服务器即可流畅托管！

### 功能特性
- 管理员与成员登录
- 管理成员、项目和研究方向
- 跟踪任务及成员申报的具体事务
- 成员申报的事务需经管理员确认后方可计入工作量报表
- 待确认事务仍可被其他成员加入、编辑或删除
- 财务报销流程与单据上传
- 管理员发布定向通知/单位规章制度
- 办公地点管理：管理员配置办公室布局与工位，成员自助选择或释放办公位置
- 生成工作量报表并支持导出为 Excel
- 默认中文界面，可切换中英语言

### 服务器环境配置（LAMP）
1. **安装软件包**（以 Ubuntu 为例）：
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-zip php-mbstring
   ```
2. **MySQL 安全设置**（可选）：
   ```bash
   sudo mysql_secure_installation
   ```
3. **启用 Apache 模块**并重启：
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
4. **创建虚拟主机**指向项目目录（如 `/var/www/yourteam`），并确保 Apache 对上传目录拥有读写权限。
5. **配置 PHP**：根据需要在 `php.ini` 中调整时区、文件上传等参数。

### 安装步骤
1. 将代码部署到 Web 服务器（例如 `/var/www/yourteam`）。
2. 导入数据库结构：
   ```bash
   mysql -u root -p < database.sql
   ```
3. 如有需要，修改 `config.php` 中的数据库配置。
4. 访问 `login.php` 登录系统。

### 自定义团队名称
在 `team_name.js` 中设置组织名称，例如：
```javascript
const TEAM_NAME = { en: 'ACME', zh: 'ACME' };
```
脚本会自动在页面标题和内容中的“Team/团队”前添加该名称。

### 导航功能
- **成员管理**：添加、编辑、导入或导出成员信息。
- **待办列表**：成员维护个人待办并在完成后勾选。
- **项目管理**：创建项目、分配成员并跟踪进度。
- **研究方向**：设定研究方向并关联相应成员。
- **定向通知**（仅管理员）：发布、编辑或撤销通知并查看成员阅读情况。
- **财务报销**：成员上传单据申请报销，管理员审核通过或拒绝。
- **任务分配**：为成员分派任务，成员申报事务等待确认。
- **工作量统计**（仅管理员）：汇总已确认事务并导出报表。
- **经费账户**（仅管理员）：管理报销账户余额与记录。

---

# English

## Ultra Lightweight Research Team Management Website
A responsive web application for managing research team members, projects, tasks and workload reports. Built with PHP, MySQL and Bootstrap 5. Can be Hosted Using Budget Cloud Server!

### Features
- Manager and member login
- Manage members, projects and research directions
- Track tasks with member-reported affairs
- Manager must confirm affairs before they appear in workload reports
- Pending affairs can still be joined, edited or deleted by members
- Expense reimbursement workflow with receipt uploads
- Targeted notifications for team members (manager only)
- Office management: managers configure office layouts and desks while members pick or release their seats
- Workload report generation and Excel export
- Language toggle (Chinese by default)

### Server Environment Setup (LAMP)
1. **Install packages** (Ubuntu example):
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-zip php-mbstring
   ```
2. **Secure MySQL** (optional):
   ```bash
   sudo mysql_secure_installation
   ```
3. **Enable Apache modules** and restart:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
4. **Create a virtual host** pointing to the project directory (e.g. `/var/www/yourteam`) and ensure Apache has permission to read/write uploads.
5. **Configure PHP** as needed in `php.ini` (timezone, file uploads, etc.).

### Installation
1. Deploy the code to your web server (e.g. `/var/www/yourteam`).
2. Import the database schema:
   ```bash
   mysql -u root -p < database.sql
   ```
3. Edit `config.php` for database credentials if needed.
4. Visit `login.php` to sign in.

### Customize Team Name
Set your organization name in `team_name.js`. Example:
```javascript
const TEAM_NAME = { en: 'ACME', zh: 'ACME' };
```
The script automatically inserts the name before "Team/团队" in page titles and content.

### Navbar Functions
- **Members**: Add, edit, import or export team members.
- **Todolist**: Manage personal to-do items and mark them complete.
- **Projects**: Create projects, assign members and track progress.
- **Research**: Define research directions and group members accordingly.
- **Notifications** (manager only): Send targeted announcements, edit or revoke them and see who has read each message.
- **Reimbursement**: Submit expense claims with receipts; managers approve or reject requests.
- **Tasks**: Assign tasks; members report affairs which managers then confirm.
- **Workload** (manager only): View statistics from confirmed affairs and export reports.
- **Account** (manager only): Adjust reimbursement account balance and review records.
