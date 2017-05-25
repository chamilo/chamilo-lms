<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Driver;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Selector\Xpath\Escaper;
use WebDriver\Element;
use WebDriver\Exception\NoSuchElement;
use WebDriver\Exception\UnknownError;
use WebDriver\Exception;
use WebDriver\Key;
use WebDriver\WebDriver;

/**
 * Selenium2 driver.
 *
 * @author Pete Otaqui <pete@otaqui.com>
 */
class Selenium2Driver extends CoreDriver
{
    /**
     * Whether the browser has been started
     * @var Boolean
     */
    private $started = false;

    /**
     * The WebDriver instance
     * @var WebDriver
     */
    private $webDriver;

    /**
     * @var string
     */
    private $browserName;

    /**
     * @var array
     */
    private $desiredCapabilities;

    /**
     * The WebDriverSession instance
     * @var \WebDriver\Session
     */
    private $wdSession;

    /**
     * The timeout configuration
     * @var array
     */
    private $timeouts = array();

    /**
     * @var Escaper
     */
    private $xpathEscaper;

    /**
     * Instantiates the driver.
     *
     * @param string $browserName         Browser name
     * @param array  $desiredCapabilities The desired capabilities
     * @param string $wdHost              The WebDriver host
     */
    public function __construct($browserName = 'firefox', $desiredCapabilities = null, $wdHost = 'http://localhost:4444/wd/hub')
    {
        $this->setBrowserName($browserName);
        $this->setDesiredCapabilities($desiredCapabilities);
        $this->setWebDriver(new WebDriver($wdHost));
        $this->xpathEscaper = new Escaper();
    }

    /**
     * Sets the browser name
     *
     * @param string $browserName the name of the browser to start, default is 'firefox'
     */
    protected function setBrowserName($browserName = 'firefox')
    {
        $this->browserName = $browserName;
    }

    /**
     * Sets the desired capabilities - called on construction.  If null is provided, will set the
     * defaults as desired.
     *
     * See http://code.google.com/p/selenium/wiki/DesiredCapabilities
     *
     * @param array $desiredCapabilities an array of capabilities to pass on to the WebDriver server
     */
    public function setDesiredCapabilities($desiredCapabilities = null)
    {
        if (null === $desiredCapabilities) {
            $desiredCapabilities = self::getDefaultCapabilities();
        }

        if (isset($desiredCapabilities['firefox'])) {
            foreach ($desiredCapabilities['firefox'] as $capability => $value) {
                switch ($capability) {
                    case 'profile':
                        $desiredCapabilities['firefox_'.$capability] = base64_encode(file_get_contents($value));
                        break;
                    default:
                        $desiredCapabilities['firefox_'.$capability] = $value;
                }
            }

            unset($desiredCapabilities['firefox']);
        }

        // See https://sites.google.com/a/chromium.org/chromedriver/capabilities
        if (isset($desiredCapabilities['chrome'])) {

            $chromeOptions = array();

            foreach ($desiredCapabilities['chrome'] as $capability => $value) {
                if ($capability == 'switches') {
                    $chromeOptions['args'] = $value;
                } else {
                    $chromeOptions[$capability] = $value;
                }
                $desiredCapabilities['chrome.'.$capability] = $value;
            }

            $desiredCapabilities['chromeOptions'] = $chromeOptions;

            unset($desiredCapabilities['chrome']);
        }

        $this->desiredCapabilities = $desiredCapabilities;
    }

    /**
     * Sets the WebDriver instance
     *
     * @param WebDriver $webDriver An instance of the WebDriver class
     */
    public function setWebDriver(WebDriver $webDriver)
    {
        $this->webDriver = $webDriver;
    }

    /**
     * Gets the WebDriverSession instance
     *
     * @return \WebDriver\Session
     */
    public function getWebDriverSession()
    {
        return $this->wdSession;
    }

    /**
     * Returns the default capabilities
     *
     * @return array
     */
    public static function getDefaultCapabilities()
    {
        return array(
            'browserName'       => 'firefox',
            'version'           => '9',
            'platform'          => 'ANY',
            'browserVersion'    => '9',
            'browser'           => 'firefox',
            'name'              => 'Behat Test',
            'deviceOrientation' => 'portrait',
            'deviceType'        => 'tablet',
            'selenium-version'  => '2.31.0'
        );
    }

    /**
     * Makes sure that the Syn event library has been injected into the current page,
     * and return $this for a fluid interface,
     *
     *     $this->withSyn()->executeJsOnXpath($xpath, $script);
     *
     * @return Selenium2Driver
     */
    protected function withSyn()
    {
        $hasSyn = $this->wdSession->execute(array(
            'script' => 'return typeof window["Syn"]!=="undefined" && typeof window["Syn"].trigger!=="undefined"',
            'args'   => array()
        ));

        if (!$hasSyn) {
            $synJs = file_get_contents(__DIR__.'/Resources/syn.js');
            $this->wdSession->execute(array(
                'script' => $synJs,
                'args'   => array()
            ));
        }

        return $this;
    }

    /**
     * Creates some options for key events
     *
     * @param string $char     the character or code
     * @param string $modifier one of 'shift', 'alt', 'ctrl' or 'meta'
     *
     * @return string a json encoded options array for Syn
     */
    protected static function charToOptions($char, $modifier = null)
    {
        $ord = ord($char);
        if (is_numeric($char)) {
            $ord = $char;
        }

        $options = array(
            'keyCode'  => $ord,
            'charCode' => $ord
        );

        if ($modifier) {
            $options[$modifier.'Key'] = 1;
        }

        return json_encode($options);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the result of the $xpath query
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param string  $xpath  the xpath to search with
     * @param string  $script the script to execute
     * @param Boolean $sync   whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    protected function executeJsOnXpath($xpath, $script, $sync = true)
    {
        return $this->executeJsOnElement($this->findElement($xpath), $script, $sync);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the element
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param Element $element the webdriver element
     * @param string  $script  the script to execute
     * @param Boolean $sync    whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnElement(Element $element, $script, $sync = true)
    {
        $script  = str_replace('{{ELEMENT}}', 'arguments[0]', $script);

        $options = array(
            'script' => $script,
            'args'   => array(array('ELEMENT' => $element->getID())),
        );

        if ($sync) {
            return $this->wdSession->execute($options);
        }

        return $this->wdSession->execute_async($options);
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        try {
            $this->wdSession = $this->webDriver->session($this->browserName, $this->desiredCapabilities);
            $this->applyTimeouts();
        } catch (\Exception $e) {
            throw new DriverException('Could not open connection: '.$e->getMessage(), 0, $e);
        }

        if (!$this->wdSession) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }
        $this->started = true;
    }

    /**
     * Sets the timeouts to apply to the webdriver session
     *
     * @param array $timeouts The session timeout settings: Array of {script, implicit, page} => time in milliseconds
     *
     * @throws DriverException
     */
    public function setTimeouts($timeouts)
    {
        $this->timeouts = $timeouts;

        if ($this->isStarted()) {
            $this->applyTimeouts();
        }
    }

    /**
     * Applies timeouts to the current session
     */
    private function applyTimeouts()
    {
        try {
            foreach ($this->timeouts as $type => $param) {
                $this->wdSession->timeouts($type, $param);
            }
        } catch (UnknownError $e) {
            throw new DriverException('Error setting timeout: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        if (!$this->wdSession) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        $this->started = false;
        try {
            $this->wdSession->close();
        } catch (\Exception $e) {
            throw new DriverException('Could not close connection', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->wdSession->deleteAllCookies();
    }

    /**
     * {@inheritdoc}
     */
    public function visit($url)
    {
        $this->wdSession->open($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl()
    {
        return $this->wdSession->url();
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $this->wdSession->refresh();
    }

    /**
     * {@inheritdoc}
     */
    public function forward()
    {
        $this->wdSession->forward();
    }

    /**
     * {@inheritdoc}
     */
    public function back()
    {
        $this->wdSession->back();
    }

    /**
     * {@inheritdoc}
     */
    public function switchToWindow($name = null)
    {
        $this->wdSession->focusWindow($name ? $name : '');
    }

    /**
     * {@inheritdoc}
     */
    public function switchToIFrame($name = null)
    {
        $this->wdSession->frame(array('id' => $name));
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie($name, $value = null)
    {
        if (null === $value) {
            $this->wdSession->deleteCookie($name);

            return;
        }

        $cookieArray = array(
            'name'   => $name,
            'value'  => urlencode($value),
            'secure' => false, // thanks, chibimagic!
        );

        $this->wdSession->setCookie($cookieArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie($name)
    {
        $cookies = $this->wdSession->getAllCookies();
        foreach ($cookies as $cookie) {
            if ($cookie['name'] === $name) {
                return urldecode($cookie['value']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->wdSession->source();
    }

    /**
     * {@inheritdoc}
     */
    public function getScreenshot()
    {
        return base64_decode($this->wdSession->screenshot());
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowNames()
    {
        return $this->wdSession->window_handles();
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowName()
    {
        return $this->wdSession->window_handle();
    }

    /**
     * {@inheritdoc}
     */
    public function findElementXpaths($xpath)
    {
        $nodes = $this->wdSession->elements('xpath', $xpath);

        $elements = array();
        foreach ($nodes as $i => $node) {
            $elements[] = sprintf('(%s)[%d]', $xpath, $i+1);
        }

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagName($xpath)
    {
        return $this->findElement($xpath)->name();
    }

    /**
     * {@inheritdoc}
     */
    public function getText($xpath)
    {
        $node = $this->findElement($xpath);
        $text = $node->text();
        $text = (string) str_replace(array("\r", "\r\n", "\n"), ' ', $text);

        return $text;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.innerHTML;');
    }

    /**
     * {@inheritdoc}
     */
    public function getOuterHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.outerHTML;');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($xpath, $name)
    {
        $script = 'return {{ELEMENT}}.getAttribute(' . json_encode((string) $name) . ')';

        return $this->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($xpath)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->name());
        $elementType = strtolower($element->attribute('type'));

        // Getting the value of a checkbox returns its value if selected.
        if ('input' === $elementName && 'checkbox' === $elementType) {
            return $element->selected() ? $element->attribute('value') : null;
        }

        if ('input' === $elementName && 'radio' === $elementType) {
            $script = <<<JS
var node = {{ELEMENT}},
    value = null;

var name = node.getAttribute('name');
if (name) {
    var fields = window.document.getElementsByName(name),
        i, l = fields.length;
    for (i = 0; i < l; i++) {
        var field = fields.item(i);
        if (field.form === node.form && field.checked) {
            value = field.value;
            break;
        }
    }
}

return value;
JS;

            return $this->executeJsOnElement($element, $script);
        }

        // Using $element->attribute('value') on a select only returns the first selected option
        // even when it is a multiple select, so a custom retrieval is needed.
        if ('select' === $elementName && $element->attribute('multiple')) {
            $script = <<<JS
var node = {{ELEMENT}},
    value = [];

for (var i = 0; i < node.options.length; i++) {
    if (node.options[i].selected) {
        value.push(node.options[i].value);
    }
}

return value;
JS;

            return $this->executeJsOnElement($element, $script);
        }

        return $element->attribute('value');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->name());

        if ('select' === $elementName) {
            if (is_array($value)) {
                $this->deselectAllOptions($element);

                foreach ($value as $option) {
                    $this->selectOptionOnElement($element, $option, true);
                }

                return;
            }

            $this->selectOptionOnElement($element, $value);

            return;
        }

        if ('input' === $elementName) {
            $elementType = strtolower($element->attribute('type'));

            if (in_array($elementType, array('submit', 'image', 'button', 'reset'))) {
                throw new DriverException(sprintf('Impossible to set value an element with XPath "%s" as it is not a select, textarea or textbox', $xpath));
            }

            if ('checkbox' === $elementType) {
                if ($element->selected() xor (bool) $value) {
                    $this->clickOnElement($element);
                }

                return;
            }

            if ('radio' === $elementType) {
                $this->selectRadioValue($element, $value);

                return;
            }

            if ('file' === $elementType) {
                $element->postValue(array('value' => array(strval($value))));

                return;
            }
        }

        $value = strval($value);

        if (in_array($elementName, array('input', 'textarea'))) {
            $existingValueLength = strlen($element->attribute('value'));
            // Add the TAB key to ensure we unfocus the field as browsers are triggering the change event only
            // after leaving the field.
            $value = str_repeat(Key::BACKSPACE . Key::DELETE, $existingValueLength) . $value . Key::TAB;
        }

        $element->postValue(array('value' => array($value)));
    }

    /**
     * {@inheritdoc}
     */
    public function check($xpath)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'checkbox', 'check');

        if ($element->selected()) {
            return;
        }

        $this->clickOnElement($element);
    }

    /**
     * {@inheritdoc}
     */
    public function uncheck($xpath)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'checkbox', 'uncheck');

        if (!$element->selected()) {
            return;
        }

        $this->clickOnElement($element);
    }

    /**
     * {@inheritdoc}
     */
    public function isChecked($xpath)
    {
        return $this->findElement($xpath)->selected();
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $element = $this->findElement($xpath);
        $tagName = strtolower($element->name());

        if ('input' === $tagName && 'radio' === strtolower($element->attribute('type'))) {
            $this->selectRadioValue($element, $value);

            return;
        }

        if ('select' === $tagName) {
            $this->selectOptionOnElement($element, $value, $multiple);

            return;
        }

        throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
    }

    /**
     * {@inheritdoc}
     */
    public function isSelected($xpath)
    {
        return $this->findElement($xpath)->selected();
    }

    /**
     * {@inheritdoc}
     */
    public function click($xpath)
    {
        $this->clickOnElement($this->findElement($xpath));
    }

    private function clickOnElement(Element $element)
    {
        $this->wdSession->moveto(array('element' => $element->getID()));
        $element->click();
    }

    /**
     * {@inheritdoc}
     */
    public function doubleClick($xpath)
    {
        $this->mouseOver($xpath);
        $this->wdSession->doubleclick();
    }

    /**
     * {@inheritdoc}
     */
    public function rightClick($xpath)
    {
        $this->mouseOver($xpath);
        $this->wdSession->click(array('button' => 2));
    }

    /**
     * {@inheritdoc}
     */
    public function attachFile($xpath, $path)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'file', 'attach a file on');

        $element->postValue(array('value' => array($path)));
    }

    /**
     * {@inheritdoc}
     */
    public function isVisible($xpath)
    {
        return $this->findElement($xpath)->displayed();
    }

    /**
     * {@inheritdoc}
     */
    public function mouseOver($xpath)
    {
        $this->wdSession->moveto(array(
            'element' => $this->findElement($xpath)->getID()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function focus($xpath)
    {
        $script = 'Syn.trigger("focus", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function blur($xpath)
    {
        $script = 'Syn.trigger("blur", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $script = "Syn.trigger('keypress', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function keyDown($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $script = "Syn.trigger('keydown', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function keyUp($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $script = "Syn.trigger('keyup', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source      = $this->findElement($sourceXpath);
        $destination = $this->findElement($destinationXpath);

        $this->wdSession->moveto(array(
            'element' => $source->getID()
        ));

        $script = <<<JS
(function (element) {
    var event = document.createEvent("HTMLEvents");

    event.initEvent("dragstart", true, true);
    event.dataTransfer = {};

    element.dispatchEvent(event);
}({{ELEMENT}}));
JS;
        $this->withSyn()->executeJsOnElement($source, $script);

        $this->wdSession->buttondown();
        $this->wdSession->moveto(array(
            'element' => $destination->getID()
        ));
        $this->wdSession->buttonup();

        $script = <<<JS
(function (element) {
    var event = document.createEvent("HTMLEvents");

    event.initEvent("drop", true, true);
    event.dataTransfer = {};

    element.dispatchEvent(event);
}({{ELEMENT}}));
JS;
        $this->withSyn()->executeJsOnElement($destination, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function executeScript($script)
    {
        if (preg_match('/^function[\s\(]/', $script)) {
            $script = preg_replace('/;$/', '', $script);
            $script = '(' . $script . ')';
        }

        $this->wdSession->execute(array('script' => $script, 'args' => array()));
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateScript($script)
    {
        if (0 !== strpos(trim($script), 'return ')) {
            $script = 'return ' . $script;
        }

        return $this->wdSession->execute(array('script' => $script, 'args' => array()));
    }

    /**
     * {@inheritdoc}
     */
    public function wait($timeout, $condition)
    {
        $script = "return $condition;";
        $start = microtime(true);
        $end = $start + $timeout / 1000.0;

        do {
            $result = $this->wdSession->execute(array('script' => $script, 'args' => array()));
            usleep(100000);
        } while (microtime(true) < $end && !$result);

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function resizeWindow($width, $height, $name = null)
    {
        $this->wdSession->window($name ? $name : 'current')->postSize(
            array('width' => $width, 'height' => $height)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm($xpath)
    {
        $this->findElement($xpath)->submit();
    }

    /**
     * {@inheritdoc}
     */
    public function maximizeWindow($name = null)
    {
        $this->wdSession->window($name ? $name : 'current')->maximize();
    }

    /**
     * Returns Session ID of WebDriver or `null`, when session not started yet.
     *
     * @return string|null
     */
    public function getWebDriverSessionId()
    {
        return $this->isStarted() ? basename($this->wdSession->getUrl()) : null;
    }

    /**
     * @param string $xpath
     *
     * @return Element
     */
    private function findElement($xpath)
    {
        return $this->wdSession->element('xpath', $xpath);
    }

    /**
     * Selects a value in a radio button group
     *
     * @param Element $element An element referencing one of the radio buttons of the group
     * @param string  $value   The value to select
     *
     * @throws DriverException when the value cannot be found
     */
    private function selectRadioValue(Element $element, $value)
    {
        // short-circuit when we already have the right button of the group to avoid XPath queries
        if ($element->attribute('value') === $value) {
            $element->click();

            return;
        }

        $name = $element->attribute('name');

        if (!$name) {
            throw new DriverException(sprintf('The radio button does not have the value "%s"', $value));
        }

        $formId = $element->attribute('form');

        try {
            if (null !== $formId) {
                $xpath = <<<'XPATH'
//form[@id=%1$s]//input[@type="radio" and not(@form) and @name=%2$s and @value = %3$s]
|
//input[@type="radio" and @form=%1$s and @name=%2$s and @value = %3$s]
XPATH;

                $xpath = sprintf(
                    $xpath,
                    $this->xpathEscaper->escapeLiteral($formId),
                    $this->xpathEscaper->escapeLiteral($name),
                    $this->xpathEscaper->escapeLiteral($value)
                );
                $input = $this->wdSession->element('xpath', $xpath);
            } else {
                $xpath = sprintf(
                    './ancestor::form//input[@type="radio" and not(@form) and @name=%s and @value = %s]',
                    $this->xpathEscaper->escapeLiteral($name),
                    $this->xpathEscaper->escapeLiteral($value)
                );
                $input = $element->element('xpath', $xpath);
            }
        } catch (NoSuchElement $e) {
            $message = sprintf('The radio group "%s" does not have an option "%s"', $name, $value);

            throw new DriverException($message, 0, $e);
        }

        $input->click();
    }

    /**
     * @param Element $element
     * @param string  $value
     * @param bool    $multiple
     */
    private function selectOptionOnElement(Element $element, $value, $multiple = false)
    {
        $escapedValue = $this->xpathEscaper->escapeLiteral($value);
        // The value of an option is the normalized version of its text when it has no value attribute
        $optionQuery = sprintf('.//option[@value = %s or (not(@value) and normalize-space(.) = %s)]', $escapedValue, $escapedValue);
        $option = $element->element('xpath', $optionQuery);

        if ($multiple || !$element->attribute('multiple')) {
            if (!$option->selected()) {
                $option->click();
            }

            return;
        }

        // Deselect all options before selecting the new one
        $this->deselectAllOptions($element);
        $option->click();
    }

    /**
     * Deselects all options of a multiple select
     *
     * Note: this implementation does not trigger a change event after deselecting the elements.
     *
     * @param Element $element
     */
    private function deselectAllOptions(Element $element)
    {
        $script = <<<JS
var node = {{ELEMENT}};
var i, l = node.options.length;
for (i = 0; i < l; i++) {
    node.options[i].selected = false;
}
JS;

        $this->executeJsOnElement($element, $script);
    }

    /**
     * Ensures the element is a checkbox
     *
     * @param Element $element
     * @param string  $xpath
     * @param string  $type
     * @param string  $action
     *
     * @throws DriverException
     */
    private function ensureInputType(Element $element, $xpath, $type, $action)
    {
        if ('input' !== strtolower($element->name()) || $type !== strtolower($element->attribute('type'))) {
            $message = 'Impossible to %s the element with XPath "%s" as it is not a %s input';

            throw new DriverException(sprintf($message, $action, $xpath, $type));
        }
    }
}
