<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\Query\Parameter;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\CountWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\WhereInWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\LimitSubqueryWalker;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker as DoctrineCountWalker;
use Doctrine\ORM\Tools\Pagination\WhereInWalker as DoctrineWhereInWalker;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker as DoctrineLimitSubqueryWalker;

class QuerySubscriber implements EventSubscriberInterface
{
    /**
     * Used if user set the count manually
     */
    const HINT_COUNT = 'knp_paginator.count';

    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof Query) {
            // process count
            $useDoctrineWalkers = false;
            $useDoctrineOutputWalker = false;
            if (version_compare(\Doctrine\ORM\Version::VERSION, '2.3.0', '>=')) {
                $useDoctrineWalkers = true;
                $useDoctrineOutputWalker = true;
            } else if (version_compare(\Doctrine\ORM\Version::VERSION, '2.2.0', '>=')) {
                $useDoctrineWalkers = true;
            }
            if (($count = $event->target->getHint(self::HINT_COUNT)) !== false) {
                $event->count = intval($count);
            } else {
                $countQuery = QueryHelper::cloneQuery($event->target);
                if ($useDoctrineOutputWalker) {
                    $treeWalker = 'Doctrine\ORM\Tools\Pagination\CountOutputWalker';
                    $countQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, $treeWalker);
                } else if ($useDoctrineWalkers) {
                    QueryHelper::addCustomTreeWalker($countQuery, 'Doctrine\ORM\Tools\Pagination\CountWalker');
                } else {
                    $treeWalker = 'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\CountWalker';
                    QueryHelper::addCustomTreeWalker($countQuery, $treeWalker);
                }

                if ($useDoctrineWalkers) {
                    $countQuery->setHint(
                        DoctrineCountWalker::HINT_DISTINCT,
                        $event->options['distinct']
                    );
                } else {
                    $countQuery->setHint(
                        CountWalker::HINT_DISTINCT,
                        $event->options['distinct']
                    );
                }
                $countQuery
                    ->setFirstResult(null)
                    ->setMaxResults(null)
                ;

                $countQuery->getEntityManager()->getConfiguration()->addCustomHydrationMode('asIs',
                            'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\AsIsHydrator');
                $countResult = $countQuery->getResult('asIs');

                $event->count = intval(current(current($countResult)));
            }
            // process items
            $result = null;
            if ($event->count) {
                if ($event->options['distinct']) {
                    $limitSubQuery = QueryHelper::cloneQuery($event->target);
                    $limitSubQuery
                        ->setFirstResult($event->getOffset())
                        ->setMaxResults($event->getLimit())
                        ->useQueryCache(false)
                    ;
                    QueryHelper::addCustomTreeWalker($limitSubQuery, $useDoctrineWalkers ?
                        'Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker' :
                        'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\LimitSubqueryWalker'
                    );

                    $ids = array_map('current', $limitSubQuery->getScalarResult());
                    // create where-in query
                    $whereInQuery = QueryHelper::cloneQuery($event->target);
                    QueryHelper::addCustomTreeWalker($whereInQuery, $useDoctrineWalkers ?
                        'Doctrine\ORM\Tools\Pagination\WhereInWalker' :
                        'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\WhereInWalker'
                    );
                    $whereInQuery
                        ->setHint($useDoctrineWalkers ?
                            DoctrineWhereInWalker::HINT_PAGINATOR_ID_COUNT :
                            WhereInWalker::HINT_PAGINATOR_ID_COUNT, count($ids)
                        )
                        ->setFirstResult(null)
                        ->setMaxResults(null)
                    ;

                    if (version_compare(\Doctrine\ORM\Version::VERSION, '2.3.0', '>=') && count($ids) > 0) {
                        $whereInQuery->setParameter(WhereInWalker::PAGINATOR_ID_ALIAS, $ids);
                    } else {
                        $type = $limitSubQuery->getHint($useDoctrineWalkers ?
                            DoctrineLimitSubqueryWalker::IDENTIFIER_TYPE :
                            LimitSubqueryWalker::IDENTIFIER_TYPE
                        );
                        $idAlias = $useDoctrineWalkers ?
                            DoctrineWhereInWalker::PAGINATOR_ID_ALIAS :
                            WhereInWalker::PAGINATOR_ID_ALIAS
                        ;
                        foreach ($ids as $i => $id) {
                            $whereInQuery->setParameter(
                                $idAlias . '_' . ++$i,
                                $id,
                                $type->getName()
                            );
                        }
                    }
                    $result = $whereInQuery->execute();
                } else {
                    $event->target
                        ->setFirstResult($event->getOffset())
                        ->setMaxResults($event->getLimit())
                    ;
                    $result = $event->target->execute();
                }
            } else {
                $result = array(); // count is 0
            }
            $event->items = $result;
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}
