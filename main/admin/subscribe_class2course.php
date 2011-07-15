<?php

// $Id: subscribe_class2course.php 20441 2009-05-10 07:39:15Z ivantcholakov $
/*
==============================================================================
    Dokeos - elearning and course management software

    Copyright (c) 2004 Dokeos S.A.
    Copyright (c) 2003 Ghent University (UGent)
    Copyright (c) 2001 Universite catholique de Louvain (UCL)
    Copyright (c) Olivier Brouckaert

    For a full list of contributors, see "credits.txt".
    The full license can be read in "license.txt".

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    See the GNU General Public License for more details.

    Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'admin';

$cidReset = true;

require ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'classmanager.lib.php');
api_protect_admin_script();
$classes = $_GET['classes'];
$form_sent = 0;
$error_message = '';
$first_letter_class = '';
$first_letter_course = '';
$courses = array ();
$classes = array();

$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_class = Database :: get_main_table(TABLE_MAIN_CLASS);

$tool_name = get_lang('AddClassesToACourse');

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

Display :: display_header($tool_name);

//api_display_tool_title($tool_name);


if ($_POST['formSent'])
{
    $form_sent = $_POST['formSent'];
    $classes = is_array($_POST['ClassList']) ? $_POST['ClassList'] : array();
    $courses = is_array($_POST['CourseList']) ? $_POST['CourseList'] : array();
    $first_letter_class = $_POST['firstLetterClass'];
    $first_letter_course = $_POST['firstLetterCourse'];

    if ($form_sent == 1)
    {
        if (count($classes) == 0 || count($courses) == 0)
        {
            Display::display_error_message(get_lang('AtLeastOneClassAndOneCourse'));
        }
        elseif (api_substr($_POST['formSubmit'], -2) == '>>') // add classes to courses
        {
            foreach ($courses as $course_code)
            {
                foreach ($classes as $class_id)
                {
                    ClassManager :: subscribe_to_course($class_id, $course_code);
                }
            }
            Display::display_normal_message(get_lang('ClassesSubscribed'));
        }
        else // remove classes from courses
            {
            foreach ($courses as $course_code)
            {
                foreach ($classes as $class_id)
                {
                    ClassManager :: unsubscribe_from_course($class_id, $course_code);
                }
            }
            Display::display_normal_message(get_lang('ClassesUnsubscribed'));
        }
    }
}

$sql = "SELECT id,name FROM $tbl_class WHERE name LIKE '".$first_letter_class."%' ORDER BY ". (count($classes) > 0 ? "(id IN('".implode("','", $classes)."')) DESC," : "")." name";
$result = Database::query($sql);
$db_classes = Database::store_result($result);
$sql = "SELECT code,visual_code,title FROM $tbl_course WHERE visual_code LIKE '".$first_letter_course."%' ORDER BY ". (count($courses) > 0 ? "(code IN('".implode("','", $courses)."')) DESC," : "")." visual_code";
$result = Database::query($sql);
$db_courses = Database::store_result($result);
if (!empty ($error_message))
{
    Display :: display_normal_message($error_message);
}
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
 <input type="hidden" name="formSent" value="1"/>
 <table border="0" cellpadding="5" cellspacing="0" width="100%">
  <tr>
   <td width="40%" align="center">
    <b><?php echo get_lang('ClassList'); ?></b>
    <br/><br/>
    <?php echo get_lang('FirstLetterClass'); ?> :
    <select name="firstLetterClass" onchange="javascript:document.formulaire.formSent.value='2'; document.formulaire.submit();">
     <option value="">--</option>
     <?php
     echo Display::get_alphabet_options($first_letter_class);
     ?>
    </select>
   </td>
   <td width="20%">&nbsp;</td>
   <td width="40%" align="center">
    <b><?php echo get_lang('CourseList'); ?> :</b>
    <br/><br/>
    <?php echo get_lang('FirstLetterCourse'); ?> :
    <select name="firstLetterCourse" onchange="javascript:document.formulaire.formSent.value='2'; document.formulaire.submit();">
     <option value="">--</option>
     <?php
     echo Display::get_alphabet_options($first_letter_course);
     ?>
    </select>
   </td>
  </tr>
  <tr>
   <td width="40%" align="center">
    <select name="ClassList[]" multiple="multiple" size="20" style="width:230px;">
<?php
foreach ($db_classes as $class)
{
?>
    <option value="<?php echo $class['id']; ?>" <?php if(in_array($class['id'],$classes)) echo 'selected="selected"'; ?>><?php echo $class['name']; ?></option>
<?php
}
?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <input type="submit" name="formSubmit" value="<?php echo get_lang('AddToThatCourse'); ?> &gt;&gt;"/>
    <br/>
    <input type="submit" name="formSubmit" value="&lt;&lt; <?php echo get_lang('DeleteSelectedClasses'); ?>"/>
   </td>
   <td width="40%" align="center">
    <select name="CourseList[]" multiple="multiple" size="20" style="width:230px;">
<?php
foreach ($db_courses as $course)
{
?>
    <option value="<?php echo $course['code']; ?>" <?php if(in_array($course['code'],$courses)) echo 'selected="selected"'; ?>><?php echo '('.$course['visual_code'].') '.$course['title']; ?></option>
<?php
}
?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php
/*
==============================================================================
        FOOTER
==============================================================================
*/
Display :: display_footer();
?>
