<?php

namespace Knp\Component\Pager;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\SortableSubscriber;
use Knp\Component\Pager\Event;

/**
 * Paginator uses event dispatcher to trigger pagination
 * lifecycle events. Subscribers are expected to paginate
 * wanted target and finally it generates pagination view
 * which is only the result of paginator
 */
class Paginator implements PaginatorInterface
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Default options of paginator
     *
     * @var array
     */
    protected $defaultOptions = array(
        'pageParameterName' => 'page',
        'sortFieldParameterName' => 'sort',
        'sortDirectionParameterName' => 'direction',
        'filterFieldParameterName' => 'filterParam',
        'filterValueParameterName' => 'filterValue',
        'distinct' => true
    );

    /**
     * Initialize paginator with event dispatcher
     * Can be a service in concept. By default it
     * hooks standard pagination subscriber
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        if (is_null($this->eventDispatcher)) {
            $this->eventDispatcher = new EventDispatcher;
            $this->eventDispatcher->addSubscriber(new PaginationSubscriber);
            $this->eventDispatcher->addSubscriber(new SortableSubscriber);
        }
    }

    /**
     * Override the default paginator options
     * to be reused for paginations
     *
     * @param array $options
     */
    public function setDefaultPaginatorOptions(array $options)
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * Paginates anything (depending on event listeners)
     * into Pagination object, which is a view targeted
     * pagination object (might be aggregated helper object)
     * responsible for the pagination result representation
     *
     * @param mixed $target - anything what needs to be paginated
     * @param integer $page - page number, starting from 1
     * @param integer $limit - number of items per page
     * @param array $options - less used options:
     *     boolean $distinct - default true for distinction of results
     *     string $alias - pagination alias, default none
     *     array $whitelist - sortable whitelist for target fields being paginated
     * @throws \LogicException
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function paginate($target, $page = 1, $limit = 10, array $options = array())
    {
        $limit = intval(abs($limit));
        if (!$limit) {
            throw new \LogicException("Invalid item per page number, must be a positive number");
        }
        $offset = abs($page - 1) * $limit;
        $options = array_merge($this->defaultOptions, $options);
        
        // default sort field and direction are set based on options (if available)
        if (!isset($_GET[$options['sortFieldParameterName']]) && isset($options['defaultSortFieldName'])) {
            $_GET[$options['sortFieldParameterName']] = $options['defaultSortFieldName'];
            
            if (!isset($_GET[$options['sortDirectionParameterName']])) {
                $_GET[$options['sortDirectionParameterName']] = isset($options['defaultSortDirection']) ? $options['defaultSortDirection'] : 'asc';
            }
        }
        
        // before pagination start
        $beforeEvent = new Event\BeforeEvent($this->eventDispatcher);
        $this->eventDispatcher->dispatch('knp_pager.before', $beforeEvent);
        // items
        $itemsEvent = new Event\ItemsEvent($offset, $limit);
        $itemsEvent->options = &$options;
        $itemsEvent->target = &$target;
        $this->eventDispatcher->dispatch('knp_pager.items', $itemsEvent);
        if (!$itemsEvent->isPropagationStopped()) {
            throw new \RuntimeException('One of listeners must count and slice given target');
        }
        // pagination initialization event
        $paginationEvent = new Event\PaginationEvent;
        $paginationEvent->target = &$target;
        $paginationEvent->options = &$options;
        $this->eventDispatcher->dispatch('knp_pager.pagination', $paginationEvent);
        if (!$paginationEvent->isPropagationStopped()) {
            throw new \RuntimeException('One of listeners must create pagination view');
        }
        // pagination class can be diferent, with diferent rendering methods
        $paginationView = $paginationEvent->getPagination();
        $paginationView->setCustomParameters($itemsEvent->getCustomPaginationParameters());
        $paginationView->setCurrentPageNumber($page);
        $paginationView->setItemNumberPerPage($limit);
        $paginationView->setTotalItemCount($itemsEvent->count);
        $paginationView->setPaginatorOptions($options);
        $paginationView->setItems($itemsEvent->items);

        // after
        $afterEvent = new Event\AfterEvent($paginationView);
        $this->eventDispatcher->dispatch('knp_pager.after', $afterEvent);
        return $paginationView;
    }

    /**
     * Hooks in the given event subscriber
     *
     * @param \Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
     */
    public function subscribe(EventSubscriberInterface $subscriber)
    {
        $this->eventDispatcher->addSubscriber($subscriber);
    }

    /**
     * Hooks the listener to the given event name
     *
     * @param string $eventName
     * @param object $listener
     * @param integer $priority
     */
    public function connect($eventName, $listener, $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }
}
