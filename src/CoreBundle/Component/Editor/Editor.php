<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Template;

class Editor
{
    public string $textareaId;

    /**
     * Name of the instance.
     */
    public string $name;

    /**
     * Name of the toolbar to load.
     */
    public string $toolbarSet;

    /**
     * Initial value.
     */
    public string $value;

    public array $config;

    public TranslatorInterface $translator;

    public RouterInterface $urlGenerator;

    public Template $template;

    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $urlGenerator
    ) {
        $this->toolbarSet = 'Basic';
        $this->value = '';
        $this->config = [];
        $this->setConfigAttribute('width', '100%');
        $this->setConfigAttribute('height', '200');
        $this->setConfigAttribute('fullPage', false);
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        //$this->course = $course;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTextareaId()
    {
        return $this->textareaId;
    }

    /**
     * @param string $textareaId
     *
     * @return Editor
     */
    public function setTextareaId($textareaId)
    {
        $this->textareaId = $textareaId;

        return $this;
    }

    /**
     * @param string $key
     */
    public function setConfigAttribute($key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function getConfigAttribute($key)
    {
        return $this->config[$key] ?? null;
    }

    /**
     * @param array $config
     */
    public function processConfig($config): void
    {
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                switch ($key) {
                    case 'ToolbarSet':
                        $this->toolbarSet = $value;

                        break;
                    case 'Config':
                        $this->processConfig($value);

                        break;
                    case 'width':
                    case 'Width':
                        $this->setConfigAttribute('width', $value);

                        break;
                    case 'height':
                    case 'Height':
                        $this->setConfigAttribute('height', $value);

                        break;
                    case 'FullPage':
                    case 'fullPage':
                        $this->setConfigAttribute('fullPage', $value);

                        break;
                    default:
                        $this->setConfigAttribute($key, $value);

                        break;
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return api_get_language_isocode();
    }

    /**
     * Converts a PHP variable into its Javascript equivalent.
     * The code of this method has been "borrowed" from the function drupal_to_js() within the Drupal CMS.
     *
     * @param mixed $var The variable to be converted into Javascript syntax
     *
     * @return string Returns a string
     *                Note: This function is similar to json_encode(),
     *                in addition it produces HTML-safe strings, i.e. with <, > and & escaped.
     *
     * @see http://drupal.org/
     */
    protected function toJavascript($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false'; // Lowercase necessary!
            case 'integer':
            case 'double':
                return (string) $var;
            case 'resource':
            case 'string':
                return '"'.str_replace(
                    ["\r", "\n", '<', '>', '&'],
                    ['\r', '\n', '\x3c', '\x3e', '\x26'],
                    addslashes($var)
                ).'"';

                break;
            case 'array':
                // Arrays in JSON can't be associative. If the array is empty or if it
                // has sequential whole number keys starting with 0, it's not associative
                // so we can go ahead and convert it as an array.
                if (empty($var) || array_keys($var) === range(0, count($var) - 1)) {
                    $output = [];
                    foreach ($var as $v) {
                        $output[] = $this->toJavascript($v);
                    }

                    return '[ '.implode(', ', $output).' ]';
                }
                //no break
            case 'object':
                // Otherwise, fall through to convert the array as an object.
                $output = [];
                foreach ($var as $k => $v) {
                    $output[] = $this->toJavascript((string) $k).': '.$this->toJavascript($v);
                }

                return '{ '.implode(', ', $output).' }';

                break;
            default:
                return 'null';
        }
    }
}
