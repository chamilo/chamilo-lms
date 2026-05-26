<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Doctrine\ORM\EntityManager;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ExerciseSubmitController
{
    public function __construct(
        private readonly ExerciseMonitoringPlugin $plugin,
        private readonly HttpRequest $request,
        private readonly EntityManager $em,
        private readonly FilesystemOperator $pluginsFilesystem
    ) {}

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function __invoke(): HttpResponse
    {
        $userId = api_get_user_id();
        $dirPath = 'ExerciseMonitoring/'.$userId;

        $existingExeId = (int) ChamiloSession::read('exe_id');

        $levelId = $this->request->request->getInt('level_id');
        $exerciseId = $this->request->request->getInt('exercise_id');

        $exercise = $this->em->find(CQuiz::class, $exerciseId);

        $objExercise = new Exercise();
        $objExercise->read($exerciseId);

        $trackingExercise = $this->em->find(TrackEExercise::class, $existingExeId);

        $filePath = '';
        $level = 0;

        /** @var UploadedFile $imgSubmit */
        if ($imgSubmit = $this->request->files->get('snapshot')) {
            $newFilename = uniqid().'_submit.jpg';
            $filePath = $dirPath.'/'.$newFilename;

            $this->pluginsFilesystem->write($filePath, $imgSubmit->getContent());
        }

        if (ONE_PER_PAGE == $objExercise->selectType()) {
            $question = $this->em->find(CQuizQuestion::class, $levelId);
            $level = $question->getIid();
        }

        $log = new Log();
        $log
            ->setExercise($exercise)
            ->setExe($trackingExercise)
            ->setLevel($level)
            ->setImageFilename($filePath)
        ;

        $this->em->persist($log);

        $this->updateOrphanSnapshots($exercise, $trackingExercise);

        $this->em->flush();

        return new HttpResponse();
    }

    private function updateOrphanSnapshots(CQuiz $exercise, TrackEExercise $trackingExe): void
    {
        $repo = $this->em->getRepository(Log::class);

        $fileNamesToUpdate = ChamiloSession::read($this->plugin->get_name().'_orphan_snapshots', []);

        if (empty($fileNamesToUpdate)) {
            return;
        }

        foreach ($fileNamesToUpdate as $filePath) {
            $log = $repo->findOneBy(['imageFilename' => $filePath, 'exercise' => $exercise, 'exe' => null]);

            if (!$log) {
                continue;
            }

            $log->setExe($trackingExe);
        }

        ChamiloSession::erase($this->plugin->get_name().'_orphan_snapshots');
    }
}