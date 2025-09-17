<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\SortableSubscriber;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

Session::erase('objExercise');
Session::erase('objQuestion');
Session::erase('objAnswer');

$interbreadcrumb[] = ['url' => Container::getRouter()->generate('admin'), 'name' => get_lang('Administration')];
$action = $_REQUEST['action'] ?? '';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : '';
$description = $_REQUEST['description'] ?? '';
$title = $_REQUEST['title'] ?? '';
$page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;

// Prepare lists for form
// Courses list
$selectedCourse = isset($_GET['selected_course']) ? (int) $_GET['selected_course'] : null;
$courseList = CourseManager::get_courses_list(0, 0, 'title');
$courseSelectionList = ['-1' => get_lang('Select')];

foreach ($courseList as $item) {
    $course = api_get_course_entity($item['real_id']);
    $courseSelectionList[$course->getId()] = '';

    if ($course->getId() == api_get_course_int_id()) {
        $courseSelectionList[$course->getId()] = '>&nbsp;&nbsp;&nbsp;&nbsp;';
    }

    $courseSelectionList[$course->getId()] .= $course->getTitle();
}

// Difficulty list (only from 0 to 5)
$questionLevel = isset($_REQUEST['question_level']) ? (int) $_REQUEST['question_level'] : -1;
$levels = [
    -1 => get_lang('All'),
    0 => 0,
    1 => 1,
    2 => 2,
    3 => 3,
    4 => 4,
    5 => 5,
];

// Answer type
$answerType = isset($_REQUEST['answer_type']) ? (int) $_REQUEST['answer_type'] : null;
$questionList = Question::getQuestionTypeList();
$questionTypesList = [];
$questionTypesList['-1'] = get_lang('All');

foreach ($questionList as $key => $item) {
    $questionTypesList[$key] = get_lang($item[1]);
}

$form = new FormValidator('admin_questions', 'get');
$form->addHeader(get_lang('Questions'));
$form->addText('id', get_lang('Id'), false);
$form->addText('title', get_lang('Title'), false);
$form->addText('description', get_lang('Description'), false);
$form
    ->addSelect(
        'selected_course',
        [get_lang('Course'), get_lang('Course in which the question was initially created.')],
        $courseSelectionList,
        ['id' => 'selected_course']
    )
    ->setSelected($selectedCourse)
;
$form
    ->addSelect(
        'question_level',
        get_lang('Difficulty'),
        $levels,
        ['id' => 'question_level']
    )
    ->setSelected($questionLevel)
;
$form
    ->addSelect(
        'answer_type',
        get_lang('Answer type'),
        $questionTypesList,
        ['id' => 'answer_type']
    )
    ->setSelected($answerType)
;
$form->addHidden('form_sent', 1);
$form->addHidden('course_id_changed', '0');
$form->addButtonSearch(get_lang('Search'));

$questions = [];
$pagination = '';
$formSent = isset($_REQUEST['form_sent']) ? (int) $_REQUEST['form_sent'] : 0;
$length = 20;
$questionCount = 0;
$start = 0;
$end = 0;
$pdfContent = '';

$params = [
    'id' => $id,
    'title' => Security::remove_XSS($title),
    'description' => Security::remove_XSS($description),
    'selected_course' => $selectedCourse,
    'question_level' => $questionLevel,
    'answer_type' => $answerType,
];

if ($formSent) {
    $params['form_sent'] = 1;

    $em = Database::getManager();
    $repo = $em->getRepository(CQuizQuestion::class);
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

    if (-1 !== $selectedCourse) {
        /** @var \Chamilo\CoreBundle\Repository\ResourceNodeRepository $resourceNodeRepo */
        $resourceNodeRepo = $em->getRepository(ResourceNode::class);
        $resourceNodes = $resourceNodeRepo->findByResourceTypeAndCourse('questions', api_get_course_entity($selectedCourse));

        $criteria->andWhere(
            Criteria::expr()->in('resourceNode', $resourceNodes)
        );
    }

    if (-1 !== $questionLevel) {
        $criteria->andWhere($criteria->expr()->eq('level', $questionLevel));
    }
    if (-1 !== $answerType) {
        $criteria->andWhere($criteria->expr()->eq('type', $answerType));
    }

    $questions = $repo->matching($criteria);

    $url = api_get_self().'?'.http_build_query($params);
    $form->setDefaults($params);
    $questionCount = count($questions);

    if ('export_pdf' === $action) {
        $length = $questionCount;
    }

    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber(new PaginationSubscriber());
    $dispatcher->addSubscriber(new SortableSubscriber());

    $paginator = new Paginator($dispatcher);
    $pagination = $paginator->paginate($questions, $page, $length);
    $pagination->setItemNumberPerPage($length);
    $pagination->setCurrentPageNumber($page);
    $pagination->renderer = function ($data) use ($url) {
        $render = '<ul class="pagination">';
        for ($i = 1; $i <= $data['pageCount']; $i++) {
            $page = $i;
            $pageContent = '<li><a href="'.$url.'&page='.$page.'">'.$page.'</a></li>';
            if ($data['current'] == $page) {
                $pageContent = '<li class="active"><a href="#" >'.$page.'</a></li>';
            }
            $render .= $pageContent;
        }
        $render .= '</ul>';

        return $render;
    };

    $urlExercise = api_get_path(WEB_CODE_PATH).'exercise/admin.php?';
    $exerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?';
    $warningText = addslashes(api_htmlentities(get_lang('Please confirm your choice')));

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
        $courseId = $question->getFirstResourceLink()->getCourse()->getId();
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];
        $question->courseCode = $courseCode;
        // Creating empty exercise
        $exercise = new Exercise($courseId);
        $questionObject = Question::read($question->getIid(), $courseInfo);

        ob_start();
        ExerciseLib::showQuestion(
            $exercise,
            $question->getIid(),
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

        if ('export_pdf' === $action) {
            $pdfContent .= '<span style="color:#000; font-weight:bold; font-size:x-large;">#'.$question->getIid().'. '.$question->getQuestion().'</span><br />';
            $pdfContent .= '<span style="color:#444;">('.$questionTypesList[$question->getType()].') ['.get_lang('Source').': '.$courseCode.']</span><br />';
            $pdfContent .= $question->getDescription().'<br />';
            $pdfContent .= $question->questionData;
            continue;
        }

        $deleteUrl = $url.'&'.http_build_query([
            'courseId' => $courseId,
            'questionId' => $question->getIid(),
            'action' => 'delete',
        ]);
        $exerciseData = '';
        $exerciseId = 0;
        if (!empty($questionObject->exerciseList)) {
            // Question exists in a valid exercise
            $exerciseData .= '<p><strong>'.get_lang('Tests').'</strong></p>';
            foreach ($questionObject->exerciseList as $exerciseId) {
                $exercise = new Exercise($courseId);
                $exercise->course_id = $courseId;
                $exercise->read($exerciseId);
                $exerciseData .= $exercise->title.'&nbsp;';
                $exerciseData .= Display::url(
                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                    $urlExercise.api_get_cidreq_params($courseInfo['id'], $exercise->sessionId).'&'.http_build_query(
                        [
                            'exerciseId' => $exerciseId,
                            'type' => $question->getType(),
                            'editQuestion' => $question->getIid(),
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
                    $question->questionData .= '<p><strong>'.get_lang('Tests').'</strong></p>';
                    /** @var CQuiz $exercise */
                    foreach ($exerciseList as $exercise) {
                        $question->questionData .= $exercise->getTitle();
                        if (-1 == $exercise->getActive()) {
                            $question->questionData .= '- ('.get_lang('The test has been deleted').' #'.$exercise->getIid().') ';
                        }
                        $question->questionData .= '<br />';
                    }
                }
            } else {
                // This question is orphan :(
                $question->questionData .= '&nbsp;'.get_lang('Orphan question');
            }
            $question->questionData .= Display::url(
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                $urlExercise.api_get_cidreq_params($courseInfo['id']).'&'.http_build_query(
                    [
                        'exerciseId' => $exerciseId,
                        'type' => $question->getType(),
                        'editQuestion' => $question->getIid(),
                    ]
                ),
                ['target' => '_blank']
            );
        }
        $question->questionData .= '<div class="float-right">'.Display::url(
            get_lang('Delete'),
            $deleteUrl,
            [
                'class' => 'btn btn--danger',
                'onclick' => 'javascript: if(!confirm(\''.$warningText.'\')) return false',
            ]
        ).'</div>';
        ob_end_clean();
    }
}

$formContent = $form->returnForm();

switch ($action) {
    case 'export_pdf':
        $pdfContent = Security::remove_XSS($pdfContent);
        $pdfParams = [
            'filename' => 'questions-export-'.api_get_local_time(),
            'pdf_date' => api_get_local_time(),
            'orientation' => 'P',
        ];
        $pdf = new PDF('A4', $pdfParams['orientation'], $pdfParams);
        $pdf->html_to_pdf_with_template($pdfContent, false, false, true);
        exit;
    case 'delete':
        $questionId = $_REQUEST['questionId'] ?? '';
        $courseId = $_REQUEST['courseId'] ?? '';
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
}

$actionsLeft = Display::url(
    Display::return_icon('back.png', get_lang('Administration'), [], ICON_SIZE_MEDIUM),
    Container::getRouter()->generate('admin'),
);

$exportUrl = '/main/admin/questions.php?'.http_build_query(['action' => 'export_pdf', ...$params]);

$actionsRight = Display::url(
    Display::return_icon('pdf.png', get_lang('Export to PDF'), [], ICON_SIZE_MEDIUM),
    $exportUrl
);

$toolbar = Display::toolbarAction(
    'toolbar-admin-questions',
    [$actionsLeft, $actionsRight]
);

$tpl = new Template(get_lang('Questions'));
$tpl->assign('form', $formContent);
$tpl->assign('toolbar', $toolbar);
$tpl->assign('pagination', $pagination);
$tpl->assign('pagination_length', $length);
$tpl->assign('start', $start);
$tpl->assign('end', $end);
$tpl->assign('question_count', $questionCount);

$layout = $tpl->get_template('admin/questions.html.twig');
$tpl->display($layout);
