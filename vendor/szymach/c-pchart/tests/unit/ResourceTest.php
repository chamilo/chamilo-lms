<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class ResourceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testInvalidResourceLoading()
    {
        $data = new Data();
        $this->tester->expectException('\Exception', function() use ($data) {
            $data->loadPalette('nonExistantPalette');
        });

        $image = new Image(700, 230, $data);

        $this->tester->expectException('\Exception', function() use ($image) {
            $image->setResourcePath('nonExistantDirectory');
        });
        $this->tester->expectException('\Exception', function() use ($image) {
            $image->setFontProperties(["FontName" => "nonExistantFont"]);
        });
        $this->tester->expectException('\Exception', function() use ($image) {
            $image->getLegendSize(['Font' => 'nonExistantFont']);
        });
    }

    public function testValidPaletteLoading()
    {
        $data = new Data();
        $data->loadPalette(sprintf('%s/../_data/test_palette.txt', __DIR__), true);

        $image = new Image(700, 230, $data);
        $firstCoordinates = [[40, 80], [280, 60], [340, 166], [590, 120]];
        $fistSplineSettings = ["R" => 255, "G" => 255, "B" => 255, "ShowControl" => true];
        $image->drawSpline($firstCoordinates, $fistSplineSettings);
        $filename = $this->tester->getOutputPathForChart('drawSpline.png');
        $image->render($filename);
        $this->tester->seeFileFound($filename);
    }

    public function testInvalidPaletteLoading()
    {
        $data = new Data();
        $this->tester->expectException('\Exception', function() use ($data) {
            $data->loadPalette(sprintf('non_existant_palette', __DIR__), true);
        });
    }
}
