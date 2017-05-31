<?php

namespace CpChart\Behat\Context;

use Behat\Behat\Context\Context;
use CpChart\Behat\Fixtures\FixtureGenerator;
use CpChart\Chart\Image;

/**
 * @author Piotr Szymaszek
 */
class DrawContext implements Context
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
     * @Given I render the chart of type :chart
     */
    public function iRenderTheChartOfType(Image $chart)
    {
        $chart->Render($this->getFilePath("example.png"));
    }

    /**
     * @Then I should see a new file :filename in output folder
     */
    public function iShouldSeeANewFileInOutputFolder($filename)
    {
        expect(file_exists($this->getFilePath($filename)))->toBe(true);
    }

    private function getFilePath($filename)
    {
        return sprintf("%s/%s", $this->outputFolderPath, $filename);
    }
}
