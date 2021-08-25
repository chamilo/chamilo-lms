<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class AreaTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        for ($i = 0; $i <= 30; $i++) {
            $data->addPoints(rand(1, 15), "Probe 1");
        }
        $data->setSerieTicks("Probe 2", 4);
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
        $image->drawLegend(600, 20, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);
        $image->Antialias = true;
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $threshold = [
            ["Min" => 0, "Max" => 5, "R" => 207, "G" => 240, "B" => 20, "Alpha" => 70],
            ["Min" => 5, "Max" => 10, "R" => 240, "G" => 232, "B" => 20, "Alpha" => 70],
            ["Min" => 10, "Max" => 20, "R" => 240, "G" => 191, "B" => 20, "Alpha" => 70]
        ];
        $image->drawAreaChart(["Threshold" => $threshold]);
        $image->drawThreshold(5, ["WriteCaption" => true, "Caption" => "Warn Zone", "Alpha" => 70, "Ticks" => 2, "R" => 0, "G" => 0, "B" => 255]);
        $image->drawThreshold(10, ["WriteCaption" => true, "Caption" => "Error Zone", "Alpha" => 70, "Ticks" => 2, "R" => 0, "G" => 0, "B" => 255]);

        $filename = $this->tester->getOutputPathForChart('drawAreaChart.threshold.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
