<?php

namespace CpChart\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use CpChart\Behat\Fixtures\FixtureGenerator;
use CpChart\Chart\Image;

class DataContext implements Context, SnippetAcceptingContext
{
    /**
     * @var FixtureGenerator
     */
    private $fixturesGenerator;

    public function __construct()
    {
        $this->fixturesGenerator = new FixtureGenerator();
    }
    
    /**
     * @Transform :chart
     * @Transform /^(spline)$/
     */
    public function castChartNameToObject($name)
    {
        $image = $this->fixturesGenerator->createEmptyImage();
        
        switch ($name) {
            case 'spline':
                $this->fixturesGenerator->setSplineData($image);
                break;
        }
        
        return $image;
    }

    /**
     * @Then I should be able to create empty images of width :width and height :height
     */
    public function iShouldBeAbleToCreateEmptyImagesOfWidthAndHeight($width, $height)
    {
        $image = $this->fixturesGenerator->createEmptyImage($width, $height);
        expect($image instanceof Image)->toBe(true);
        expect($image->getWidth() == $width)->toBe(true);
        expect($image->getHeight() == $height)->toBe(true);
    }
}
