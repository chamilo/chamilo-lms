<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class LineTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        $data->addPoints([-4, VOID, VOID, 12, 8, 3], "Probe 1");
        $data->addPoints([3, 12, 15, 8, 5, -5], "Probe 2");
        $data->addPoints([2, 7, 5, 18, 19, 22], "Probe 3");
        $data->setSerieTicks("Probe 2", 4);
        $data->setSerieWeight("Probe 3", 2);
        $data->setAxisName(0, "Temperatures");
        $data->addPoints(["Jan", "Feb", "Mar", "Apr", "May", "Jun"], "Labels");
        $data->setSerieDescription("Labels", "Months");
        $data->setAbscissa("Labels");

        $image = new Image(700, 230, $data);
        $image->setGraphArea(60, 60, 450, 190);
        $image->drawFilledRectangle(60, 60, 450, 190, ["R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10]);
        $image->drawScale(["DrawSubTicks" => true]);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->drawLineChart(["DisplayValues" => true, "DisplayColor" => DISPLAY_AUTO]);
        $image->setShadow(false);
        $image->setGraphArea(500, 60, 670, 190);
        $image->drawFilledRectangle(500, 60, 670, 190, ["R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10]);
        $image->drawScale(["Pos" => SCALE_POS_TOPBOTTOM, "DrawSubTicks" => true]);
        $image->setShadow(true, ["X" => -1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $image->drawLineChart();
        $image->setShadow(false);
        $image->drawLegend(510, 205, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

        $filename = $this->tester->getOutputPathForChart('drawLine.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
