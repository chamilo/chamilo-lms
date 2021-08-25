<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class SplineTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        $data->addPoints([], "Serie1");

        $image = new Image(700, 230, $data);
        $image->setShadow(
            true,
            ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]
        );
        $firstCoordinates = [[40, 80], [280, 60], [340, 166], [590, 120]];
        $fistSplineSettings = ["R" => 255, "G" => 255, "B" => 255, "ShowControl" => true];
        $image->drawSpline($firstCoordinates, $fistSplineSettings);
        $secondCoordinates = [[250, 50], [250, 180], [350, 180], [350, 50]];
        $secondSplineSettings = [
            "R" => 255,
            "G" => 255,
            "B" => 255,
            "ShowControl" => true,
            "Ticks" => 4
        ];
        $image->drawSpline($secondCoordinates, $secondSplineSettings);
        $filename = $this->tester->getOutputPathForChart('drawSpline.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
