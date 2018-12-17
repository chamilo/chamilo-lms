-- After the database schema is created, the database is filled
-- with default values.

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

-- Set parent language to Spanish for all close-by languages, same for Italian, French, Portuguese and Chinese
UPDATE language SET parent_id = 49 WHERE english_name = 'quechua_cusco';
UPDATE language SET parent_id = 49 WHERE english_name = 'galician';
UPDATE language SET parent_id = 49 WHERE english_name = 'esperanto';
UPDATE language SET parent_id = 49 WHERE english_name = 'catalan';
UPDATE language SET parent_id = 49 WHERE english_name = 'asturian';
UPDATE language SET parent_id = 28 WHERE english_name = 'friulian';
UPDATE language SET parent_id = 18 WHERE english_name = 'occitan';
UPDATE language SET parent_id = 40 WHERE english_name = 'brazilian';
UPDATE language SET parent_id = 45 WHERE english_name = 'trad_chinese';

INSERT INTO course_category (name, code, parent_id, tree_pos, children_count, auth_course_child, auth_cat_child) VALUES
('Language skills','LANG',NULL,1,0,'TRUE','TRUE'),
('PC Skills','PC',NULL,2,0,'TRUE','TRUE'),
('Projects','PROJ',NULL,3,0,'TRUE','TRUE');

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
(18,'learnpath','lp/lp_controller.php','scorms.gif',5,1,'basic'),
(19,'blog','blog/blog.php','blog.gif',1,2,'basic'),
(20,'blog_management','blog/blog_admin.php','blog_admin.gif',1,2,'courseadmin'),
(21,'course_maintenance','course_info/maintenance.php','backup.gif',2,3,'courseadmin'),
(22,'survey','survey/survey_list.php','survey.gif',2,1,'basic'),
(23,'wiki','wiki/index.php','wiki.gif',2,3,'basic'),
(24,'gradebook','gradebook/index.php','gradebook.gif',2,2,'basic'),
(25,'glossary','glossary/index.php','glossary.gif',2,1,'basic'),
(26,'notebook','notebook/index.php','notebook.gif',2,1,'basic'),
(27,'attendance','attendance/index.php','attendance.gif',2,1,'basic'),
(28,'course_progress','course_progress/index.php','course_progress.gif',2,1,'basic');

INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'legal_accept','Legal',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'already_logged_in','Already logged in',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'update_type','Update script type',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 10, 'tags','tags',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'rssfeeds','RSS',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'dashboard', 'Dashboard', 0, 0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 11, 'timezone', 'Timezone', 0, 0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_invitation',   'MailNotifyInvitation',0,1,'1', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_message',      'MailNotifyMessage',0,1,'1', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_group_message','MailNotifyGroupMessage',0,1,'1', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'user_chat_status','User chat status',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'google_calendar_url','Google Calendar URL',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) VALUES (2, 13, 'special_course', 'Special course', 1 , 1, '', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (2, 10, 'tags', 'Tags', 1, 1, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (2, 19, 'video_url', 'VideoUrl', 1, 1, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (3, 16, 'image', 'Image', 1, 1, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'captcha_blocked_until_date', 'Account locked until', 0, 0, NOW());

INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (8, '1', 'AtOnce',1);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (8, '8', 'Daily',2);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (8, '0', 'No',3);

INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (9, '1', 'AtOnce',1);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (9, '8', 'Daily',2);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (9, '0', 'No',3);

INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (10, '1', 'AtOnce',1);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (10, '8', 'Daily',2);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (10, '0', 'No',3);

-- Adding the platform templates
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCourseTitle', 'TemplateTitleCourseTitleDescription', 'coursetitle.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style type="text/css">
        .gris_title {
            color: silver;
        }

        h1 {
            text-align: right;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="vertical-align: middle; width: 50%;" colspan="1" rowspan="1">
            <h1>TITULUS 1<br>
                <span class="gris_title">TITULUS 2</span><br>
            </h1>
        </td>
        <td style="width: 50%;">
            <img style="width: 100px; height: 100px;" alt="Chamilo logo" src="{COURSE_DIR}images/logo_chamilo.png">
        </td>
    </tr>
    </tbody>
</table>
<p>
    <br><br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTeacher', 'TemplateTitleTeacherDescription', 'yourinstructor.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style type="text/css">
        .text {
            font-weight: normal;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td></td>
        <td style="height: 33%;"></td>
        <td></td>
    </tr>
    <tr>
        <td style="width: 25%;"></td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right; font-weight: bold;"
            colspan="1" rowspan="1">
    <span class="text">
    <br>
    Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</span>
        </td>
        <td style="width: 25%; font-weight: bold;">
            <img style="width: 180px; height: 241px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_case.png ">
        </td>
    </tr>
    </tbody>
</table>
<p>
    <br><br>
</p>
</body>
</html>
');


INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLeftList', 'TemplateTitleListLeftListDescription', 'leftlist.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="width: 66%;"></td>
        <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="4">&nbsp;<img
                style="width: 180px; height: 248px;" alt="trainer"
                src="{COURSE_DIR}images/trainer/trainer_reads.png "><br>
        </td>
    </tr>
    <tr align="right">
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
            Lorem
            ipsum dolor sit amet.
        </td>
    </tr>
    <tr align="right">
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
            Vivamus
            a quam.&nbsp;<br>
        </td>
    </tr>
    <tr align="right">
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
            Proin
            a est stibulum ante ipsum.
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLeftRightList', 'TemplateTitleLeftRightListDescription', 'leftrightlist.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; height: 400px; width: 720px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td></td>
        <td style="vertical-align: top;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 294px;"
                                                                            alt="Trainer"
                                                                            src="{COURSE_DIR}images/trainer/trainer_join_hands.png "><br>
        </td>
        <td></td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Lorem
            ipsum dolor sit amet.
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
            Convallis
            ut.&nbsp;Cras dui magna.
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Vivamus
            a quam.&nbsp;<br>
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
            Etiam
            lacinia stibulum ante.<br>
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Proin
            a est stibulum ante ipsum.
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
            Consectetuer
            adipiscing elit. <br>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleRightList', 'TemplateTitleRightListDescription', 'rightlist.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body style="direction: ltr;">
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="vertical-align: bottom; width: 50%;" colspan="1" rowspan="4"><img
                style="width: 300px; height: 199px;" alt="trainer"
                src="{COURSE_DIR}images/trainer/trainer_points_right.png"><br>
        </td>
        <td style="width: 50%;"></td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
            Convallis
            ut.&nbsp;Cras dui magna.
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
            Etiam
            lacinia.<br>
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
            Consectetuer
            adipiscing elit. <br>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleDiagram', 'TemplateTitleDiagramDescription', 'diagram.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; height: 33%; width: 33%;">
            <br>
            Etiam
            lacinia stibulum ante.
            Convallis
            ut.&nbsp;Cras dui magna.
        </td>
        <td colspan="1" rowspan="3">
            <img style="width: 350px; height: 267px;" alt="Alaska chart"
                 src="{COURSE_DIR}images/diagrams/alaska_chart.png "></td>
    </tr>
    <tr>
        <td colspan="1" rowspan="1">
            <img style="width: 300px; height: 199px;" alt="trainer"
                 src="{COURSE_DIR}images/trainer/trainer_points_right.png "></td>
    </tr>
    <tr>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleDesc', 'TemplateTitleCheckListDescription', 'description.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <img style="width: 48px; height: 49px; float: left;" alt="01" src="{COURSE_DIR}images/small/01.png "
                 hspace="5"><br>Lorem ipsum dolor sit amet<br><br><br>
            <img style="width: 48px; height: 49px; float: left;" alt="02" src="{COURSE_DIR}images/small/02.png "
                 hspace="5">
            <br>Ut enim ad minim veniam<br><br><br>
            <img style="width: 48px; height: 49px; float: left;" alt="03" src="{COURSE_DIR}images/small/03.png "
                 hspace="5">Duis aute irure dolor in reprehenderit<br><br><br>
            <img style="width: 48px; height: 49px; float: left;" alt="04" src="{COURSE_DIR}images/small/04.png "
                 hspace="5">Neque porro quisquam est
        </td>

        <td style="vertical-align: top; width: 50%; text-align: right;" colspan="1" rowspan="1">
            <img style="width: 300px; height: 291px;" alt="Gearbox" src="{COURSE_DIR}images/diagrams/gearbox.jpg "><br>
        </td>
    </tr>
    <tr></tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCycle', 'TemplateTitleCycleDescription', 'cyclechart.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style>
        .title {
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="8" cellspacing="6">
    <tbody>
    <tr>
        <td style="text-align: center; vertical-align: bottom; height: 10%;" colspan="3" rowspan="1">
            <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/top_arrow.png ">
        </td>
    </tr>
    <tr>
        <td style="height: 5%; width: 45%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
            <span class="title">Lorem ipsum</span>
        </td>
        <td style="height: 5%; width: 10%;"></td>
        <td style="height: 5%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
            <span class="title">Sed ut perspiciatis</span>
        </td>
    </tr>
    <tr>
        <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
            <ul>
                <li>dolor sit amet</li>
                <li>consectetur adipisicing elit</li>
                <li>sed do eiusmod tempor&nbsp;</li>
                <li>adipisci velit, sed quia non numquam</li>
                <li>eius modi tempora incidunt ut labore et dolore magnam</li>
            </ul>
        </td>
        <td style="width: 10%;"></td>
        <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
            <ul>
                <li>ut enim ad minim veniam</li>
                <li>quis nostrud exercitation</li>
                <li>ullamco laboris nisi ut</li>
                <li> Quis autem vel eum iure reprehenderit qui in ea</li>
                <li>voluptate velit esse quam nihil molestiae consequatur,</li>
            </ul>
        </td>
    </tr>
    <tr align="center">
        <td style="height: 10%; vertical-align: top;" colspan="3" rowspan="1">
            <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/bottom_arrow.png ">&nbsp;&nbsp;
            &nbsp; &nbsp; &nbsp;
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTimeline', 'TemplateTitleTimelineDescription', 'phasetimeline.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style>
        .title {
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="8" cellspacing="5">
    <tbody>
    <tr class="title">
        <td style="vertical-align: top; height: 3%; background-color: rgb(224, 224, 224);">Lorem ipsum</td>
        <td style="height: 3%;"></td>
        <td style="vertical-align: top; height: 3%; background-color: rgb(237, 237, 237);">Perspiciatis</td>
        <td style="height: 3%;"></td>
        <td style="vertical-align: top; height: 3%; background-color: rgb(245, 245, 245);">Nemo enim</td>
    </tr>
    <tr>
        <td style="vertical-align: top; width: 30%; background-color: rgb(224, 224, 224);">
            <ul>
                <li>dolor sit amet</li>
                <li>consectetur</li>
                <li>adipisicing elit</li>
            </ul>
            <br>
        </td>
        <td>
            <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
        </td>
        <td style="vertical-align: top; width: 30%; background-color: rgb(237, 237, 237);">
            <ul>
                <li>ut labore</li>
                <li>et dolore</li>
                <li>magni dolores</li>
            </ul>
        </td>
        <td>
            <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
        </td>
        <td style="vertical-align: top; background-color: rgb(245, 245, 245); width: 30%;">
            <ul>
                <li>neque porro</li>
                <li>quisquam est</li>
                <li>qui dolorem&nbsp;&nbsp;</li>
            </ul>
            <br><br>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTable', 'TemplateTitleCheckListDescription', 'table.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style type="text/css">
        .title {
            font-weight: bold;
            text-align: center;
        }

        .items {
            text-align: right;
        }
    </style>
</head>
<body>
<br/>
<h2>A table</h2>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px;"
       border="1" cellpadding="5" cellspacing="0">
    <tbody>
    <tr class="title">
        <td>City</td>
        <td>2005</td>
        <td>2006</td>
        <td>2007</td>
        <td>2008</td>
    </tr>
    <tr class="items">
        <td>Lima</td>
        <td>10,40</td>
        <td>8,95</td>
        <td>9,19</td>
        <td>9,76</td>
    </tr>
    <tr class="items">
        <td>New York</td>
        <td>18,39</td>
        <td>17,52</td>
        <td>16,57</td>
        <td>16,60</td>
    </tr>
    <tr class="items">
        <td>Barcelona</td>
        <td>0,10</td>
        <td>0,10</td>
        <td>0,05</td>
        <td>0,05</td>
    </tr>
    <tr class="items">
        <td>Paris</td>
        <td>3,38</td>
        <td>3,63</td>
        <td>3,63</td>
        <td>3,54</td>
    </tr>
    </tbody>
</table>
<br>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleAudio', 'TemplateTitleAudioDescription', 'audiocomment.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td>
            <div align="center">
    <span style="text-align: center;">
        <embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"
               width="300" height="20" bgcolor="#FFFFFF" src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
               allowfullscreen="false" allowscriptaccess="always"
               flashvars="file={COURSE_DIR}audio/ListeningComprehension.mp3&amp;autostart=true"></embed>
    </span></div>

            <br>
        </td>
        <td colspan="1" rowspan="3"><br>
            <img style="width: 300px; height: 341px; float: right;" alt="image"
                 src="{COURSE_DIR}images/diagrams/head_olfactory_nerve.png "><br></td>
    </tr>
    <tr>
        <td colspan="1" rowspan="1">
            <img style="width: 180px; height: 271px;" alt="trainer"
                 src="{COURSE_DIR}images/trainer/trainer_glasses.png"><br></td>
    </tr>
    <tr>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleVideo', 'TemplateTitleVideoDescription', 'video.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <div style="text-align: center;" id="player810625-parent">
                <div style="border-style: none; overflow: hidden; width: 320px; height: 240px; background-color: rgb(220, 220, 220);">
                    <div id="player810625">
                        <div id="player810625-config"
                             style="overflow: hidden; display: none; visibility: hidden; width: 0px; height: 0px;">
                            url={COURSE_DIR}video/flv/example.flv width=320 height=240
                            loop=false play=false downloadable=false fullscreen=true displayNavigation=true
                            displayDigits=true align=left dispPlaylist=none playlistThumbs=false
                        </div>
                    </div>
                    <embed
                            type="application/x-shockwave-flash"
                            src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
                            width="320"
                            height="240"
                            id="single"
                            name="single"
                            quality="high"
                            allowfullscreen="true"
                            flashvars="width=320&height=240&autostart=false&file={COURSE_DIR}video/flv/example.flv&repeat=false&image=&showdownload=false&link={COURSE_DIR}video/flv/example.flv&showdigits=true&shownavigation=true&logo="
                    />
                </div>
            </div>
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 50%;">
            <h3><br>
            </h3>
            <h3>Lorem ipsum dolor sit amet</h3>
            <ul>
                <li>consectetur adipisicing elit</li>
                <li>sed do eiusmod tempor incididunt</li>
                <li>ut labore et dolore magna aliqua</li>
            </ul>
            <h3>Ut enim ad minim veniam</h3>
            <ul>
                <li>quis nostrud exercitation ullamco</li>
                <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                <li>Excepteur sint occaecat cupidatat non proident</li>
            </ul>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
<style type="text/css">body {
}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleFlash', 'TemplateTitleFlashDescription', 'flash.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 100%; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td align="center">
            <embed width="700" height="300" type="application/x-shockwave-flash"
                   pluginspage="http://www.macromedia.com/go/getflashplayer"
                   src="{COURSE_DIR}flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed>
            </span><br/>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO user_friend_relation_type (id, title)
VALUES
(1,'SocialUnknow'),
(2,'SocialParent'),
(3,'SocialFriend'),
(4,'SocialGoodFriend'),
(5,'SocialEnemy'),
(6,'SocialDeleted');

INSERT INTO skill (name, icon, description, short_code, access_url_id, updated_at) VALUES ('Root', '', '', 'root', 1, now());

INSERT INTO skill_rel_skill VALUES(1, 1, 0, 0, 0);
INSERT INTO course_type (id, name) VALUES (1, 'All tools');
INSERT INTO course_type (id, name) VALUES (2, 'Entry exam');
INSERT INTO sequence_rule (description) VALUES ('If user completes 70% of an entity or group of items, he will be able to access another entity or group of items');
INSERT INTO sequence_condition (description, mat_op, param, act_true, act_false) VALUES ('<= 100%','<=', 100.0, 2, 0), ('>= 70%','>=', 70.0, 0, '');
INSERT INTO sequence_rule_condition VALUES (1,1,1), (2,1,2);

INSERT INTO sequence_method (description,formula, assign, met_type, act_false) VALUES
('Add completed item','v#2 + $complete_items;', 2, 'add', ''),
('Update progress by division', 'v#2 / v#3 * 100;', 1, 'div', ''),
('Update items count', '$total_items;', 3,'update', ''),
('Enable success', '1;', 4, 'success', ''),
('Store success date', '(empty(v#5))? api_get_utc_datetime() : v#5;', 5, 'success', ''),
('Enable availability', '1;', 6, 'pre', ''),
('Store availability start date', '(empty(v#7))? api_get_utc_datetime() : v#7;', 7, 'pre', ''),
('Store availability end date', '(empty($available_end_date))? api_get_utc_datetime($available_end_date) : "0000-00-00 00:00:00";', 8, 'pre', ''),
('Increase the items count', 'v#3 + $total_items;', 3,'add', ''),
('Update completed items', '$complete_items;', 2,'update', ''),
('Update progress', '$complete_items / $total_items * 100;', 1, 'update', '');

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
(1, 'Percentile progress', 'advance', 0.0),
(2, 'Completed items', 'complete_items', 0),
(3, 'Items count', 'total_items', 0),
(4, 'Completed', 'success', 0),
(5, 'Completion date', 'success_date', '0000-00-00 00:00:00'),
(6, 'Available', 'available', 0),
(7, 'Availability start date', 'available_start_date', '0000-00-00 00:00:00'),
(8, 'Availability end date', 'available_end_date', '0000-00-00 00:00:00');

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

UPDATE user SET username_canonical = username;

INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at)
VALUES
(1, 1, 'skype', 'Skype', 1, 1, now()),
(1, 1, 'linkedin_url', 'LinkedInUrl', 1, 1, now());

INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 1);
INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 2);
INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 3);

INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (8, 10, 'tags', 'Tags', 1, 1, NOW());
INSERT INTO branch_sync (access_url_id, branch_name, unique_id, ssl_pub_key) VALUES (1, 'localhost', SHA1(UUID()), SHA1(UUID()));

