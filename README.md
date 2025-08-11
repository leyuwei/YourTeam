# Team Management Website

A simple responsive website for managing team members, projects, small tasks and workload reports. Built with PHP, MySQL and Bootstrap 5.

## Features
- Manager login (multiple accounts defined in database)
- Manage team members (create, edit, delete, import/export CSV/Excel) with detailed fields: name, email, identity number, campus ID number, year of join, current degree, degree pursuing, phone, WeChat, department, workplace and homeplace
- Manage projects with member assignments and status filtering
- Manage small tasks and their urgent affairs
- Generate workload reports for each member within a time range (exportable to CSV)

## Requirements
- PHP 8+
- MySQL 5.7+/MariaDB
- Apache with PHP support (LAMP stack)

## Installation
1. Clone or download this repository to your web root.
2. Import the database schema and sample data:
   ```bash
   mysql -u root -p < database.sql
   ```
   (Change `root` to your DB user.)
3. Edit `config.php` if your database credentials differ.
4. Access the site via `http://your-server/login.php` and log in using one of the predefined accounts:
   - Username: `manager1`, Password: `password`
   - Username: `manager2`, Password: `password`

## Usage
- After logging in, use the navigation bar to access members, projects, tasks and workload report pages.
- Import/export member lists and workload reports using CSV files (compatible with Excel).
- Member CSV columns are: `CampusID,Name,Email,IdentityNumber,YearOfJoin,CurrentDegree,DegreePursuing,Phone,WeChat,Department,Workplace,Homeplace`.
- When adding or removing members from projects, you will be asked to supply exact join/exit timestamps so that workload can be tracked accurately.

## Notes
This project uses Bootstrap from a CDN for styling and is responsive on both desktop and mobile browsers.

## License
This sample project is provided as-is without warranty.
