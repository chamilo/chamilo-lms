<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Callback;

use SM\Event\TransitionEvent;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Callback implements CallbackInterface
{
    /**
     * @var array
     */
    protected $specs;

    /**
     * @var mixed
     */
    protected $callable;

    /**
     * @param array $specs    Specification for the Callback to be called
     * @param mixed $callable Closure or Callable that will be called if specifications pass
     */
    public function __construct(array $specs, $callable)
    {
        foreach (array('from', 'to', 'on', 'excluded_from', 'excluded_to', 'excluded_on') as $clause) {
            if (!isset($specs[$clause])) {
                $specs[$clause] = array();
            } elseif (!is_array($specs[$clause])) {
                $specs[$clause] = array($specs[$clause]);
            }
        }

        $this->specs = $specs;
        $this->callable = $callable;
    }

    /**
     * @param TransitionEvent $event
     *
     * @return mixed The returned value from the callback
     */
    public function call(TransitionEvent $event)
    {
        if (!isset($this->specs['args'])) {
            $args = array($event);
        } else {
            $expr = new ExpressionLanguage();
            $args = array_map(
                function($arg) use($expr, $event) {
                    return $expr->evaluate($arg, array(
                        'object' => $event->getStateMachine()->getObject(),
                        'event'  => $event
                    ));
                }, $this->specs['args']
            );
        }

        return call_user_func_array($this->callable, $args);
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(TransitionEvent $event)
    {
        if ($this->isSatisfiedBy($event)) {
            return $this->call($event);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isSatisfiedBy(TransitionEvent $event)
    {
        $config = $event->getConfig();

        return
            $this->isSatisfiedByClause('on', $event->getTransition())
            && $this->isSatisfiedByClause('from', $event->getState())
            && $this->isSatisfiedByClause('to', $config['to'])
        ;
    }

    /**
     * @param string $clause The clause to check (on, from or to)
     * @param string $value  The value to check the clause against
     *
     * @return bool
     */
    protected function isSatisfiedByClause($clause, $value)
    {
        if (0 < count($this->specs[$clause]) && !in_array($value, $this->specs[$clause])) {
            return false;
        }

        if (0 < count($this->specs['excluded_'.$clause]) && in_array($value, $this->specs['excluded_'.$clause])) {
            return false;
        }

        return true;
    }
}
