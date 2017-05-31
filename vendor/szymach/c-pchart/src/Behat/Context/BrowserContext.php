<?php

namespace CpChart\Behat\Context;

/**
 * @author Piotr Szymaszek
 */
class BrowserContext extends MinkAwarePageContext
{
    /**
     * @Given I open the :name page
     */
    public function iOpenTheIndexPage($name)
    {
        $this->getPage($name)->open();
    }

    /**
     * @Then there should be a :name header with value :expectedValue set in the response
     */
    public function thereShouldBeAHeaderSetInTheResponse($name, $expectedValue)
    {
        $currentValue = $this->getSession()->getResponseHeader($name);
        expect($currentValue === $expectedValue)->toBe(true);
    }
}
