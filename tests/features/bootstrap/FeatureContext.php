<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
    }
    /**
     * @Given /^I am a platform administrator$/
     */
    public function iAmAPlatformAdministrator()
    {
        return array(
            new \Behat\Behat\Context\Step\Given('I am on homepage'),
            new \Behat\Behat\Context\Step\Given('I fill in "login" with "admin"'),
            new  \Behat\Behat\Context\Step\Given('I fill in "password" with "admin"'),
            new  \Behat\Behat\Context\Step\Given('I press "submitAuth"')
        );
    }

}
