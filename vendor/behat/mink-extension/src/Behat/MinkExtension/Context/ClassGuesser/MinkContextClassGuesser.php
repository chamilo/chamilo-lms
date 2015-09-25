<?php

namespace Behat\MinkExtension\Context\ClassGuesser;

use Behat\Behat\Context\ClassGuesser\ClassGuesserInterface;

/**
 * Mink context class guesser.
 * Provides Mink context class if no other class found.
 */
class MinkContextClassGuesser implements ClassGuesserInterface
{
    /**
     * Tries to guess context classname.
     *
     * @return string
     */
    public function guess()
    {
        return 'Behat\\MinkExtension\\Context\\MinkContext';
    }
}
