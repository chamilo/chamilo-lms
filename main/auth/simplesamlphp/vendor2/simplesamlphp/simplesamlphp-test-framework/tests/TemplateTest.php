<?php
/**
 * Simple test for syntax-checking Twig-templates.
 *
 * @author Tim van Dijen <tvdijen@gmail.com>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\TestUtils;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;
use SimpleSAML\Module;
use Twig\Error\SyntaxError;

class TemplateTest extends TestCase
{
    /**
     * @return void
     */
    public function testSyntax()
    {
        $config = Configuration::loadFromArray([
            'usenewui' => true,
            'module.enable' => array_fill_keys(Module::getModules(), true),
        ]);
        Configuration::setPreLoadedConfig($config);

        $basedir = $config->getBaseDir().DIRECTORY_SEPARATOR.'templates';
        if (file_exists($basedir)) {
            $files = array_diff(scandir($basedir), ['.', '..']);

            // Base templates
            foreach ($files as $file) {
                if (preg_match('/.twig$/', $file)) {
                    $t = new Template($config, $file);
                    ob_start();
                    try {
                        $t->show();
                        $this->addToAssertionCount(1);
                    } catch (SyntaxError $e) {
                        $this->fail($e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                    }
                    ob_end_clean();
                }
            }
        }

        // Module templates
        foreach (Module::getModules() as $module) {
            $basedir = Module::getModuleDir($module).DIRECTORY_SEPARATOR.'templates';
            if (file_exists($basedir)) {
                $files = array_diff(scandir($basedir), ['.', '..']);
                foreach ($files as $file) {
                    if (preg_match('/.twig$/', $file)) {
                        $t = new Template($config, $module.':'.$file);
                        ob_start();
                        try {
                            $t->show();
                            $this->addToAssertionCount(1);
                        } catch (SyntaxError $e) {
                            $this->fail($e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                        }
                        ob_end_clean();
                    }
                }
            }
        }
    }
}
