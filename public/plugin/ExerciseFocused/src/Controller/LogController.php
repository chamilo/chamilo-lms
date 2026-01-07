<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use ChamiloSession;
use Exercise;
use ExerciseFocusedPlugin;
use Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LogController extends BaseController
{
    public const VALID_ACTIONS = [
        Log::TYPE_OUTFOCUSED,
        Log::TYPE_RETURN,
        Log::TYPE_OUTFOCUSED_LIMIT,
        Log::TYPE_TIME_LIMIT,
    ];

    public function __invoke(): Response
    {
        parent::__invoke();

        $tokenIsValid = Security::check_token('get', null, 'exercisefocused');

        if (!$tokenIsValid) {
            throw new AccessDeniedHttpException('token invalid');
        }

        $action = $this->request->query->get('action');
        $levelId = $this->request->query->getInt('level_id');

        $exeId = (int) ChamiloSession::read('exe_id');

        if (!in_array($action, self::VALID_ACTIONS)) {
            throw new AccessDeniedHttpException('action invalid');
        }

        $trackingExercise = $this->em->find(TrackEExercise::class, $exeId);

        if (!$trackingExercise) {
            throw new NotFoundHttpException('no exercise attempt');
        }

        $objExercise = new Exercise($trackingExercise->getCId());
        $objExercise->read($trackingExercise->getExeExoId());

        $level = 0;

        if (ONE_PER_PAGE == $objExercise->selectType()) {
            $question = $this->em->find(CQuizQuestion::class, $levelId);

            if (!$question) {
                throw new NotFoundHttpException('Invalid level');
            }

            $level = $question->getIid();
        }

        $log = new Log();
        $log
            ->setAction($action)
            ->setExe($trackingExercise)
            ->setLevel($level);

        $this->em->persist($log);
        $this->em->flush();

        $remainingOutfocused = -1;

        if ('true' === $this->plugin->get(ExerciseFocusedPlugin::SETTING_ENABLE_OUTFOCUSED_LIMIT)) {
            $countOutfocused = $this->logRepository->countByActionInExe($trackingExercise, Log::TYPE_OUTFOCUSED);

            $remainingOutfocused = (int) $this->plugin->get(ExerciseFocusedPlugin::SETTING_OUTFOCUSED_LIMIT) - $countOutfocused;
        }

        $exercise = new Exercise(api_get_course_int_id());
        $exercise->read($trackingExercise->getExeExoId());

        $json = [
            'sec_token' => Security::get_token('exercisefocused'),
            'remainingOutfocused' => $remainingOutfocused,
        ];

        return new JsonResponse($json);
    }
}
