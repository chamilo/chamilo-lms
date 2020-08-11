<?php
namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;


/**
 * As is hydrator to fetch count query result without resultSetMappings etc.
 *
 * @author Vladimir Chub <v@chub.com.ua>
 */
class AsIsHydrator extends AbstractHydrator
{
    /**
     * Hydrates all rows from the current statement instance at once.
     */
    protected function hydrateAllData()
    {
        return $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
