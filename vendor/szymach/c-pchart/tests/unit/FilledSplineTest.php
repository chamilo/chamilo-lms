<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class FilledSplineTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        $data->setAxisName(0, "Strength");
        for ($i = 0; $i <= 720; $i = $i + 20) {
            $data->addPoints(cos(deg2rad($i)) * 100, "Probe 1");
            $data->addPoints(cos(deg2rad($i + 90)) * 60, "Probe 2");
        }
        $image = new Image(847, 304, $data);
        $image->drawGradientArea(0, 0, 847, 304, DIRECTION_VERTICAL, ["StartR" => 47, "StartG" => 47, "StartB" => 47, "EndR" => 17, "EndG" => 17, "EndB" => 17, "Alpha" => 100]);
        $image->drawGradientArea(0, 250, 847, 304, DIRECTION_VERTICAL, ["StartR" => 47, "StartG" => 47, "StartB" => 47, "EndR" => 27, "EndG" => 27, "EndB" => 27, "Alpha" => 100]);
        $image->drawLine(0, 249, 847, 249, ["R" => 0, "G" => 0, "B" => 0]);
        $image->drawLine(0, 250, 847, 250, ["R" => 70, "G" => 70, "B" => 70]);
        $image->drawRectangle(0, 0, 846, 303, ["R" => 204, "G" => 204, "B" => 204]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->drawText(423, 14, "Cyclic magnetic field strength", ["R" => 255, "G" => 255, "B" => 255, "Align" => TEXT_ALIGN_MIDDLEMIDDLE]);
        $image->setGraphArea(58, 27, 816, 228);
        $image->drawFilledRectangle(58, 27, 816, 228, ["R" => 0, "G" => 0, "B" => 0, "Dash" => true, "DashR" => 0, "DashG" => 51, "DashB" => 51, "BorderR" => 0, "BorderG" => 0, "BorderB" => 0]);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]);
        $image->setFontProperties(["R" => 255, "G" => 255, "B" => 255]);
        $ScaleSettings = ["XMargin" => 4, "DrawSubTicks" => true, "GridR" => 255, "GridG" => 255, "GridB" => 255, "AxisR" => 255, "AxisG" => 255, "AxisB" => 255, "GridAlpha" => 30, "CycleBackground" => true];
        $image->drawScale($ScaleSettings);
        $image->drawFilledSplineChart();
        $BoundsSettings = ["MaxDisplayR" => 237, "MaxDisplayG" => 23, "MaxDisplayB" => 48, "MinDisplayR" => 23, "MinDisplayG" => 144, "MinDisplayB" => 237];
        $image->writeBounds(BOUND_BOTH, $BoundsSettings);
        $image->drawThreshold(0, ["WriteCaption" => true]);
        $image->setFontProperties(["R" => 255, "G" => 255, "B" => 255]);
        $image->drawLegend(560, 266, ["Style" => LEGEND_NOBORDER]);
        $settings = ["R" => 188, "G" => 224, "B" => 46, "Align" => TEXT_ALIGN_BOTTOMLEFT];
        $image->drawText(620, 270, "Max : " . ceil($data->getMax("Probe 1")), $settings);
        $image->drawText(680, 270, "Min : " . ceil($data->getMin("Probe 1")), $settings);
        $image->drawText(740, 270, "Avg : " . ceil($data->getSerieAverage("Probe 1")), $settings);
        $settings = ["R" => 224, "G" => 100, "B" => 46, "Align" => TEXT_ALIGN_BOTTOMLEFT];
        $image->drawText(620, 283, "Max : " . ceil($data->getMax("Probe 2")), $settings);
        $image->drawText(680, 283, "Min : " . ceil($data->getMin("Probe 2")), $settings);
        $image->drawText(740, 283, "Avg : " . ceil($data->getSerieAverage("Probe 2")), $settings);

        $filename = $this->tester->getOutputPathForChart('drawFilledSplineChart.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
