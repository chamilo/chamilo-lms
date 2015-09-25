<?php

namespace Behat\MinkExtension\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Behat\Behat\Event\StepEvent;

use Behat\Mink\Mink;
use Behat\Mink\Exception\Exception as MinkException;

/*
 * This file is part of the Behat\MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Failed step response show listener.
 * Listens to failed Behat steps and shows last response in a browser.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FailureShowListener implements EventSubscriberInterface
{
    private $mink;
    private $parameters;

    /**
     * Initializes initializer.
     *
     * @param Mink  $mink
     * @param array $parameters
     */
    public function __construct(Mink $mink, array $parameters)
    {
        $this->mink       = $mink;
        $this->parameters = $parameters;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'afterStep' => array('showFailedStepResponse', -10)
        );
    }

    /**
     * Shows last response of failed step with preconfigured command.
     * Configuration is based on `behat.yml`:
     *
     * `show_auto` enable this listener (default to false)
     * `show_cmd` command to run (`open %s` to open default browser on Mac)
     * `show_tmp_dir` folder where to store temp files (default is system temp)
     *
     * @param StepEvent $event
     */
    public function showFailedStepResponse($event)
    {
        if (StepEvent::FAILED !== $event->getResult()) {
            return;
        }
        
        if (!$event->getException() instanceof MinkException) {
            return;
        }

        if (null === $this->parameters['show_cmd']) {
            throw new \RuntimeException('Set "show_cmd" parameter in behat.yml to be able to open page in browser (ex.: "show_cmd: open %s")');
        }

        $filename = rtrim($this->parameters['show_tmp_dir'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.uniqid().'.html';
        file_put_contents($filename, $this->mink->getSession()->getPage()->getContent());
        system(sprintf($this->parameters['show_cmd'], escapeshellarg($filename)));
    }
}
