<?php

namespace CpChart\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use CpChart\Behat\Fixtures\FixtureGenerator;
use DirectoryIterator;
use FilesystemIterator;

/**
 * @author Piotr Szymaszek
 */
class DirectoryContext implements Context
{
    /**
     * @var string
     */
    private $outputFolderPath;

    /**
     * @param string $basePath
     */
    public function __construct($basePath)
    {
        $this->outputFolderPath = FixtureGenerator::getFixturesPath($basePath);
    }

    /**
     * @BeforeScenario
     */
    public function before(BeforeScenarioScope $scope)
    {
        $this->deleteOutputFolder();
        mkdir($this->outputFolderPath);
    }

    /**
     * @Given the output directory is empty
     */
    public function theOutputDirectoryIsEmpty()
    {
        $iterator = new FilesystemIterator($this->outputFolderPath);
        expect($iterator->valid())->toBe(false);
    }

    /**
     * @AfterScenario
     */
    public function after(AfterScenarioScope $scope)
    {
        $this->deleteOutputFolder();
    }

    private function deleteOutputFolder()
    {
        if (file_exists($this->outputFolderPath)) {
            $iterator = new DirectoryIterator($this->outputFolderPath);
            foreach ($iterator as $file) {
                if ($file->isDot()) {
                    continue;
                }
                unlink($file->getPathname());
            }
            rmdir($this->outputFolderPath);
        }
    }
}
