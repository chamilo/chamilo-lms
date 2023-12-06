<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\ExerciseFocused\Traits\ReportingFilterTrait;
use Display;
use Exception;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportingController extends BaseController
{
    use ReportingFilterTrait;

    public function __invoke(): HttpResponse
    {
        parent::__invoke();

        $exercise = $this->em->find(
            CQuiz::class,
            $this->request->query->getInt('id')
        );

        if (!$exercise) {
            throw new Exception();
        }

        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();

        $tab1 = $this->generateTabResume($exercise);

        $tab2 = $this->generateTabSearch($exercise, $courseCode, $sessionId);

        $tab3 = $this->generateTabSampling($exercise);

        $content = Display::tabs(
            [
                $this->plugin->get_lang('ReportByAttempts'),
                get_lang('Search'),
                $this->plugin->get_lang('RandomSampling'),
            ],
            [$tab1, $tab2, $tab3],
            'exercise-focused-tabs',
            [],
            [],
            isset($_GET['submit']) ? 2 : 1
        );

        $this->setBreadcrumb($exercise->getId());

        return $this->renderView(
            $this->plugin->get_lang('ReportByAttempts'),
            $content,
            $exercise->getTitle()
        );
    }

    private function generateTabResume(CQuiz $exercise): string
    {
        $results = $this->findResultsInCourse($exercise->getId());

        return $this->createTable($results)->toHtml();
    }

    /**
     * @throws Exception
     */
    private function generateTabSearch(CQuiz $exercise, string $courseCode, int $sessionId): string
    {
        $form = $this->createForm();
        $form->updateAttributes(['action' => api_get_self().'?'.api_get_cidreq().'&id='.$exercise->getId()]);
        $form->addHidden('cidReq', $courseCode);
        $form->addHidden('id_session', $sessionId);
        $form->addHidden('gidReq', 0);
        $form->addHidden('gradebook', 0);
        $form->addHidden('origin', api_get_origin());
        $form->addHidden('id', $exercise->getId());

        $tableHtml = '';
        $actions = '';

        if ($form->validate()) {
            $formValues = $form->exportValues();

            $actionLeft = Display::url(
                Display::return_icon('export_excel.png', get_lang('ExportExcel'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PLUGIN_PATH).'exercisefocused/pages/export.php?'.http_build_query($formValues)
            );
            $actionRight = Display::toolbarButton(
                get_lang('Clean'),
                api_get_path(WEB_PLUGIN_PATH)
                .'exercisefocused/pages/reporting.php?'
                .api_get_cidreq().'&'.http_build_query(['id' => $exercise->getId(), 'submit' => '']),
                'search'
            );

            $actions = Display::toolbarAction(
                'em-actions',
                [$actionLeft, $actionRight]
            );

            $results = $this->findResults($formValues);

            $tableHtml = $this->createTable($results)->toHtml();
        }

        return $form->returnForm().$actions.$tableHtml;
    }

    private function generateTabSampling(CQuiz $exercise): string
    {
        $results = $this->findRandomResults($exercise->getId());

        return $this->createTable($results)->toHtml();
    }

    /**
     * @return array<int, TrackEExercises>
     */
    private function setBreadcrumb($exerciseId): void
    {
        $codePath = api_get_path('WEB_CODE_PATH');
        $cidReq = api_get_cidreq();

        $GLOBALS['interbreadcrumb'][] = [
            'url' => $codePath."exercise/exercise.php?$cidReq",
            'name' => get_lang('Exercises'),
        ];
        $GLOBALS['interbreadcrumb'][] = [
            'url' => $codePath."exercise/exercise_report.php?$cidReq&".http_build_query(['exerciseId' => $exerciseId]),
            'name' => get_lang('StudentScore'),
        ];
    }
}
