<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;
use Knp\Component\Pager\Paginator;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

Session::erase('objExercise');
Session::erase('objQuestion');
Session::erase('objAnswer');

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
$start = 0;
$end = 0;

if ($formSent) {
    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : '';
    $description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
    $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
    $page = isset($_GET['page']) && !empty($_GET['page']) ? (int) $_GET['page'] : 1;

    $em = Database::getManager();
    $repo = $em->getRepository('ChamiloCourseBundle:CQuizQuestion');
    $criteria = new Criteria();
    if (!empty($id)) {
        $criteria->where($criteria->expr()->eq('iid', $id));
    }

    if (!empty($description)) {
        $criteria->orWhere($criteria->expr()->contains('description', $description."\r"));
        $criteria->orWhere($criteria->expr()->eq('description', $description));
        $criteria->orWhere($criteria->expr()->eq('description', '<p>'.$description.'</p>'));
    }

    if (!empty($title)) {
        $criteria->orWhere($criteria->expr()->contains('question', "%$title%"));
    }

    $questions = $repo->matching($criteria);

    if (empty($id)) {
        $id = '';
    }
    $params = [
        'id' => $id,
        'title' => Security::remove_XSS($title),
        'description' => Security::remove_XSS($description),
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

    if ($pagination) {
        $urlExercise = api_get_path(WEB_CODE_PATH).'exercise/admin.php?';
        $exerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?';
        $warningText = addslashes(api_htmlentities(get_lang('ConfirmYourChoice')));

        /** @var CQuizQuestion $question */
        for ($i = 0; $i < $length; $i++) {
            $index = $i;
            if (!empty($page)) {
                $index = ($page - 1) * $length + $i;
            }
            if (0 === $i) {
                $start = $index;
            }
            if (!isset($pagination[$index])) {
                continue;
            }

            if ($i < $length) {
                $end = $index;
            }
            $question = &$pagination[$index];
            $courseId = $question->getCId();
            $courseInfo = api_get_course_info_by_id($courseId);
            $courseCode = $courseInfo['code'];
            $question->courseCode = $courseCode;
            // Creating empty exercise
            $exercise = new Exercise($courseId);
            $questionObject = Question::read($question->getId(), $courseInfo);

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

            $deleteUrl = $url.'&'.http_build_query([
                'courseId' => $question->getCId(),
                'questionId' => $question->getId(),
                'action' => 'delete',
            ]);

            $exerciseData = '';
            $exerciseId = 0;
            if (!empty($questionObject->exerciseList)) {
                // Question exists in a valid exercise
                $exerciseData .= '<h4>'.get_lang('Exercises').'</h4>';
                foreach ($questionObject->exerciseList as $exerciseId) {
                    $exercise = new Exercise($question->getCId());
                    $exercise->course_id = $question->getCId();
                    $exercise->read($exerciseId);
                    $exerciseData .= $exercise->title.'&nbsp;';
                    $exerciseData .= Display::url(
                        Display::return_icon('edit.png', get_lang('Edit')),
                        $urlExercise.http_build_query(
                            [
                                'cidReq' => $courseCode,
                                'id_session' => $exercise->sessionId,
                                'exerciseId' => $exerciseId,
                                'type' => $question->getType(),
                                'editQuestion' => $question->getId(),
                            ]
                        ),
                        ['target' => '_blank']
                    ).'<br />';
                }
                $question->questionData .= '<br />'.$exerciseData;
            } else {
                // Question exists but it's orphan or it belongs to a deleted exercise
                // This means the question is added in a deleted exercise
                if ($questionObject->getCountExercise() > 0) {
                    $exerciseList = $questionObject->getExerciseListWhereQuestionExists();
                    if (!empty($exerciseList)) {
                        $question->questionData .= '<br />'.get_lang('Exercises').'<br />';
                        /** @var CQuiz $exercise */
                        foreach ($exerciseList as $exercise) {
                            $question->questionData .= $exercise->getTitle();
                            if ($exercise->getActive() == -1) {
                                $question->questionData .= '- ('.get_lang('ExerciseDeleted').' #'.$exercise->getIid().') ';
                            }
                            $question->questionData .= '<br />';
                        }
                    }
                } else {
                    // This question is orphan :(
                    $question->questionData .= '&nbsp;'.get_lang('OrphanQuestion');
                }

                $question->questionData .= Display::url(
                    Display::return_icon('edit.png', get_lang('Edit')),
                    $urlExercise.http_build_query(
                        [
                            'cidReq' => $courseCode,
                            'id_session' => 0, //$exercise->sessionId,
                            'exerciseId' => $exerciseId,
                            'type' => $question->getType(),
                            'editQuestion' => $question->getId(),
                        ]
                    ),
                    ['target' => '_blank']
                );
            }

            $question->questionData .= '<div class="pull-right">'.Display::url(
                get_lang('Delete'),
                $deleteUrl,
                [
                    'class' => 'btn btn-danger',
                    'onclick' => 'javascript: if(!confirm(\''.$warningText.'\')) return false',
                ]
            ).'</div>';

            ob_end_clean();
        }
    }
}

$formContent = $form->returnForm();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
switch ($action) {
    case 'delete':
        $questionId = isset($_REQUEST['questionId']) ? $_REQUEST['questionId'] : '';
        $courseId = isset($_REQUEST['courseId']) ? $_REQUEST['courseId'] : '';
        $courseInfo = api_get_course_info_by_id($courseId);
        if (!empty($courseInfo)) {
            $objQuestionTmp = Question::read($questionId, $courseInfo);
            if (!empty($objQuestionTmp)) {
                $result = $objQuestionTmp->delete();
                if ($result) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('Deleted').' #'.$questionId.' - "'.$objQuestionTmp->question.'"'
                        )
                    );
                }
            }
        }

        header("Location: $url");
        exit;
        break;
}

$tpl = new Template(get_lang('Questions'));
$tpl->assign('form', $formContent);
$tpl->assign('pagination', $pagination);
$tpl->assign('pagination_length', $length);
$tpl->assign('start', $start);
$tpl->assign('end', $end);
$tpl->assign('question_count', $questionCount);

$layout = $tpl->get_template('admin/questions.tpl');
$tpl->display($layout);
