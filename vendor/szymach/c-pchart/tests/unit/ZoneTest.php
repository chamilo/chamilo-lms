<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class ZoneTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        for ($i = 0; $i <= 10; $i = $i + .2) {
            $data->addPoints(log($i + 1) * 10, "Bounds 1");
            $data->addPoints(log($i + 3) * 10 + rand(0, 2) - 1, "Probe");
            $data->addPoints(log($i + 6) * 10, "Bounds 2");
            $data->addPoints($i * 10, "Labels");
        }
        $data->setAxisName(0, "Size (cm)");
        $data->setSerieDescription("Labels", "Months");
        $data->setAbscissa("Labels");
        $data->setAbscissaName("Time (years)");
        $image = new Image(700, 230, $data);
        $image->Antialias = false;
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
        $image->drawText(150, 35, "Size by time generations", ["FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->setGraphArea(40, 40, 680, 200);
        $scaleSettings = ["LabelSkip" => 4, "XMargin" => 10, "YMargin" => 10, "Floating" => true, "GridR" => 200, "GridG" => 200, "GridB" => 200, "DrawSubTicks" => true, "CycleBackground" => true];
        $image->drawScale($scaleSettings);
        $image->Antialias = true;
        $image->drawZoneChart("Bounds 1", "Bounds 2");
        $data->setSerieDrawable(["Bounds 1", "Bounds 2"], false);
        $image->drawStepChart();
        $image->drawLegend(640, 20, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

        $filename = $this->tester->getOutputPathForChart('drawZone.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
