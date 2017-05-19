<?php

namespace CpChart\Behat\Context;

use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\MinkAwareContext;
use SensioLabs\Behat\PageObjectExtension\Context\PageObjectContext;

/**
 * @author Piotr Szymaszek
 */
abstract class MinkAwarePageContext extends PageObjectContext implements MinkAwareContext
{
    private $mink;
    private $minkParameters;

    /**
     * @param Mink $mink Mink session manager
     */
    public function setMink(Mink $mink)
    {
        $this->mink = $mink;
    }

    /**
     * @return Mink
     */
    public function getMink()
    {
        return $this->mink;
    }

    /**
     * @param array $parameters
     */
    public function setMinkParameters(array $parameters)
    {
        $this->minkParameters = $parameters;
    }

    /**
     * @param string|null $name name of the session OR active session will be used
     *
     * @return Session
     */
    public function getSession($name = null)
    {
        return $this->mink->getSession($name);
    }
}
