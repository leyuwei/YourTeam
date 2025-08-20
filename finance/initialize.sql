create database receipts;
ALTER DATABASE receipts CHARACTER SET = utf8 COLLATE utf8_general_ci;
use receipts;
create table basecfg ( id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, current_limit DATETIME NOT NULL, userid TEXT NOT NULL, price_limit INT(6) DEFAULT 200 NOT NULL);
alter table basecfg default character set utf8;
create table records ( id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, username TEXT NOT NULL, userid TEXT NOT NULL, filename TEXT NOT NULL, filetype TEXT NOT NULL, fileprice FLOAT NOT  NULL, isfinished BOOLEAN DEFAULT 0 NOT NULL, uploadtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
alter table records default character set utf8;
create table roll ( id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, username TEXT NOT NULL, userid TEXT NOT NULL, permission INT(1) DEFAULT 0 NOT NULL);
alter table roll default character set utf8;
insert into roll (id, username, userid, permission)
values
(NULL, 'test_user', '135791113', 2)