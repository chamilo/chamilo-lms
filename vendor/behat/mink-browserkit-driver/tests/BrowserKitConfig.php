<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Tests\Driver\Util\FixturesKernel;
use Symfony\Component\HttpKernel\Client;

class BrowserKitConfig extends AbstractConfig
{
    public static function getInstance()
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver()
    {
        $client = new Client(new FixturesKernel());

        return new BrowserKitDriver($client);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebFixturesUrl()
    {
        return 'http://localhost';
    }

    protected function supportsJs()
    {
        return false;
    }

    public function skipMessage($testCase, $test)
    {
        if (
            'Behat\Mink\Tests\Driver\Form\Html5Test' === $testCase
            && in_array($test, array(
                'testHtml5FormAction',
                'testHtml5FormMethod',
            ))
            && !method_exists('Symfony\Component\DomCrawler\Tests\FormTest', 'testGetMethodWithOverride')
            // Symfony 4.4 removed tests from dist archives, making the previous detection return a false-positive
            && !method_exists('Symfony\Component\DomCrawler\Form', 'getName')
        ) {
            return 'Mink BrowserKit doesn\'t support HTML5 form attributes before Symfony 3.3';
        }

        return parent::skipMessage($testCase, $test);
    }
}
