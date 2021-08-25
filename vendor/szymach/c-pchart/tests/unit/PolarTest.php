<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use CpChart\Chart\Radar;
use UnitTester;

class PolarTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        $data->addPoints([10, 20, 30, 40, 50, 60, 70, 80, 90], "ScoreA");
        $data->addPoints([20, 40, 50, 12, 10, 30, 40, 50, 60], "ScoreB");
        $data->setSerieDescription("ScoreA", "Coverage A");
        $data->setSerieDescription("ScoreB", "Coverage B");
        $data->addPoints([40, 80, 120, 160, 200, 240, 280, 320, 360], "Coord");
        $data->setAbscissa("Coord");
        $image = new Image(700, 230, $data);
        $settings = ["R" => 179, "G" => 217, "B" => 91, "Dash" => 1, "DashR" => 199, "DashG" => 237, "DashB" => 111];
        $image->drawFilledRectangle(0, 0, 700, 230, $settings);
        $settings = ["StartR" => 194, "StartG" => 231, "StartB" => 44, "EndR" => 43, "EndG" => 107, "EndB" => 58, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100]);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "pRadar - Draw polar charts", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);
        $image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $splitChart = new Radar();
        $image->setGraphArea(10, 25, 340, 225);
        $Options = ["BackgroundGradient" => ["StartR" => 255, "StartG" => 255, "StartB" => 255, "StartAlpha" => 100, "EndR" => 207, "EndG" => 227, "EndB" => 125, "EndAlpha" => 50]];
        $splitChart->drawPolar($image, $data, $Options);
        $image->setGraphArea(350, 25, 690, 225);
        $Options = ["LabelPos" => RADAR_LABELS_HORIZONTAL, "BackgroundGradient" => ["StartR" => 255, "StartG" => 255, "StartB" => 255, "StartAlpha" => 50, "EndR" => 32, "EndG" => 109, "EndB" => 174, "EndAlpha" => 30], "AxisRotation" => 0, "DrawPoly" => true, "PolyAlpha" => 50];
        $splitChart->drawPolar($image, $data, $Options);

        /* Write the chart legend */
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->drawLegend(270, 205, ["Style" => LEGEND_BOX, "Mode" => LEGEND_HORIZONTAL]);

        $filename = $this->tester->getOutputPathForChart('drawPolar.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
