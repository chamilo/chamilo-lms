<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class FilledStepTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        $data->addPoints([-4, 2, VOID, 12, 8, 3], "Probe 1");
        $data->addPoints([3, 12, 15, 8, 5, -5], "Probe 2");
        $data->addPoints([2, 7, 5, 18, 19, 22], "Probe 3");
        $data->setSerieTicks("Probe 2", 4);
        $data->setAxisName(0, "Temperatures");
        $data->addPoints(["Jan", "Feb", "Mar", "Apr", "May", "Jun"], "Labels");
        $data->setSerieDescription("Labels", "Months");
        $data->setAbscissa("Labels");
        $image = new Image(700, 230, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 700, 230, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "drawFilledStepChart() - draw a filled step chart", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
        $image->drawText(250, 55, "Average temperature", ["FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]);
        $image->setGraphArea(60, 60, 450, 190);
        $image->drawFilledRectangle(60, 60, 450, 190, ["R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10]);
        $image->drawScale(["DrawSubTicks" => true]);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->drawFilledStepChart(["ForceTransparency" => 40, "DisplayValues" => true, "DisplayColor" => DISPLAY_AUTO]);
        $image->setShadow(false);
        $image->setGraphArea(500, 60, 670, 190);
        $image->drawFilledRectangle(500, 60, 670, 190, ["R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10]);
        $image->drawScale(["Pos" => SCALE_POS_TOPBOTTOM, "DrawSubTicks" => true]);
        $image->setShadow(true, ["X" => -1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $image->drawFilledStepChart(["ForceTransparency" => 40]);
        $image->setShadow(false);
        $image->drawLegend(510, 205, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

        $filename = $this->tester->getOutputPathForChart('drawFilledStepChart.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
