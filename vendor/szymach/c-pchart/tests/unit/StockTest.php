<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use CpChart\Chart\Stock;
use UnitTester;

class StockTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $data = new Data();
        $data->addPoints([34, 55, 15, 62, 38, 42], "Open");
        $data->addPoints([42, 25, 40, 38, 49, 36], "Close");
        $data->addPoints([27, 14, 12, 25, 32, 32], "Min");
        $data->addPoints([45, 59, 47, 65, 64, 48], "Max");
        $data->setAxisDisplay(0, AXIS_FORMAT_CURRENCY, "$");
        $data->addPoints(["8h", "10h", "12h", "14h", "16h", "18h"], "Time");
        $data->setAbscissa("Time");
        $image = new Image(700, 230, $data);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 700, 230, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "../fonts/Forgotte.ttf", "FontSize" => 11]);
        $image->drawText(60, 45, "Stock price", ["FontSize" => 28, "Align" => TEXT_ALIGN_BOTTOMLEFT]);
        $image->setGraphArea(60, 60, 450, 190);
        $image->drawFilledRectangle(60, 60, 450, 190, ["R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10]);
        $image->drawScale(["DrawSubTicks" => true, "CycleBackground" => true]);
        $stockChart = new Stock($image, $data);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 30]);
        $stockChart->drawStockChart();
        $data->setAxisDisplay(0, AXIS_FORMAT_DEFAULT);
        $image->setShadow(false);
        $image->setGraphArea(500, 60, 670, 190);
        $image->drawFilledRectangle(500, 60, 670, 190, ["R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10]);
        $image->drawScale(["Pos" => SCALE_POS_TOPBOTTOM, "DrawSubTicks" => true]);
        $stockChart = new Stock($image, $data);
        $image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 30]);
        $stockChart->drawStockChart();
        $filename = $this->tester->getOutputPathForChart('drawStock.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
