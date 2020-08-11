<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Factory;

use SM\Callback\CallbackFactoryInterface;
use SM\SMException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Factory extends AbstractFactory
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var CallbackFactoryInterface
     */
    protected $callbackFactory;

    public function __construct(
        array $configs,
        EventDispatcherInterface $dispatcher      = null,
        CallbackFactoryInterface $callbackFactory = null
    ) {
        parent::__construct($configs);

        $this->dispatcher      = $dispatcher;
        $this->callbackFactory = $callbackFactory;
    }

    /**
     * {@inheritDoc}
     */
    protected function createStateMachine($object, array $config)
    {
        if (!isset($config['state_machine_class'])) {
            $class = 'SM\\StateMachine\\StateMachine';
        } elseif (class_exists($config['state_machine_class'])) {
            $class = $config['state_machine_class'];
        } else {
            throw new SMException(sprintf(
               'Class "%s" for creating a new state machine does not exist.',
                $config['state_machine_class']
            ));
        }

        return new $class($object, $config, $this->dispatcher, $this->callbackFactory);
    }
}
