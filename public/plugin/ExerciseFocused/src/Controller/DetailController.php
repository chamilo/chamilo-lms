<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Chamilo\PluginBundle\ExerciseFocused\Traits\DetailControllerTrait;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Exception;
use Exercise;
use HTML_Table;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DetailController extends BaseController
{
    use DetailControllerTrait;

    /**
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws Exception
     */
    public function __invoke(): Response
    {
        parent::__invoke();

        $exeId = $this->request->query->getInt('id');
        $exe = $this->em->find(TrackEExercise::class, $exeId);

        if (!$exe) {
            throw new NotFoundHttpException(
                Response::$statusTexts[Response::HTTP_NOT_FOUND]
            );
        }

        $objExercise = new Exercise($exe->getCourse()->getId());
        $objExercise->read($exe->getQuiz()->getIid());

        $logs = $this->logRepository->findBy(['exe' => $exe], ['updatedAt' => 'ASC']);
        $table = $this->getTable($objExercise, $logs);

        $content = $this->generateHeader($objExercise, $exe->getUser(), $exe)
            .'<hr>'
            .$table->toHtml();

        return new Response($content);
    }

    /**
     * @param array<int, Log> $logs
     */
    private function getTable(Exercise $objExercise, array $logs): HTML_Table
    {
        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaderContents(0, 0, get_lang('Action'));
        $table->setHeaderContents(0, 1, get_lang('DateTime'));
        $table->setHeaderContents(0, 2, $this->plugin->get_lang('LevelReached'));

        $row = 1;

        foreach ($logs as $log) {
            $strLevel = '';

            if (ONE_PER_PAGE == $objExercise->selectType()) {
                try {
                    $strLevel = $this->em
                        ->find(CQuizQuestion::class, $log->getLevel())
                        ?->getQuestion()
                    ;
                } catch (Exception) {
                }
            }

            $table->setCellContents(
                $row,
                0,
                $this->plugin->getActionTitle($log->getAction())
            );
            $table->setCellContents(
                $row,
                1,
                api_get_local_time($log->getCreatedAt(), null, null, true, true, true)
            );
            $table->setCellContents($row, 2, $strLevel);

            $row++;
        }

        return $table;
    }
}
