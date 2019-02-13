<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizQuestion;

/**
 *  @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$form = new FormValidator('admin_questions', 'get');
$form->addHeader(get_lang('Questions'));
$form->addText('id', get_lang('Id'));
$form->addButtonSearch(get_lang('Search'));

$formContent = $form->returnForm();
$questionContent = '';

if ($form->validate()) {
    $id = (int) $form->getSubmitValue('id');
    if (!empty($id)) {
        $em = Database::getManager();
        /** @var CQuizQuestion $question */
        $question = $em->getRepository('ChamiloCourseBundle:CQuizQuestion')->find($id);
        if ($question) {
            // Creating empty exercise
            $exercise = new Exercise();
            $exercise->course_id = $question->getCId();
            ob_start();
            ExerciseLib::showQuestion(
                $exercise,
                $id,
                false,
                null,
                null,
                false,
                true,
                false,
                true,
                true
            );

            $questionContent = "<h3>#".$question->getIid()."</h3>";
            $questionContent .= "<h4>".Security::remove_XSS($question->getQuestion())."</h4>";
            $questionContent .= "<p>".Security::remove_XSS($question->getDescription())."</p>";
            $questionContent .= ob_get_contents();

            ob_end_clean();
        } else {
            Display::addFlash(Display::return_message(get_lang('NotFound')));
            header('Location:' .api_get_self());
            exit;
        }
    }
}

$tpl = new Template(get_lang('Questions'));
$content = " $formContent $questionContent ";

$tpl->assign('content', $content);
$tpl->display_one_col_template();
