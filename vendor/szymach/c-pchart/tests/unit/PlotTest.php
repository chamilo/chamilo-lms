<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class PlotTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        for ($i = 0; $i <= 20; $i++) {
            $data->addPoints(rand(0, 20), "Probe 1");
        }
        for ($i = 0; $i <= 20; $i++) {
            $data->addPoints(rand(0, 20), "Probe 2");
        }
        $data->setSerieShape("Probe 1", SERIE_SHAPE_FILLEDTRIANGLE);
        $data->setSerieShape("Probe 2", SERIE_SHAPE_FILLEDSQUARE);
        $data->setAxisName(0, "Temperatures");
        $image = new Image(700, 230, $data);
        $image->Antialias = false;
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
        $image->drawText(150, 35, "Average temperature", ["FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->setGraphArea(60, 40, 650, 200);
        $scaleSettings = ["XMargin" => 10, "YMargin" => 10, "Floating" => true, "GridR" => 200, "GridG" => 200, "GridB" => 200, "DrawSubTicks" => true, "CycleBackground" => true];
        $image->drawScale($scaleSettings);
        $image->Antialias = true;
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $image->drawPlotChart();
        $image->drawLegend(580, 20, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);
        $filename = $this->tester->getOutputPathForChart('drawPlot.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
