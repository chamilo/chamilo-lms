<?php
namespace Helper;

use Codeception\Module;
use Codeception\Module\Filesystem;

class Unit extends Module
{
    public function _beforeSuite($settings = [])
    {
        $chartDir = $this->getChartDirectoryPath();
        if (!is_dir($chartDir)) {
            mkdir($chartDir);
        }

        $this->clearOutputDirectory();
    }

    public function _afterSuite($settings = [])
    {
        $this->clearOutputDirectory();
    }

    private function clearOutputDirectory()
    {
        $this->getFileSystem()->cleanDir($this->getChartDirectoryPath());
    }

    private function getChartDirectoryPath()
    {
        return sprintf(__DIR__."/../../_output/charts");
    }

    /**
     * @return Filesystem
     */
    private function getFileSystem()
    {
        return $this->getModule('Filesystem');
    }
}
