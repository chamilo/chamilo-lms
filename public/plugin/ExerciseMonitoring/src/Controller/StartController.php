<?php

/* For license terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Doctrine\ORM\EntityManager;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StartController
{
    public function __construct(
        private readonly ExerciseMonitoringPlugin $plugin,
        private readonly Request $request,
        private readonly EntityManager $em,
        private readonly FilesystemOperator $pluginsFilesystem
    ) {}

    public function __invoke(): Response
    {
        $userId = api_get_user_id();
        $dirPath = 'ExerciseMonitoring/'.$userId;

        /** @var UploadedFile $imgIddoc */
        $imgIddoc = $this->request->files->get('iddoc');
        /** @var UploadedFile $imgLearner */
        $imgLearner = $this->request->files->get('learner');

        $exercise = $this->em->find(CQuiz::class, $this->request->request->getInt('exercise_id'));

        $fileNamesToUpdate = [];

        if ($imgIddoc) {
            $newFilename = uniqid().'_iddoc.jpg';
            $filePath = $dirPath.'/'.$newFilename;

            $this->pluginsFilesystem->write($filePath, $imgIddoc->getContent());

            $log = new Log();
            $log
                ->setExercise($exercise)
                ->setLevel(-1)
                ->setImageFilename($filePath)
            ;

            $this->em->persist($log);
            $fileNamesToUpdate[] = $filePath;
        }

        if ($imgLearner) {
            $newFilename = uniqid().'_learner.jpg';
            $filePath = $dirPath.'/'.$newFilename;

            $this->pluginsFilesystem->write($filePath, $imgLearner->getContent());

            $log = new Log();
            $log
                ->setExercise($exercise)
                ->setLevel(-1)
                ->setImageFilename($filePath)
            ;

            $this->em->persist($log);
            $fileNamesToUpdate[] = $filePath;
        }

        $this->em->flush();

        ChamiloSession::write($this->plugin->get_name().'_orphan_snapshots', $fileNamesToUpdate);

        return new Response();
    }
}