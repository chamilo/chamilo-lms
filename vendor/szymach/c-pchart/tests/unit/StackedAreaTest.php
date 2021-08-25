<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class StackedAreaTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        $data->addPoints([4, 0, 0, 12, 8, 3, 0, 12, 8], "Frontend #1");
        $data->addPoints([3, 12, 15, 8, 5, 5, 12, 15, 8], "Frontend #2");
        $data->addPoints([2, 7, 5, 18, 19, 22, 7, 5, 18], "Frontend #3");
        $data->setAxisName(0, "Average Usage");
        $data->addPoints(["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jui", "Aug", "Sep"], "Labels");
        $data->setSerieDescription("Labels", "Months");
        $data->setAbscissa("Labels");
        $data->normalize(100, "%");
        $image = new Image(700, 230, $data);
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, ["StartR" => 240, "StartG" => 240, "StartB" => 240, "EndR" => 180, "EndG" => 180, "EndB" => 180, "Alpha" => 100]);
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_HORIZONTAL, ["StartR" => 240, "StartG" => 240, "StartB" => 240, "EndR" => 180, "EndG" => 180, "EndB" => 180, "Alpha" => 20]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->setGraphArea(60, 20, 680, 190);
        $image->drawScale(["XMargin" => 2, "DrawSubTicks" => true, "Mode" => SCALE_MODE_ADDALL]);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $image->drawStackedAreaChart(["Surrounding" => 60]);
        $image->setShadow(false);
        $image->drawLegend(480, 210, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

        $filename = $this->tester->getOutputPathForChart('drawStackedArea.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
