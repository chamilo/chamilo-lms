<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseMonitoring\Controller;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\PluginBundle\ExerciseFocused\Traits\DetailControllerTrait;
use Chamilo\PluginBundle\ExerciseMonitoring\Repository\LogRepository;
use Display;
use Doctrine\ORM\EntityManager;
use Exercise;
use ExerciseMonitoringPlugin;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DetailController
{
    use DetailControllerTrait;


    public function __construct(
        private readonly ExerciseMonitoringPlugin $plugin,
        private readonly HttpRequest $request,
        private readonly EntityManager $em,
        private readonly LogRepository $logRepository,
    ) {}

    public function __invoke(): HttpResponse
    {
        if (!$this->plugin->isEnabled(true)) {
            throw new AccessDeniedHttpException(
                HttpResponse::$statusTexts[HttpResponse::HTTP_FORBIDDEN],
            );
        }

        $trackExe = $this->em->find(
            TrackEExercise::class,
            $this->request->query->getInt('id')
        );

        if (!$trackExe) {
            throw new NotFoundHttpException(
                HttpResponse::$statusTexts[HttpResponse::HTTP_NOT_FOUND],
            );
        }

        $quiz = $trackExe->getQuiz();
        $user = $trackExe->getUser();

        $objExercise = new Exercise($trackExe->getCourse()->getId());
        $objExercise->read($quiz->getIid());

        $logs = $this->logRepository->findSnapshots($objExercise, $trackExe);

        $content = $this->generateHeader($objExercise, $user, $trackExe)
            .'<hr>'
            .$this->generateSnapshotList($logs);

        return new HttpResponse($content);
    }

    private function generateSnapshotList(array $logs): string
    {
        $html = '';

        foreach ($logs as $i => $log) {
            $date = api_get_local_time($log['createdAt'], null, null, true, true, true);

            $html .= '<div class="border border-gray-30 rounded-lg">';
            $html .= Display::img(
                '/plugin/ExerciseMonitoring/pages/snapshot.php?f='.urlencode($log['imageFilename']),
                $date,
                ['class' => 'rounded-t-lg'],
                false
            );
            $html .= '<div class="caption">';
            $html .= Display::tag('p', $date, ['class' => 'text-caption text-center']);
            $html .= Display::tag('p', $log['log_level'], ['class' => 'text-caption text-center']);
            $html .= '</div>';
            $html .= '</div>';
        }

        return '<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">'.$html.'</div>';
    }
}
