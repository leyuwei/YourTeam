[English](#english) | [中文](#中文)

<img width="2706" height="1395" alt="mainpage" src="https://github.com/user-attachments/assets/f64c2de2-1b69-43ae-b723-7407f0b09940" />

# 中文

## 超轻量级研究团队（牛马）管理网站
一个用于管理科研团队成员、项目、任务与工作量统计的响应式网站，基于 PHP、MySQL 与 Bootstrap 5 开发，丐版云服务器即可流畅托管！

### 功能特性
- 管理员与成员登录，可在主页一键切换身份号/密码登录方式

- 默认中文界面，可切换中英语言；支持深浅色主题与移动端自适应布局

- 管理成员、项目和研究方向

- AskMe 智能检索：按关键词跨规章/办公室/资产/知识库模糊搜索，管理员可维护问答库

- 待办列表、任务分配与成员申报事务；管理员确认后计入工作量报表

- 财务报销流程，批量导入单据、管理员审核；支持经费账户余额记录

- 资产管理：资产编码生成、图片上传、操作日志及借用/归还记录

- 办公地点管理：管理员配置办公室布局与工位，成员自助选择或释放座位

- 管理员发布定向通知/规章制度，成员在首页集中确认并弹窗提醒

- 生成工作量报表并导出 Excel


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
- **成员管理**：添加、编辑、导入或导出成员信息

- **AskMe**：关键词检索规章/办公室/资产/知识库；管理员维护问答条目

- **待办列表**：成员维护个人待办并在完成后勾选

- **项目管理**：创建项目、分配成员并跟踪进度

- **研究方向**：设定研究方向并关联相应成员

- **办公室**：工位配置、成员自助选座/释放

- **财务报销**：成员上传单据申请报销，管理员审核

- **资产**：资产编码生成、图片/附件存储、借用/归还、操作日志

- **通知与规章**（管理员）：发布、撤销、排序；成员在首页查看与确认

- **任务分配**：分派任务，成员申报事务等待确认

- **工作量统计**（管理员）：汇总已确认事务并导出报表

- **经费账户**（管理员）：调整报销账户余额与查看记录

- 顶栏语言切换、暗黑模式、移动端折叠导航


---

# English

## Ultra Lightweight Research Team Management Website
A responsive web app for managing research team members, projects, tasks, and workload reports. Built with PHP, MySQL, and Bootstrap 5; runs smoothly on budget cloud servers.

### Features
- Manager & member sign-in with on-homepage preference for identity-number or password login

- Bilingual UI (ZH/EN), dark/light theme toggle, and mobile-friendly navbar

- Manage members, projects, and research directions

- AskMe search: fuzzy keyword lookup across policies, offices, assets, and a manager-maintained knowledge base

- Personal todolist; task assignments and member-reported affairs require manager confirmation for workload reports

- Reimbursement flow with receipt uploads and manager approval; expense account tracking

- Asset management with code generator, image upload, operation log, and borrow/return tracking

- Office layouts and desks: managers configure seating, members claim or release seats

- Targeted notifications and regulations; members confirm notices from the homepage with modal reminders

- Workload reporting and Excel export for confirmed affairs


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
4. **Create a virtual host** pointing to the project directory (e.g. `/var/www/yourteam`) and ensure Apache can read/write uploads.
5. **Configure PHP** in `php.ini` as needed (timezone, upload limits, etc.).

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
The script automatically inserts the name before “Team/团队” in titles and content.

### Navbar Functions
- **Members**: Add, edit, import, or export team members

- **AskMe**: Keyword search over policies/offices/assets/knowledge; manager-maintained Q&A entries

- **Todolist**: Manage personal to-dos and mark them complete

- **Projects**: Create projects, assign members, and track progress

- **Research**: Define research directions and group members

- **Offices**: Configure layouts; members claim or release desks

- **Reimbursement**: Submit claims with receipts; managers approve or reject

- **Assets**: Generate codes, upload images, log operations, track borrow/return history

- **Notifications & Regulations** (manager): Publish/revoke/sort; members confirm from homepage

- **Tasks**: Assign tasks; members report affairs for confirmation

- **Workload** (manager): View confirmed-affair stats and export reports

- **Account** (manager): Adjust reimbursement account balance and review records

- Language toggle, dark mode, and mobile-friendly collapsible navbar


---

## Testing
- ⚠️ Not run (static README update only)
