<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CToolIntro;

/**
 * The INTRODUCTION MICRO MODULE is used to insert and edit
 * an introduction section on a Chamilo module or on the course homepage.
 * It can be inserted on any Chamilo module, provided the corresponding setting
 * is enabled in the administration section.
 *
 * The introduction content are stored in a table called "tool_intro"
 * in the course Database. Each module introduction has an Id stored in
 * the table, which matches a specific module.
 *
 * '(c_)tool_intro' table description
 *   c_id: int
 *   id : int
 *   intro_text :text
 *   session_id: int
 *
 * usage :
 *
 * $moduleId = 'XX'; // specifying the module tool (string value)
 * include(introductionSection.inc.php);
 *
 * This script is also used since Chamilo 1.9 to show course progress (from the
 * course_progress module)
 */
$em = Database::getManager();
$intro_editAllowed = $is_allowed_to_edit = api_is_allowed_to_edit();
$session_id = api_get_session_id();
$blogParam = isset($_GET['blog_id']) ? ('&blog_id='.(int) $_GET['blog_id']) : '';

$introduction_section = '';

global $charset;
$intro_cmdEdit = empty($_GET['intro_cmdEdit']) ? '' : $_GET['intro_cmdEdit'];
$intro_cmdUpdate = isset($_POST['intro_cmdUpdate']);
$intro_cmdDel = empty($_GET['intro_cmdDel']) ? '' : $_GET['intro_cmdDel'];
$intro_cmdAdd = empty($_GET['intro_cmdAdd']) ? '' : $_GET['intro_cmdAdd'];
$courseId = api_get_course_id();

if (!empty($courseId)) {
    $form = new FormValidator(
        'introduction_text',
        'post',
        api_get_self().'?'.api_get_cidreq().$blogParam
    );
} else {
    $form = new FormValidator('introduction_text');
}

$config = [
    'ToolbarSet' => 'IntroductionSection',
    'Width' => '100%',
    'Height' => '300',
];

$form->addHtmlEditor('intro_content', null, false, false, $config, ['data-block' => true]);
$form->addButtonSave(get_lang('Save introductory text'), 'intro_cmdUpdate', false, ['data-block' => true]);

/* INTRODUCTION MICRO MODULE - COMMANDS SECTION (IF ALLOWED) */
$course_id = api_get_course_int_id();

if ($intro_editAllowed) {
    /** @var CToolIntro $toolIntro */
    $toolIntro = $em
        ->getRepository('ChamiloCourseBundle:CToolIntro')
        ->findOneBy(['cId' => $course_id, 'id' => $moduleId, 'sessionId' => $session_id]);

    /* Replace command */
    if ($intro_cmdUpdate) {
        if ($form->validate()) {
            $form_values = $form->exportValues();
            $intro_content = $form_values['intro_content'];

            if (!empty($intro_content)) {
                if (!$toolIntro) {
                    $toolIntro = new CToolIntro();
                    $toolIntro
                        ->setSessionId($session_id)
                        ->setCId($course_id)
                        ->setId($moduleId);
                }

                $toolIntro->setIntroText($intro_content);

                $em->persist($toolIntro);
                $em->flush();
                Display::addFlash(Display::return_message(get_lang('Intro was updated'), 'confirmation', false));
            } else {
                // got to the delete command
                $intro_cmdDel = true;
            }
        } else {
            $intro_cmdEdit = true;
        }
    }

    /* Delete Command */
    if ($intro_cmdDel && $toolIntro) {
        $em->remove($toolIntro);
        $em->flush();

        Display::addFlash(Display::return_message(get_lang('Intro was deleted'), 'confirmation'));
    }
}

/* INTRODUCTION MICRO MODULE - DISPLAY SECTION */

/* Retrieves the module introduction text, if exist */
// Getting course intro
/** @var CToolIntro $toolIntro */
$toolIntro = $em
    ->getRepository('ChamiloCourseBundle:CToolIntro')
    ->findOneBy(['cId' => $course_id, 'id' => $moduleId, 'sessionId' => 0]);

$intro_content = $toolIntro ? $toolIntro->getIntroText() : '';
if ($session_id) {
    /** @var CToolIntro $toolIntro */
    $toolIntro = $em
        ->getRepository('ChamiloCourseBundle:CToolIntro')
        ->findOneBy(['cId' => $course_id, 'id' => $moduleId, 'sessionId' => $session_id]);

    $introSessionContent = $toolIntro && $toolIntro->getIntroText() ? $toolIntro->getIntroText() : '';
    $intro_content = $introSessionContent ?: $intro_content;
}

// Default behaviour show iframes.
$userStatus = COURSEMANAGERLOWSECURITY;

// Allows to do a remove_XSS in course introduction with user status COURSEMANAGERLOWSECURITY
// Block embed type videos (like vimeo, wistia, etc) - see BT#12244 BT#12556
if (api_get_configuration_value('course_introduction_html_strict_filtering')) {
    $userStatus = COURSEMANAGER;
}

// Ignore editor.css
$cssEditor = api_get_path(WEB_CSS_PATH).'editor.css';
$linkToReplace = [
    '<link href="'.$cssEditor.'" rel="stylesheet" type="text/css" />',
    '<link href="'.$cssEditor.'" media="screen" rel="stylesheet" type="text/css" />',
];
$intro_content = str_replace($linkToReplace, '', $intro_content);
$intro_content = Security::remove_XSS($intro_content, $userStatus);

/* Determines the correct display */
if ($intro_cmdEdit || $intro_cmdAdd) {
    $intro_dispDefault = false;
    $intro_dispForm = true;
    $intro_dispCommand = false;
} else {
    $intro_dispDefault = true;
    $intro_dispForm = false;

    if ($intro_editAllowed) {
        $intro_dispCommand = true;
    } else {
        $intro_dispCommand = false;
    }
}

/* Executes the display */

// display thematic advance inside a postit
if ($intro_dispForm) {
    $default['intro_content'] = $intro_content;
    $form->setDefaults($default);
    $introduction_section .= $form->returnForm();
}

$thematic_description_html = '';
$thematicItemTwo = '';

if (TOOL_COURSE_HOMEPAGE == $tool && !isset($_GET['intro_cmdEdit'])) {
    // Only show this if we're on the course homepage and we're not currently editing
    $thematic = new Thematic();
    $displayMode = api_get_course_setting('display_info_advance_inside_homecourse');
    $class1 = '';
    if ('1' === $displayMode) {
        // Show only the current course progress step
        $last_done_advance = $thematic->get_last_done_thematic_advance();
        $thematicAdvance = $thematic->getThematicAdvance($last_done_advance);
        $subTitle1 = get_lang('Current topic');
        $class1 = ' current';
    } elseif ('2' === $displayMode) {
        // Show only the two next course progress steps
        $last_done_advance = $thematic->get_next_thematic_advance_not_done();
        $next_advance_not_done = $thematic->get_next_thematic_advance_not_done(2);
        $thematicAdvance = $thematic->getThematicAdvance($last_done_advance);
        $thematicAdvance2 = $thematic->getThematicAdvance($next_advance_not_done);
        $subTitle1 = $subTitle2 = get_lang('Next topic');
    } elseif ('3' === $displayMode) {
        // Show the current and next course progress steps
        $last_done_advance = $thematic->get_last_done_thematic_advance();
        $next_advance_not_done = $thematic->get_next_thematic_advance_not_done();
        $thematicAdvance = $thematic->getThematicAdvance($last_done_advance);
        $thematicAdvance2 = $thematic->getThematicAdvance($next_advance_not_done);
        $subTitle1 = get_lang('Current topic');
        $subTitle2 = get_lang('Next topic');
        $class1 = ' current';
    }

    if (!empty($thematicAdvance)) {
        $thematic_advance = get_lang('Course progress');
        $thematicScore = $thematic->get_total_average_of_thematic_advances().'%';
        $thematicUrl = api_get_path(WEB_CODE_PATH).'course_progress/index.php?action=thematic_details&'.api_get_cidreq();
        $thematic_info = $thematicAdvance->getThematic();
        $startDate = $thematicAdvance->getStartDate();

        //$thematicAdvance['start_date'] = api_get_local_time($startDate);
        //$thematicAdvance['start_date'] = api_format_date($startDate, DATE_TIME_FORMAT_LONG);
        $userInfo = api_get_user_info();
        $courseInfo = api_get_course_info();
        $titleThematic = $thematic_advance.' : '.$courseInfo['name'].' <b>( '.$thematicScore.' )</b>';

        $infoUser = '<div class="thematic-avatar"><img src="'.$userInfo['avatar'].'" class="img-circle img-responsive"></div>';
        $infoUser .= '<div class="progress">
                        <div class="progress-bar progress-bar-primary" role="progressbar" style="width: '.$thematicScore.';">
                        '.$thematicScore.'
                        </div>
                    </div>';

        $thematicItemOne = '
        <div class="col-md-6 items-progress">
            <div class="thematic-cont '.$class1.'">
            <div class="topics">'.$subTitle1.'</div>
            <h4 class="title-topics">'.
                Display::returnFontAwesomeIcon('book').strip_tags($thematic_info->getTitle()).'</h4>
            <p class="date">'.
                Display::returnFontAwesomeIcon('calendar-o').$thematicAdvance['start_date'].'</p>
            <div class="views">'.
                Display::returnFontAwesomeIcon('file-text-o').strip_tags($thematicAdvance->getContent()).'</div>
            <p class="time">'.
                Display::returnFontAwesomeIcon('clock-o').get_lang('Duration in hours').' : '.$thematicAdvance->getDuration().' -
                <a href="'.$thematicUrl.'">'.get_lang('See detail').'</a></p>
            </div>
        </div>';

        if (!empty($thematicAdvance2)) {
            $thematic_info2 = $thematicAdvance2->getThematic();
            //$thematicAdvance2['start_date'] = api_get_local_time($thematicAdvance2['start_date']);
            //$thematicAdvance2['start_date'] = api_format_date($thematicAdvance2['start_date'], DATE_TIME_FORMAT_LONG);

            $thematicItemTwo = '
                <div class="col-md-6 items-progress">
                    <div class="thematic-cont">
                    <div class="topics">'.$subTitle2.'</div>
                    <h4 class="title-topics">'.
                        Display::returnFontAwesomeIcon('book').api_get_local_time($thematic_info2->getStartDate()).'</h4>
                    <p class="date">'.
                        Display::returnFontAwesomeIcon('calendar-o').api_get_local_time($thematicAdvance2->getStartDate()).'</p>
                    <div class="views">'.
                        Display::returnFontAwesomeIcon('file-text-o').strip_tags($thematicAdvance2->getContent()).'</div>
                    <p class="time">'.
                        Display::returnFontAwesomeIcon('clock-o').get_lang('Duration in hours').' : '.$thematicAdvance2->getDuration().' -
                        <a href="'.$thematicUrl.'">'.get_lang('See detail').'</a></p>
                    </div>
                </div>';
        }
        $thematicPanel = '<div class="row">';
        $thematicPanel .= '<div class="col-md-2">'.$infoUser.'</div>';
        $thematicPanel .= '<div class="col-md-10"><div class="row">'.$thematicItemOne.$thematicItemTwo.'</div></div>';
        $thematicPanel .= '</div>';
        $thematicPanel .= '<div class="separate">
                        <a href="'.$thematicUrl.'" class="btn btn-default btn-block">'.get_lang('Show course planning').'</a>
                    </div>';

        $thematicProgress = Display::panelCollapse(
            $titleThematic,
            $thematicPanel,
            'thematic',
            null,
            'accordion-thematic',
            'collapse-thematic',
            false
        );
    }
}

$thematicSection = '';
if (!empty($thematicAdvance)) {
    $thematicSection = $thematic_description_html;
    $thematicSection .= $thematicProgress;
}

$toolbar = [];

/*if (api_is_allowed_to_edit() && empty($session_id)) {
    $tool = [
        'name' => get_lang('Customize icons'),
        'url' => api_get_path(WEB_CODE_PATH).'course_info/tools.php?'.api_get_cidreq(),
        'icon' => 'fas fa-cog',
    ];
    $toolbar[] = $tool;
}*/

if ($intro_dispCommand) {
    if (empty($intro_content)) {
        // Displays "Add intro" commands

        if (!empty($courseId)) {
            $tool = [
                'name' => addslashes(get_lang('Add Intro')),
                'url' => api_get_self().'?'.api_get_cidreq().$blogParam.'&intro_cmdAdd=1',
                'icon' => 'fas fa-pencil-alt',
            ];
            $toolbar[] = $tool;
        } else {
            $tool = [
                'name' => get_lang('Add Intro'),
                'url' => api_get_self().'?intro_cmdAdd=1',
                'icon' => 'fas fa-pencil-alt',
            ];
            $toolbar[] = $tool;
        }
    } else {
        // Displays "edit intro && delete intro" commands

        if (!empty($courseId)) {
            $tool = [
                'name' => get_lang('Edit'),
                'url' => api_get_self().'?'.api_get_cidreq().$blogParam.'&intro_cmdEdit=1',
                'icon' => 'fas fa-pencil-alt',
            ];
            $toolbar[] = $tool;

            $tool = [
                'name' => addslashes(api_htmlentities(get_lang('Please confirm your choice'))),
                'url' => api_get_self()."?".api_get_cidreq().$blogParam."&intro_cmdDel=1",
                'icon' => 'fas fa-trash-alt',
                'class' => 'delete-swal',
            ];
            $toolbar[] = $tool;
        } else {
            $tool = [
                'name' => get_lang('Edit'),
                'url' => api_get_self().'?intro_cmdEdit=1',
                'icon' => 'fas fa-pencil-alt',
            ];
            $toolbar[] = $tool;

            $tool = [
                'name' => addslashes(api_htmlentities(get_lang('Please confirm your choice'))),
                'url' => api_get_self()."?".api_get_cidreq()."&intro_cmdDel=1",
                'icon' => 'fas fa-trash-alt',
                'class' => 'delete-swal',
            ];
            $toolbar[] = $tool;
        }

        // Fix for chrome XSS filter for videos in iframes - BT#7930
        $browser = api_get_navigator();
        if (false !== strpos($introduction_section, '<iframe') && 'Chrome' == $browser['name']) {
            header('X-XSS-Protection: 0');
        }
    }
}

$nameSection = get_lang('Add an introduction to the course');
$helpSection = get_lang('This course is already created! Now you can add a presentation or welcome text to your course in this section, ideal for your students.');
if ('course_homepage' != $moduleId) {
    $nameSection = get_lang('Add an introduction to the tool');
    $helpSection = get_lang('Add Custom Tools Intro');
}

$textContent = [];
if ($intro_dispDefault) {
    if (!empty($intro_content)) {
        $textContent = [
            'id' => 'introduction-tool',
            'name' => $nameSection,
            'help' => $helpSection,
            'text' => $intro_content,
        ];
    } else {
        if (api_is_allowed_to_edit()) {
            $textContent = [
                'id' => 'introduction-course',
                'name' => $nameSection,
                'help' => $helpSection,
                'text' => $intro_content,
            ];
        }
    }
}

$browser = api_get_navigator();

if (false !== strpos($introduction_section, '<iframe') && 'Chrome' == $browser['name']) {
    header("X-XSS-Protection: 0");
}
$data = null;
$tpl = new Template(null);
$tpl->assign('thematic', $thematicSection);
$tpl->assign('intro', $textContent);
$tpl->assign('toolbar', $toolbar);
$introduction_section .= $tpl->fetch($tpl->get_template('auth/introduction_section.html.twig'));
