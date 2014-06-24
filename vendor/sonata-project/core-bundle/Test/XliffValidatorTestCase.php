<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Test;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Loader\XliffFileLoader;

abstract class XliffValidatorTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var XliffFileLoader
     */
    protected $loader;

    protected $errors = array();

    public function setUp()
    {
        $this->loader = new XliffFileLoader();
    }

    /**
     * @param string $file The path to the xliff file
     */
    protected function validateXliff($file)
    {
        try {
            $this->loader->load($file, 'en');
            $this->assertTrue(true, sprintf("Successful loading file: %s", $file));
        } catch (InvalidResourceException $e) {
            $this->errors[] = sprintf("%s => %s", $file, $e->getMessage());
        }
    }

    /**
     * @param string $path The path to lookup for Xliff file
     */
    protected function validatePath($path)
    {
        $files = glob(sprintf("%s/*.xliff", $path));

        foreach ($files as $file) {
            $this->validateXliff($file);
        }
    }

    /**
     *
     * @dataProvider getXliffPaths
     */
    public function testXliff($path)
    {
        $this->validatePath($path);

        if (count($this->errors) > 0) {
            $this->fail(sprintf("Unable to parse xliff files: %s", implode(", ", $this->errors)));
        }
    }

    /**
     * @return array List all path to validate xliff
     */
    abstract function getXliffPaths();
}