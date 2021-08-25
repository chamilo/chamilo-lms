<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use CpChart\Chart\Pie;
use UnitTester;

class PieTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function test2dPieRender()
    {
        $data = new Data();
        $data->addPoints([40, 60, 15, 10, 6, 4], "ScoreA");
        $data->setSerieDescription("ScoreA", "Application A");
        $data->addPoints(["<10", "10<>20", "20<>40", "40<>60", "60<>80", ">80"], "Labels");
        $data->setAbscissa("Labels");
        $image = new Image(700, 230, $data);
        $image->drawFilledRectangle(0, 0, 700, 230, [
            "R" => 173, "G" => 152, "B" => 217, "Dash" => 1, "DashR" => 193,
            "DashG" => 172, "DashB" => 237
        ]);
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, [
            "StartR" => 209, "StartG" => 150, "StartB" => 231, "EndR" => 111,
            "EndG" => 3, "EndB" => 138, "Alpha" => 50
        ]);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, [
            "StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50,
            "EndB" => 50, "Alpha" => 100
        ]);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "pPie - Draw 2D pie charts", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);
        $image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 150, "G" => 150, "B" => 150, "Alpha" => 100]);
        $pieChart = new Pie($image, $data);
        $pieChart->draw2DPie(140, 125, ["SecondPass" => false]);
        $pieChart->draw2DPie(340, 125, ["DrawLabels" => true, "Border" => true]);
        $pieChart->draw2DPie(540, 125, [
            "DataGapAngle" => 10, "DataGapRadius" => 6, "Border" => true,
            "BorderR" => 255, "BorderG" => 255, "BorderB" => 255
        ]);

        $image->setFontProperties(["FontName" => "MankSans.ttf", "FontSize" => 11]);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]);
        $image->drawText(140, 200, "Single AA pass", [
            "R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE
        ]);
        $image->drawText(540, 200, "Extended AA pass / Splitted", [
            "R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE
        ]);

        $filename = $this->tester->getOutputPathForChart('draw2DPie.png');
        $pieChart->pChartObject->render($filename);
        $pieChart->pChartObject->stroke();

        $this->tester->seeFileFound($filename);
    }

    public function test2dRingRender()
    {
        $data = new Data();
        $data->addPoints([50, 2, 3, 4, 7, 10, 25, 48, 41, 10], "ScoreA");
        $data->setSerieDescription("ScoreA", "Application A");
        $data->addPoints(["A0", "B1", "C2", "D3", "E4", "F5", "G6", "H7", "I8", "J9"], "Labels");
        $data->setAbscissa("Labels");
        $image = new Image(300, 260, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 300, 300, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 300, 260, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 300, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100]);
        $image->drawRectangle(0, 0, 299, 259, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "pPie - Draw 2D ring charts", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);
        $image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 50]);
        $pieChart = new Pie($image, $data);
        $pieChart->draw2DRing(160, 140, ["DrawLabels" => true, "LabelStacked" => true, "Border" => true]);
        $image->setShadow(false);
        $pieChart->drawPieLegend(15, 40, ["Alpha" => 20]);

        $filename = $this->tester->getOutputPathForChart('draw2DRing.png');
        $pieChart->pChartObject->render($filename);
        $pieChart->pChartObject->stroke();

        $this->tester->seeFileFound($filename);
    }

    public function test3dPieRender()
    {
        $data = new Data();
        $data->addPoints([40, 30, 20], "ScoreA");
        $data->setSerieDescription("ScoreA", "Application A");
        $data->addPoints(["A", "B", "C"], "Labels");
        $data->setAbscissa("Labels");
        $image = new Image(700, 230, $data, true);
        $settings = ["R" => 173, "G" => 152, "B" => 217, "Dash" => 1, "DashR" => 193, "DashG" => 172, "DashB" => 237];
        $image->drawFilledRectangle(0, 0, 700, 230, $settings);
        $settings = ["StartR" => 209, "StartG" => 150, "StartB" => 231, "EndR" => 111, "EndG" => 3, "EndB" => 138, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100]);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "pPie - Draw 3D pie charts", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);
        $pieChart = new Pie($image, $data);
        $pieChart->setSliceColor(0, ["R" => 143, "G" => 197, "B" => 0]);
        $pieChart->setSliceColor(1, ["R" => 97, "G" => 77, "B" => 63]);
        $pieChart->setSliceColor(2, ["R" => 97, "G" => 113, "B" => 63]);
        $pieChart->draw3DPie(120, 125, ["SecondPass" => false]);
        $pieChart->draw3DPie(340, 125, ["DrawLabels" => true, "Border" => true]);
        $image->setShadow(true, ["X" => 3, "Y" => 3, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $pieChart->draw3DPie(560, 125, ["WriteValues" => true, "DataGapAngle" => 10, "DataGapRadius" => 6, "Border" => true]);
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]);
        $image->drawText(120, 200, "Single AA pass", ["DrawBox" => true, "BoxRounded" => true, "R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE]);
        $image->drawText(440, 200, "Extended AA pass / Splitted", ["DrawBox" => true, "BoxRounded" => true, "R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6, "R" => 255, "G" => 255, "B" => 255]);
        $pieChart->drawPieLegend(600, 8, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

        $filename = $this->tester->getOutputPathForChart('draw3DPie.png');
        $pieChart->pChartObject->render($filename);
        $pieChart->pChartObject->stroke();

        $this->tester->seeFileFound($filename);
    }

    public function test3dRingRender()
    {
        $data = new Data();
        $data->addPoints([50, 2, 3, 4, 7, 10, 25, 48, 41, 10], "ScoreA");
        $data->setSerieDescription("ScoreA", "Application A");
        $data->addPoints(["A0", "B1", "C2", "D3", "E4", "F5", "G6", "H7", "I8", "J9"], "Labels");
        $data->setAbscissa("Labels");
        $image = new Image(400, 400, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 400, 400, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 400, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100]);
        $image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "pPie - Draw 3D ring charts", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);
        $image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 50]);
        $pieChart = new Pie($image, $data);
        $pieChart->draw3DRing(200, 200, ["DrawLabels" => true, "LabelStacked" => true, "Border" => true]);
        $pieChart->drawPieLegend(80, 360, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER, "Alpha" => 20]);

        $filename = $this->tester->getOutputPathForChart('draw3DRing.png');
        $pieChart->pChartObject->render($filename);
        $pieChart->pChartObject->stroke();

        $this->tester->seeFileFound($filename);
    }
}
