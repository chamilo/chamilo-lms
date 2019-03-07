<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\Common\Collections\Criteria;
use Knp\Component\Pager\Paginator;

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
$form->addText('id', get_lang('Id'), false);
$form->addText('title', get_lang('Title'), false);
$form->addText('description', get_lang('Description'), false);
$form->addHidden('form_sent', 1);
$form->addButtonSearch(get_lang('Search'));

$questions = [];
$pagination = '';
$formSent = isset($_REQUEST['form_sent']) ? (int) $_REQUEST['form_sent'] : 0;
$length = 20;
$questionCount = 0;

if ($formSent) {
    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : '';
    $description = isset($_REQUEST['description']) ? Security::remove_XSS($_REQUEST['description']) : '';
    $title = isset($_REQUEST['title']) ? Security::remove_XSS($_REQUEST['title']) : '';
    $page = isset($_GET['page']) && !empty($_GET['page']) ? (int) $_GET['page'] : 1;

    $em = Database::getManager();
    $repo = $em->getRepository('ChamiloCourseBundle:CQuizQuestion');
    $criteria = new Criteria();
    if (!empty($id)) {
        $criteria->where($criteria->expr()->eq('iid', $id));
    }

    if (!empty($description)) {
        $criteria->orWhere($criteria->expr()->contains('description', "%$description%"));
    }

    if (!empty($title)) {
        $criteria->orWhere($criteria->expr()->contains('question', "%$title%"));
    }

    $questions = $repo->matching($criteria);

    $params = [
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'form_sent' => 1,
    ];
    $url = api_get_self().'?'.http_build_query($params);

    $form->setDefaults($params);

    $questionCount = count($questions);

    $paginator = new Paginator();
    $pagination = $paginator->paginate($questions, $page, $length);
    $pagination->setItemNumberPerPage($length);
    $pagination->setCurrentPageNumber($page);
    $pagination->renderer = function ($data) use ($url) {
        $render = '<ul class="pagination">';
        for ($i = 1; $i <= $data['pageCount']; $i++) {
            $page = (int) $i;
            $pageContent = '<li><a href="'.$url.'&page='.$page.'">'.$page.'</a></li>';
            if ($data['current'] == $page) {
                $pageContent = '<li class="active"><a href="#" >'.$page.'</a></li>';
            }
            $render .= $pageContent;
        }
        $render .= '</ul>';

        return $render;
    };

    /** @var CQuizQuestion $question */
    if ($pagination) {
        $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?';
        $exerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?';
        foreach ($pagination as $question) {
            $courseId = $question->getCId();
            $courseInfo = api_get_course_info_by_id($courseId);
            $courseCode = $courseInfo['code'];
            $question->courseCode = $courseCode;
            // Creating empty exercise
            $exercise = new Exercise();
            $exercise->course_id = $courseId;
            $questionObject = Question::read($question->getId(), $courseId);

            ob_start();
            ExerciseLib::showQuestion(
                $exercise,
                $question->getId(),
                false,
                null,
                null,
                false,
                true,
                false,
                true,
                true
            );
            $question->questionData = ob_get_contents();

            $exerciseData = '';
            $exerciseId = 0;
            if (!empty($questionObject->exerciseList)) {
                $exerciseData .= get_lang('Exercises').'<br />';
                foreach ($questionObject->exerciseList as $exerciseId) {
                    $exercise = new Exercise();
                    $exercise->course_id = $question->getCId();
                    $exercise->read($exerciseId);
                    $exerciseData .= $exercise->title.'&nbsp;';
                    $exerciseData .= Display::url(
                        Display::return_icon('edit.png', get_lang('Edit')),
                        $url.http_build_query([
                            'cidReq' => $courseCode,
                            'id_session' => $exercise->sessionId,
                            'myid' => 1,
                            'exerciseId' => $exerciseId,
                            'type' => $question->getType(),
                            'editQuestion' => $question->getId(),
                        ])
                    );
                }
                $question->questionData .= '<br />'.$exerciseData;
            } else {
                $question->questionData .= get_lang('Course').': '.Display::url(
                    $courseInfo['name'],
                    $exerciseUrl.http_build_query([
                        'cidReq' => $courseCode,
                    ])
                );
            }
            ob_end_clean();
        }
    }
}

$formContent = $form->returnForm();

$tpl = new Template(get_lang('Questions'));
$tpl->assign('form', $formContent);
$tpl->assign('pagination', $pagination);
$tpl->assign('pagination_length', $length);
$tpl->assign('question_count', $questionCount);

$layout = $tpl->get_template('admin/questions.tpl');
$tpl->display($layout);
