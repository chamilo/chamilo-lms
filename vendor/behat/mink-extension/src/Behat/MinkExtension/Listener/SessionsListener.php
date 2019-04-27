<?php

/*
 * This file is part of the Behat MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\MinkExtension\Listener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\ServiceContainer\Exception\ProcessingException;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Behat\Testwork\Suite\Suite;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Mink sessions listener.
 * Listens Behat events and configures/stops Mink sessions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class SessionsListener implements EventSubscriberInterface
{
    private $mink;
    private $defaultSession;
    private $javascriptSession;

    /**
     * @var string[] The available javascript sessions
     */
    private $availableJavascriptSessions;

    /**
     * Initializes initializer.
     *
     * @param Mink        $mink
     * @param string      $defaultSession
     * @param string|null $javascriptSession
     * @param string[]    $availableJavascriptSessions
     */
    public function __construct(Mink $mink, $defaultSession, $javascriptSession, array $availableJavascriptSessions = array())
    {
        $this->mink              = $mink;
        $this->defaultSession    = $defaultSession;
        $this->javascriptSession = $javascriptSession;
        $this->availableJavascriptSessions = $availableJavascriptSessions;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScenarioTested::BEFORE   => array('prepareDefaultMinkSession', 10),
            ExampleTested::BEFORE    => array('prepareDefaultMinkSession', 10),
            ExerciseCompleted::AFTER => array('tearDownMinkSessions', -10)
        );
    }

    /**
     * Configures default Mink session before each scenario.
     * Configuration is based on provided scenario tags:
     *
     * `@javascript` tagged scenarios will get `javascript_session` as default session
     * `@mink:CUSTOM_NAME tagged scenarios will get `CUSTOM_NAME` as default session
     * Other scenarios get `default_session` as default session
     *
     * `@insulated` tag will cause Mink to stop current sessions before scenario
     * instead of just soft-resetting them
     *
     * @param ScenarioLikeTested $event
     *
     * @throws ProcessingException when the @javascript tag is used without a javascript session
     */
    public function prepareDefaultMinkSession(ScenarioLikeTested $event)
    {
        $scenario = $event->getScenario();
        $feature  = $event->getFeature();
        $session  = null;

        foreach (array_merge($feature->getTags(), $scenario->getTags()) as $tag) {
            if ('javascript' === $tag) {
                $session = $this->getJavascriptSession($event->getSuite());
            } elseif (preg_match('/^mink\:(.+)/', $tag, $matches)) {
                $session = $matches[1];
            }
        }

        if (null === $session) {
            $session = $this->getDefaultSession($event->getSuite());
        }

        if ($scenario->hasTag('insulated') || $feature->hasTag('insulated')) {
            $this->mink->stopSessions();
        } else {
            $this->mink->resetSessions();
        }

        $this->mink->setDefaultSessionName($session);
    }

    /**
     * Stops all started Mink sessions.
     */
    public function tearDownMinkSessions()
    {
        $this->mink->stopSessions();
    }

    private function getDefaultSession(Suite $suite)
    {
        if (!$suite->hasSetting('mink_session')) {
            return $this->defaultSession;
        }

        $session = $suite->getSetting('mink_session');

        if (!is_string($session)) {
            throw new SuiteConfigurationException(
                sprintf(
                    '`mink_session` setting of the "%s" suite is expected to be a string, %s given.',
                    $suite->getName(),
                    gettype($session)
                ),
                $suite->getName()
            );
        }

        return $session;
    }

    private function getJavascriptSession(Suite $suite)
    {
        if (!$suite->hasSetting('mink_javascript_session')) {
            if (null === $this->javascriptSession) {
                throw new ProcessingException('The @javascript tag cannot be used without enabling a javascript session');
            }

            return $this->javascriptSession;
        }

        $session = $suite->getSetting('mink_javascript_session');

        if (!is_string($session)) {
            throw new SuiteConfigurationException(
                sprintf(
                    '`mink_javascript_session` setting of the "%s" suite is expected to be a string, %s given.',
                    $suite->getName(),
                    gettype($session)
                ),
                $suite->getName()
            );
        }

        if (!in_array($session, $this->availableJavascriptSessions)) {
            throw new SuiteConfigurationException(
                sprintf(
                    '`mink_javascript_session` setting of the "%s" suite is not a javascript session. %s given but expected one of %s.',
                    $suite->getName(),
                    $session,
                    implode(', ', $this->availableJavascriptSessions)
                ),
                $suite->getName()
            );
        }

        return $session;
    }
}
