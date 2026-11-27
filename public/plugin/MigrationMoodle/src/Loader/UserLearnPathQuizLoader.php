<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathQuizLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathQuizLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $itemView = $this->findViewOfItem(
            $incomingData['item_id'],
            $incomingData['user_id'],
            $incomingData['session_id']
        );

        \Database::update(
            \Database::get_course_table(TABLE_LP_ITEM_VIEW),
            [
                'start_time' => $incomingData['start_time'],
                'total_time' => $incomingData['total_time'],
                'score' => $incomingData['score'],
                'status' => $incomingData['status'],
            ],
            ['iid = ?' => $itemView['iid']]
        );

        return $itemView['iid'];
    }

    /**
     * @param int $itemId
     * @param int $userId
     * @param int $sessionId
     *
     * @throws \Exception
     *
     * @return array
     */
    private function findViewOfItem($itemId, $userId, $sessionId)
    {
        $qb = \Database::getManager()
            ->createQueryBuilder()
            ->select('lpiv.iid')
            ->from(CLpItemView::class, 'lpiv')
            ->innerJoin('lpiv.view', 'lpv')
            ->where('lpiv.item = :itemId')
            ->andWhere('lpv.user = :userId')
            ->setParameter('itemId', (int) $itemId)
            ->setParameter('userId', (int) $userId)
            ->setMaxResults(1)
        ;

        // c_lp_view.session_id is NULL for "no session" (it used to be 0).
        if (empty($sessionId)) {
            $qb->andWhere('lpv.session IS NULL');
        } else {
            $qb->andWhere('lpv.session = :sessionId')->setParameter('sessionId', (int) $sessionId);
        }

        $itemView = $qb->getQuery()->getOneOrNullResult();

        if (!$itemView) {
            throw new \Exception("Item view not found for item ($itemId) and user ($userId) in session ($sessionId).");
        }

        return $itemView;
    }
}
