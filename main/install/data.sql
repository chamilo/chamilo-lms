
INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available) VALUES
('&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;','arabic','ar','arabic',0),
('Asturianu','asturian','ast','asturian',0),
('Euskara','basque','eu','basque',1),
('&#2476;&#2494;&#2434;&#2482;&#2494;','bengali','bn','bengali',0),
('Bosanski','bosnian','bs','bosnian',1),
('Portugu&ecirc;s do Brasil','brazilian','pt-BR','brazilian',1),
('&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;','bulgarian','bg','bulgarian',1),
('Catal&agrave;','catalan','ca','catalan',0),
('Hrvatski','croatian','hr','croatian',0),
('&#268;esky','czech','cs','czech',0),
('Dansk','danish','da','danish',0),
('&#1583;&#1585;&#1740;','dari','prs','dari',0),
('Nederlands','dutch','nl','dutch',1),
('English','english','en','english',1),
('Esperanto','esperanto','eo','esperanto',0),
('Føroyskt', 'faroese', 'fo', 'faroese', 0),
('Suomi','finnish','fi','finnish',0),
('Fran&ccedil;ais','french','fr','french',1),
('Furlan','friulian','fur','friulian',0),
('Galego','galician','gl','galician',1),
('&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;','georgian','ka','georgian',0),
('Deutsch','german','de','german',1),
('&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;','greek','el','greek',1),
('&#1506;&#1489;&#1512;&#1497;&#1514;','hebrew','he','hebrew',0),
('&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;','hindi','hi','hindi',0),
('Magyar','hungarian','hu','hungarian',1),
('Bahasa Indonesia','indonesian','id','indonesian',1),
('Italiano','italian','it','italian',1),
('&#26085;&#26412;&#35486;','japanese','ja','japanese',0),
('&#54620;&#44397;&#50612;','korean','ko','korean',0),
('Latvie&scaron;u','latvian','lv','latvian',1),
('Lietuvi&#371;','lithuanian','lt','lithuanian',0),
('&#1052;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080;','macedonian','mk','macedonian',0),
('Bahasa Melayu','malay','ms','malay',0),
('Norsk','norwegian','no','norwegian',0),
('Occitan','occitan','oc','occitan',0),
('&#1662;&#1690;&#1578;&#1608;','pashto','ps','pashto',0),
('&#1601;&#1575;&#1585;&#1587;&#1740;','persian','fa','persian',0),
('Polski','polish','pl','polish',1),
('Portugu&ecirc;s europeu','portuguese','pt','portuguese',1),
('Runasimi','quechua_cusco','qu','quechua_cusco',0),
('Rom&acirc;n&#259;','romanian','ro','romanian',0),
('&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;','russian','ru','russian',0),
('Srpski','serbian','sr','serbian',0),
('&#20013;&#25991;&#65288;&#31616;&#20307;&#65289;','simpl_chinese','zh','simpl_chinese',0),
('Sloven&#269;ina','slovak','sk','slovak',1),
('Sloven&scaron;&#269;ina','slovenian','sl','slovenian',1),
('&#1575;&#1604;&#1589;&#1608;&#1605;&#1575;&#1604;&#1610;&#1577;','somali','so','somali',0),
('Espa&ntilde;ol','spanish','es','spanish',1),
('Kiswahili','swahili','sw','swahili',0),
('Svenska','swedish','sv','swedish',0),
('Tagalog', 'tagalog', 'tl', 'tagalog',1),
('&#3652;&#3607;&#3618;','thai','th','thai',0),
('Tibetan', 'tibetan', 'bo', 'tibetan', 0),
('&#32321;&#39636;&#20013;&#25991;','trad_chinese','zh-TW','trad_chinese',0),
('T&uuml;rk&ccedil;e','turkish','tr','turkish',0),
('&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;','ukrainian','uk','ukrainian',0),
('Ti&#7871;ng Vi&#7879;t','vietnamese','vi','vietnamese',0),
('isiXhosa', 'xhosa', 'xh', 'xhosa', 0),
('Yor&ugrave;b&aacute;','yoruba','yo','yoruba',0);


INSERT INTO course_category VALUES (1,'Language skills','LANG',NULL,1,0,'TRUE','TRUE'),(2,'PC Skills','PC',NULL,2,0,'TRUE','TRUE'),(3,'Projects','PROJ',NULL,3,0,'TRUE','TRUE');

INSERT INTO course_module VALUES
(1,'calendar_event','calendar/agenda.php','agenda.gif',1,1,'basic'),
(2,'link','link/link.php','links.gif',4,1,'basic'),
(3,'document','document/document.php','documents.gif',3,1,'basic'),
(4,'student_publication','work/work.php','works.gif',3,2,'basic'),
(5,'announcement','announcements/announcements.php','valves.gif',2,1,'basic'),
(6,'user','user/user.php','members.gif',2,3,'basic'),
(7,'forum','forum/index.php','forum.gif',1,2,'basic'),
(8,'quiz','exercice/exercice.php','quiz.gif',2,2,'basic'),
(9,'group','group/group.php','group.gif',3,3,'basic'),
(10,'course_description','course_description/','info.gif',1,3,'basic'),
(11,'chat','chat/chat.php','chat.gif',0,0,'external'),
(12,'dropbox','dropbox/index.php','dropbox.gif',4,2,'basic'),
(13,'tracking','tracking/courseLog.php','statistics.gif',1,3,'courseadmin'),
(14,'homepage_link','link/link.php?action=addlink','npage.gif',1,1,'courseadmin'),
(15,'course_setting','course_info/infocours.php','reference.gif',1,1,'courseadmin'),
(16,'External','','external.gif',0,0,'external'),
(17,'AddedLearnpath','','scormbuilder.gif',0,0,'external'),
(18,'conference','conference/index.php?type=conference','conf.gif',0,0,'external'),
(19,'conference','conference/index.php?type=classroom','conf.gif',0,0,'external'),
(20,'learnpath','newscorm/lp_controller.php','scorms.gif',5,1,'basic'),
(21,'blog','blog/blog.php','blog.gif',1,2,'basic'),
(22,'blog_management','blog/blog_admin.php','blog_admin.gif',1,2,'courseadmin'),
(23,'course_maintenance','course_info/maintenance.php','backup.gif',2,3,'courseadmin'),
(24,'survey','survey/survey_list.php','survey.gif',2,1,'basic'),
(25,'wiki','wiki/index.php','wiki.gif',2,3,'basic'),
(26,'gradebook','gradebook/index.php','gradebook.gif',2,2,'basic'),
(27,'glossary','glossary/index.php','glossary.gif',2,1,'basic'),
(28,'notebook','notebook/index.php','notebook.gif',2,1,'basic'),
(29,'attendance','attendance/index.php','attendance.gif',2,1,'basic'),
(30,'course_progress','course_progress/index.php','course_progress.gif',2,1,'basic');

INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'legal_accept','Legal',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'already_logged_in','Already logged in',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'update_type','Update script type',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (10, 'tags','tags',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'rssfeeds','RSS',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'dashboard', 'Dashboard', 0, 0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (11, 'timezone', 'Timezone', 0, 0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_invitation',   'MailNotifyInvitation',1,1,'1');
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_message',      'MailNotifyMessage',1,1,'1');
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_group_message','MailNotifyGroupMessage',1,1,'1');
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'user_chat_status','User chat status',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'google_calendar_url','Google Calendar URL',0,0);

INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (8, '1', 'AtOnce',1);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (8, '8', 'Daily',2);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (8, '0', 'No',3);

INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (9, '1', 'AtOnce',1);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (9, '8', 'Daily',2);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (9, '0', 'No',3);

INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (10, '1', 'AtOnce',1);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (10, '8', 'Daily',2);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (10, '0', 'No',3);

INSERT INTO access_url(url, description, active, created_by) VALUES ('http://localhost/',' ',1,1);

-- Adding admin to the first portal
INSERT INTO access_url_rel_user VALUES(1, 1);

-- Adding the platform templates


INSERT INTO user_friend_relation_type (id,title)
VALUES
(1,'SocialUnknow'),
(2,'SocialParent'),
(3,'SocialFriend'),
(4,'SocialGoodFriend'),
(5,'SocialEnemy'),
(6,'SocialDeleted');

INSERT INTO course_field (field_type, field_variable, field_display_text, field_default_value, field_visible, field_changeable) values (1, 'special_course', 'Special course', '', 1 , 1);

INSERT INTO skill (name) VALUES ('Root');

INSERT INTO skill_rel_skill VALUES(1, 1, 0, 0, 0);

INSERT INTO course_type (id, name) VALUES (1, 'All tools');
INSERT INTO course_type (id, name) VALUES (2, 'Entry exam');


INSERT INTO sequence_rule (description)
VALUES ('Si el usuario completa un 70% de una entidad o grupo de recursos podrá acceder acceder a otra entidad o grupo de recursos');

INSERT INTO sequence_condition (description, mat_op, param, act_true, act_false) VALUES
('<= 100%','<=', 100.0, 2, null),
('>= 70%','>=', 70.0, 0, null);

INSERT INTO sequence_rule_condition VALUES
  (1,1,1),
  (2,1,2);

INSERT INTO sequence_method (description,formula, assign, met_type) VALUES
('Aumenta elemento completado','v#2 + $complete_items;', 2, 'add'),
('Actualiza Avance por división', 'v#2 / v#3 * 100;', 1, 'div'),
('Actualiza total de elementos', '$total_items;', 3,'update'),
('Activa logro', '1;', 4, 'success'),
('Almacena la fecha de logro', '(empty(v#5))? api_get_utc_datetime() : v#5;', 5, 'success'),
('Activa disponibilidad', '1;', 6, 'pre'),
('Almacena la fecha inicio de disponibilidad', '(empty(v#7))? api_get_utc_datetime() : v#7;', 7, 'pre'),
('Almacena la fecha fin de disponibilidad', '(empty($available_end_date))? api_get_utc_datetime($available_end_date) : "0000-00-00 00:00:00";', 8, 'pre'),
('Aumenta el total de elementos', 'v#3 + $total_items;', 3,'add'),
('Actualiza elementos completados', '$complete_items;', 2,'update'),
('Actualiza Avance', '$complete_items / $total_items * 100;', 1, 'update');

INSERT INTO sequence_rule_method VALUES
(1,1,1,1),
(2,1,2,3),
(3,1,3,0),
(4,1,4,0),
(5,1,5,0),
(6,1,6,0),
(7,1,7,0),
(8,1,8,0),
(9,1,9,2),
(10,1,10,0),
(11,1,11,0);

INSERT INTO sequence_variable VALUES
(1, 'Avance porcentual', 'advance', 0.0),
(2, 'Elementos completados', 'complete_items', 0),
(3, 'Total de elementos', 'total_items', 0),
(4, 'Completado', 'success', 0),
(5, 'Fecha de completado', 'success_date', '0000-00-00 00:00:00'),
(6, 'Disponible', 'available', 0),
(7, 'Fecha de inicio de disponibilidad', 'available_start_date', '0000-00-00 00:00:00'),
(8, 'Fecha de fin de disponibilidad', 'available_end_date', '0000-00-00 00:00:00');

INSERT INTO sequence_formula VALUES
(1,1,2),
(2,2,2),
(3,2,3),
(4,2,1),
(5,3,3),
(6,4,4),
(7,5,5),
(8,6,6),
(9,7,7),
(10,8,8),
(11,9,3),
(12,10,2),
(13,11,1);
INSERT INTO sequence_valid VALUES
(1,1,1),
(2,1,2);
INSERT INTO sequence_type_entity VALUES
(1,'Lp', 'Learning Path','c_lp'),
(2,'Quiz', 'Quiz and Tests','c_quiz'),
(3,'LpItem', 'Items of a Learning Path','c_lp_item');

UPDATE settings_current SET selected_value = '1.10.0.35' WHERE variable = 'chamilo_database_version';




