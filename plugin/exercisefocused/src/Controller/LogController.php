<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use ChamiloSession;
use Exception;
use Exercise;
use Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LogController extends BaseController
{
    public const VALID_ACTIONS = [
        Log::TYPE_OUTFOCUSED,
        Log::TYPE_RETURN,
        Log::TYPE_ABANDONMENT_LIMIT,
        Log::TYPE_TIME_LIMIT,
    ];

    /**
     * @throws Exception
     */
    public function __invoke(): Response
    {
        parent::__invoke();

        $tokenIsValid = Security::check_token('get', null, 'exercisefocused');

        if (!$tokenIsValid) {
            throw new Exception();
        }

        $action = $this->request->query->get('action');
        $exeId = (int) ChamiloSession::read('exe_id');

        if (!in_array($action, self::VALID_ACTIONS)) {
            throw new Exception();
        }

        $trackingExercise = $this->em->find(TrackEExercises::class, $exeId);

        if (!$trackingExercise) {
            throw new Exception();
        }

        $log = new Log();
        $log
            ->setAction($action)
            ->setExe($trackingExercise);

        $this->em->persist($log);
        $this->em->flush();

        $remainingAbandonments = -1;

        if ('true' === $this->plugin->get('enable_abandonment_limit')) {
            $countAbandonments = $this->logRepository->countByActionInExe($trackingExercise, Log::TYPE_OUTFOCUSED);

            $remainingAbandonments = (int) $this->plugin->get('abandonment_limit') - $countAbandonments;
        }

        $exercise = new Exercise(api_get_course_int_id());
        $exercise->read($trackingExercise->getExeExoId());

        $json = [
            'sec_token' => Security::get_token('exercisefocused'),
            'type' => (int) $exercise->selectType(),
            'remainingAbandonments' => $remainingAbandonments,
        ];

        return JsonResponse::create($json);
    }
}
