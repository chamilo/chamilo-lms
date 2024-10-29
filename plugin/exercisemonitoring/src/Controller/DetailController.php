<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseMonitoring\Controller;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\ExerciseFocused\Traits\DetailControllerTrait;
use Display;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use Exercise;
use ExerciseMonitoringPlugin;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DetailController
{
    use DetailControllerTrait;

    /**
     * @var ExerciseMonitoringPlugin
     */
    private $plugin;

    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $logRepository;

    public function __construct(
        ExerciseMonitoringPlugin $plugin,
        HttpRequest $request,
        EntityManager $em,
        EntityRepository $logRepository
    ) {
        $this->plugin = $plugin;
        $this->request = $request;
        $this->em = $em;
        $this->logRepository = $logRepository;
    }

    /**
     * @throws Exception
     */
    public function __invoke(): HttpResponse
    {
        if (!$this->plugin->isEnabled(true)) {
            throw new Exception();
        }

        $trackExe = $this->em->find(
            TrackEExercises::class,
            $this->request->query->getInt('id')
        );

        if (!$trackExe) {
            throw new Exception();
        }

        $exercise = $this->em->find(CQuiz::class, $trackExe->getExeExoId());
        $user = api_get_user_entity($trackExe->getExeUserId());

        $objExercise = new Exercise($trackExe->getCId());
        $objExercise->read($trackExe->getExeExoId());

        $logs = $this->logRepository->findSnapshots($objExercise, $trackExe);

        $content = $this->generateHeader($objExercise, $user, $trackExe)
            .'<hr>'
            .$this->generateSnapshotList($logs, $trackExe->getExeUserId());

        return HttpResponse::create($content);
    }

    private function generateSnapshotList(array $logs, int $userId): string
    {
        $html = '';

        foreach ($logs as $i => $log) {
            $date = api_get_local_time($log['createdAt'], null, null, true, true, true);

            $html .= '<div class="col-xs-12 col-sm-6 col-md-3" style="clear: '.($i % 4 === 0 ? 'both' : 'none').';">';
            $html .= '<div class="thumbnail">';
            $html .= Display::img(
                ExerciseMonitoringPlugin::generateSnapshotUrl($userId, $log['imageFilename']),
                $date
            );
            $html .= '<div class="caption">';
            $html .= Display::tag('p', $date, ['class' => 'text-center']);
            $html .= Display::tag('div', $log['log_level'], ['class' => 'text-center']);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        return '<div class="row">'.$html.'</div>';
    }
}
