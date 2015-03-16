<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {

    }

    /**
     * Check Chamilo is installed - otherwise try to install it
     * @param   BeforeSuiteScope $scope The context scope
     * @BeforeSuite
     */
    public static function prepare($scope)
    {
        // prepare system for test suite
        // before it runs
        require __DIR__.'/../../../main/inc/lib/api.lib.php';
        $installed = apiIsSystemInstalled();
        if ($installed['installed'] == 0) {
            // Try to install Chamilo
            //apiInstallChamilo();
        } else {
            // show version
        }
        require __DIR__.'/../../../main/inc/global.inc.php';
    }

    /**
     * @Given I am logged in
     */
    public function iAmLoggedIn()
    {
        if (api_get_user_id() == 0) {
            throw new Exception('I am not connected as a user yet');
        }
    }

    /**
     * @Given I am an administrator
     */
    public function iAmAnAdministrator()
    {
        if (!api_is_platform_admin()) {
            throw new Exception('I am not connected as an admin');
        }
    }

    /**
     * @When I create a user with e-mail :arg1
     */
    public function iCreateAUserWithEMail($email)
    {
        throw new PendingException();
    }

    /**
     * @Then the user should be added
     */
    public function theUserShouldBeAdded()
    {
        throw new PendingException();
    }

}
