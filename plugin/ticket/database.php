<?php

$table = Database::get_main_table('tck_assigned_log');
$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
		ticket_id int UNSIGNED DEFAULT NULL,
		user_id int UNSIGNED DEFAULT NULL,
		assigned_date datetime DEFAULT NULL,
		sys_insert_user_id int UNSIGNED DEFAULT NULL,
		KEY FK_ticket_assigned_log (ticket_id) )";
Database::query($sql);

$table = Database::get_main_table('tck_category');
$sql = "CREATE TABLE ".$table." (
		project_id char(3) NOT NULL,
		category_id char(3) NOT NULL,
		name varchar(100) NOT NULL,
		description varchar(255) NOT NULL,
		total_tickets int UNSIGNED NOT NULL DEFAULT '0',
		course_required char(1) NOT NULL,
		sys_insert_user_id int UNSIGNED DEFAULT NULL,
		sys_insert_datetime datetime DEFAULT NULL,
		sys_lastedit_user_id int UNSIGNED DEFAULT NULL,
		sys_lastedit_datetime datetime DEFAULT NULL,
		PRIMARY KEY (project_id,category_id) )";
Database::query($sql);

$table = Database::get_main_table('tck_message');
$sql = "CREATE TABLE ".$table." (
		ticket_id int UNSIGNED NOT NULL,
		message_id int UNSIGNED NOT NULL,
		subject varchar(150) DEFAULT NULL,
		message text NOT NULL,
		status char(3) NOT NULL,
		ip_address varchar(16) DEFAULT NULL,
		sys_insert_user_id int UNSIGNED DEFAULT NULL,
		sys_insert_datetime datetime DEFAULT NULL,
		sys_lastedit_user_id int UNSIGNED DEFAULT NULL,
		sys_lastedit_datetime datetime DEFAULT NULL,
		PRIMARY KEY (ticket_id,message_id),
		KEY FK_tick_message (ticket_id) )";
Database::query($sql);


$table = Database::get_main_table('tck_message_attch');
$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
		ticket_id int UNSIGNED NOT NULL,
		message_id char(2) NOT NULL,
		message_attch_id char(2) NOT NULL,
		path varchar(255) NOT NULL,
		filename varchar(255) NOT NULL,
		size varchar(25) DEFAULT NULL,
		sys_insert_user_id int UNSIGNED DEFAULT NULL,
		sys_insert_datetime datetime DEFAULT NULL,
		sys_lastedit_user_id int UNSIGNED DEFAULT NULL,
		sys_lastedit_datetime datetime DEFAULT NULL,
		PRIMARY KEY (ticket_id,message_id,message_attch_id),
		KEY ticket_message_id_fk (message_id) ))";
Database::query($sql);

$table = Database::get_main_table('tck_priority');
$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
		priority_id char(3) NOT NULL,
		priority varchar(20) DEFAULT NULL,
		priority_desc varchar(250) DEFAULT NULL,
		priority_color varchar(25) DEFAULT NULL,
		priority_urgency tinyint DEFAULT NULL,
		sys_insert_user_id int UNSIGNED DEFAULT NULL,
		sys_insert_datetime datetime DEFAULT NULL,
		sys_lastedit_user_id int UNSIGNED DEFAULT NULL,
		sys_lastedit_datetime datetime DEFAULT NULL,
		PRIMARY KEY (priority_id))";
Database::query($sql);

$table = Database::get_main_table('tck_project');
$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
		project_id char(3) NOT NULL,
		name varchar(50) DEFAULT NULL,
		description varchar(250) DEFAULT NULL,
		email varchar(50) DEFAULT NULL,
		other_area tinyint NOT NULL DEFAULT '0',
		sys_insert_user_id int UNSIGNED DEFAULT NULL,
		sys_insert_datetime datetime DEFAULT NULL,
		sys_lastedit_user_id int UNSIGNED DEFAULT NULL,
		sys_lastedit_datetime datetime DEFAULT NULL,
		PRIMARY KEY (project_id))";
Database::query($sql);

$table = Database::get_main_table('tck_status');
$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
		status_id char(3) NOT NULL,
		name varchar(100) NOT NULL,
		description varchar(255) DEFAULT NULL,
		PRIMARY KEY (status_id))";
Database::query($sql);

$table = Database::get_main_table('tck_ticket');
$sql = "CREATE TABLE IF NOT EXISTS ".$table." (
		ticket_id int UNSIGNED NOT NULL AUTO_INCREMENT,
		ticket_code char(12) DEFAULT NULL,
		project_id char(3) DEFAULT NULL,
		category_id char(3) NOT NULL,
		priority_id char(3) NOT NULL,
		course_id int UNSIGNED NOT NULL,
		request_user int UNSIGNED NOT NULL,
		personal_email varchar(150) DEFAULT NULL,
		assigned_last_user int UNSIGNED NOT NULL DEFAULT '0',
		status_id char(3) NOT NULL,
		total_messages int UNSIGNED NOT NULL DEFAULT '0',
		keyword varchar(250) DEFAULT NULL,
		source char(3) NOT NULL,
		start_date datetime NOT NULL,
		end_date datetime DEFAULT NULL,
		sys_insert_user_id int UNSIGNED DEFAULT NULL,
		sys_insert_datetime datetime DEFAULT NULL,
		sys_lastedit_user_id int UNSIGNED DEFAULT NULL,
		sys_lastedit_datetime datetime DEFAULT NULL,
		PRIMARY KEY (ticket_id),
		UNIQUE KEY UN_ticket_code (ticket_code),
		KEY FK_ticket_priority (priority_id),
		KEY FK_ticket_category (project_id,category_id))";
Database::query($sql);

// Menu main tabs
//$table = Database::get_main_table('tck_ticket');
//$sql = "INSERT INTO settings_current
//(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
//VALUES
//('show_tabs', 'tickets', 'checkbox', 'Platform', 'true', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsTickets', 1)";
//Database::query($sql);
