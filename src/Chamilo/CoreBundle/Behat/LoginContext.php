<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Behat;

use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Gherkin\Node\TableNode;

/**
 * Class LoginContext
 */
class LoginContext extends MinkContext
{
    /**
     * First, force logout, then go to the login page, fill the informations and finally go to requested page
     *
     * @Given /^I am connected with "([^"]*)" and "([^"]*)" on "([^"]*)"$/
     *
     * @param string $login
     * @param string $rawPassword
     * @param string $url
     */
    public function iAmConnectedWithOn($login, $rawPassword, $url)
    {
        $this->visit('logout');
        $this->visit('login');
        $this->fillField('_username', $login);
        $this->fillField('_password', $rawPassword);
        $this->pressButton('Login');

        $this->visit($url);
    }
}
