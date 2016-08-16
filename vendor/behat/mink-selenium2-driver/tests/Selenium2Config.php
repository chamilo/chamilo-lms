<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\Selenium2Driver;

class Selenium2Config extends AbstractConfig
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
        $browser = $_SERVER['WEB_FIXTURES_BROWSER'];
        $seleniumHost = $_SERVER['DRIVER_URL'];

        return new Selenium2Driver($browser, null, $seleniumHost);
    }

    /**
     * {@inheritdoc}
     */
    public function skipMessage($testCase, $test)
    {
        if ('phantomjs' === getenv('WEBDRIVER') && null !== $message = $this->skipPhantomJs($testCase, $test)) {
            return $message;
        }

        if (
            'phantomjs' !== getenv('WEBDRIVER')
            && 'Behat\Mink\Tests\Driver\Form\Html5Test' === $testCase
            && 'testHtml5Types' === $test
        ) {
            return 'WebDriver does not support setting value in color inputs. See https://code.google.com/p/selenium/issues/detail?id=7650';
        }

        if (
            'Behat\Mink\Tests\Driver\Js\WindowTest' === $testCase
            && 'testWindowMaximize' === $test
            && 'true' === getenv('TRAVIS')
        ) {
            return 'Maximizing the window does not work when running the browser in Xvfb.';
        }

        return parent::skipMessage($testCase, $test);
    }

    /**
     * {@inheritdoc}
     */
    protected function supportsCss()
    {
        return true;
    }

    private function skipPhantomJs($testCase, $test)
    {
        if (
            'Behat\Mink\Tests\Driver\Js\WindowTest' === $testCase
            && in_array($test, array('testResizeWindow', 'testWindowMaximize'))
        ) {
            return 'PhantomJS is headless so resizing the window does not make sense.';
        }


        if (
            'Behat\Mink\Tests\Driver\Basic\CookieTest' === $testCase
            && 'testHttpOnlyCookieIsDeleted' === $test
        ) {
            return 'This test does not work for PhantomJS. See https://github.com/detro/ghostdriver/issues/170';
        }

        return null;
    }
}
