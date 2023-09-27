<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ExerciseSubmitController
{
    private $plugin;
    private $request;
    private $em;

    public function __construct(ExerciseMonitoringPlugin $plugin, HttpRequest $request, EntityManager $em)
    {
        $this->plugin = $plugin;
        $this->request = $request;
        $this->em = $em;
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function __invoke(): HttpResponse
    {
        $userDirName = $this->createDirectory();

        $existingExeId = (int) ChamiloSession::read('exe_id');

        $levelId = $this->request->request->getInt('level_id');
        $exercise = $this->em->find(
            CQuiz::class,
            $this->request->request->getInt('exercise_id')
        );

        $trackingExercise = $this->em->find(TrackEExercises::class, $existingExeId);

        $newFilename = '';
        $level = 0;

        /** @var UploadedFile $imgSubmit */
        if ($imgSubmit = $this->request->files->get('snapshot')) {
            $newFilename = uniqid().'_submit.jpg';

            $imgSubmit->move($userDirName, $newFilename);
        }

        if (ONE_PER_PAGE === $exercise->getType()) {
            $question = $this->em->find(CQuizQuestion::class, $levelId);
            $level = $question->getIid();
        }

        $log = new Log();
        $log
            ->setExercise($exercise)
            ->setExe($trackingExercise)
            ->setLevel($level)
            ->setImageFilename($newFilename)
        ;

        $this->em->persist($log);

        $this->em->flush();

        return HttpResponse::create();
    }

    private function createDirectory(): string
    {
        $user = api_get_user_entity(api_get_user_id());

        $pluginDirName = api_get_path(SYS_UPLOAD_PATH).'plugins/exercisemonitoring';
        $userDirName = $pluginDirName.'/'.$user->getId();

        $fs = new Filesystem();
        $fs->mkdir(
            [$pluginDirName, $userDirName],
            api_get_permissions_for_new_directories()
        );

        return $userDirName;
    }
}
