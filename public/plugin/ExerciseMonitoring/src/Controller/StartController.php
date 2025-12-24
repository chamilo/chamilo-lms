<?php

/* For license terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StartController
{
    public function __construct(
        private readonly ExerciseMonitoringPlugin $plugin,
        private readonly Request $request,
        private readonly EntityManager $em
    ) {}

    public function __invoke(): Response
    {
        $userDirName = $this->createDirectory();

        /** @var UploadedFile $imgIddoc */
        $imgIddoc = $this->request->files->get('iddoc');
        /** @var UploadedFile $imgLearner */
        $imgLearner = $this->request->files->get('learner');

        $exercise = $this->em->find(CQuiz::class, $this->request->request->getInt('exercise_id'));

        $fileNamesToUpdate = [];

        if ($imgIddoc) {
            $newFilename = uniqid().'_iddoc.jpg';
            $fileNamesToUpdate[] = $newFilename;

            $imgIddoc->move($userDirName, $newFilename);

            $log = new Log();
            $log
                ->setExercise($exercise)
                ->setLevel(-1)
                ->setImageFilename($newFilename)
            ;

            $this->em->persist($log);
        }

        if ($imgLearner) {
            $newFilename = uniqid().'_learner.jpg';
            $fileNamesToUpdate[] = $newFilename;

            $imgLearner->move($userDirName, $newFilename);

            $log = new Log();
            $log
                ->setExercise($exercise)
                ->setLevel(-1)
                ->setImageFilename($newFilename)
            ;

            $this->em->persist($log);
        }

        $this->em->flush();

        ChamiloSession::write($this->plugin->get_name().'_orphan_snapshots', $fileNamesToUpdate);

        return new Response();
    }

    private function createDirectory(): string
    {
        $user = api_get_user_entity(api_get_user_id());

        $pluginDirName = api_get_path(SYS_UPLOAD_PATH).'plugins/ExerciseMonitoring';
        $userDirName = $pluginDirName.'/'.$user->getId();

        $fs = new Filesystem();
        $fs->mkdir(
            [$pluginDirName, $userDirName],
            api_get_permissions_for_new_directories()
        );

        return $userDirName;
    }
}
