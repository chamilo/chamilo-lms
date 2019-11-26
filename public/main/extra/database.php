<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

// database changes

exit;

$sql = "ALTER TABLE track_e_exercises ADD COLUMN coment VARCHAR(255);";
Database::query($sql);

$sql = "ALTER TABLE track_e_exercises ADD COLUMN diff VARCHAR(255);";
Database::query($sql);

$sql = "ALTER TABLE track_e_exercises ADD COLUMN mod_no VARCHAR(255);";
Database::query($sql);

$sql = "ALTER TABLE track_e_exercises ADD COLUMN score_ex VARCHAR(255);";
Database::query($sql);

$sql = "ALTER TABLE track_e_exercises ADD COLUMN score_rep1 VARCHAR(255);";
Database::query($sql);

$sql = "ALTER TABLE track_e_exercises ADD COLUMN score_rep2 VARCHAR(255);";
Database::query($sql);

$sql = "ALTER TABLE track_e_exercises ADD COLUMN inter_coment VARCHAR(255);";
Database::query($sql);

$sql = "ALTER TABLE track_e_exercises ADD COLUMN level VARCHAR(255);";
Database::query($sql);

$sql = "CREATE TABLE IF NOT EXISTS set_module   (
`id` int(4) NOT NULL AUTO_INCREMENT,
  `cours` varchar(40) NOT NULL,
  `module` varchar(40) NOT NULL,
  `jours` varchar(6) NOT NULL,
  `cal_name` varchar(20) NOT NULL,
  `cal_day_num` int(6) NOT NULL,
  `cal_date` date NOT NULL,
  PRIMARY KEY (`id`)
)";

Database::query($sql);

/*$sql = "CREATE TABLE IF NOT EXISTS  `kezprerequisites` (
`c_id` int(11) NOT NULL,
  `lp_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `prereq` int(11) DEFAULT NULL,
  `disporder` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`c_id`,`lp_id`,`id`)
)";*/

$sql = "CREATE TABLE IF NOT EXISTS `c_cal_dates` (
`c_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(1) NOT NULL,
  `horaire_name` varchar(190) DEFAULT NULL,
  UNIQUE KEY `temp` (`c_id`,`date`,`horaire_name`),
  UNIQUE KEY `indexunique` (`c_id`,`date`,`horaire_name`),
  KEY `idx` (`c_id`)
)";

Database::query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `c_cal_horaire` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `c_id` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `num_minute` int(11) NOT NULL,
  `num_hours` int(11) NOT NULL,
  `learnpath_dw` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `c_id` (`c_id`,`name`),
  KEY `idx` (`c_id`)
)";
Database::query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `c_cal_set_module` (
`id` int(4) NOT NULL AUTO_INCREMENT,
  `c_id` varchar(40) NOT NULL,
  `module` varchar(40) NOT NULL,
  `minutes` int(6) NOT NULL,
  PRIMARY KEY (`id`)
)";

Database::query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `c_cal_temp` (
`c_id` int(11) NOT NULL,
  `temp` varchar(250) NOT NULL,
  `user` varchar(250) NOT NULL,
  KEY `idx` (`c_id`)
)";
Database::query($sql);
