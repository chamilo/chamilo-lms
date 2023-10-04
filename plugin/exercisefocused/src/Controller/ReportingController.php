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

        $exerciseId = $this->request->query->getInt('id');
        $exercise = $this->em->find(CQuiz::class, $exerciseId);

        if (!$exercise) {
            throw new Exception();
        }

        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();

        $form = $this->createForm();
        $form->updateAttributes(['action' => api_get_self().'?'.api_get_cidreq().'&id='.$exercise->getId()]);
        $form->addHidden('cidReq', $courseCode);
        $form->addHidden('id_session', $sessionId);
        $form->addHidden('gidReq', 0);
        $form->addHidden('gradebook', 0);
        $form->addHidden('origin', api_get_origin());
        $form->addHidden('id', $exercise->getId());

        $results = [];

        if ($form->validate()) {
            $formValues = $form->exportValues();

            $results = $this->findResults($formValues);
        }

        $table = $this->createTable($results);

        $this->setBreadcrumb($exercise->getId());

        $content = $form->returnForm()
            .Display::page_subheader(get_lang('ReportByAttempts'))
            .$table->toHtml();

        return $this->renderView(
            get_lang('ReportByAttempts'),
            $content,
            $exercise->getTitle()
        );
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
