<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Display;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Exception;
use HTML_Table;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DetailController extends BaseController
{
    /**
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ORMException
     * @throws Exception
     */
    public function __invoke(): HttpResponse
    {
        parent::__invoke();

        $exeId = $this->request->query->getInt('id');
        $exe = $this->em->find(TrackEExercises::class, $exeId);

        if (!$exe) {
            throw new Exception();
        }

        $session = api_get_session_entity($exe->getSessionId());
        $user = api_get_user_entity($exe->getExeUserId());

        $subHeader = Display::page_subheader($user->getCompleteNameWithUsername());
        $subHeader2 = $session ? Display::page_subheader2($session->getName()) : '';

        $logs = $this->logRepository->findBy(['exe' => $exe], ['updatedAt' => 'ASC']);
        $table = $this->getTable($logs);

        return HttpResponse::create(
            $subHeader.$subHeader2.$table->toHtml()
        );
    }

    /**
     * @param array<int, Log> $logs
     *
     * @return void
     */
    private function getTable(array $logs): HTML_Table
    {
        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaderContents(0, 0, get_lang('Action'));
        $table->setHeaderContents(0, 1, get_lang('DateTime'));

        $row = 1;

        foreach ($logs as $log) {
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

            $row++;
        }

        return $table;
    }
}
