<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Data;
use CpChart\Image;
use CpChart\Chart\Split;
use UnitTester;

class SplitPathTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $image = new Image(700, 230);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 700, 230, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "pSplit - Draw splitted path charts", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);
        $image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $data = new Data();
        $data->addPoints([30, 20, 15, 10, 8, 4], "Score");
        $data->addPoints(["End of visit", "Home Page", "Product Page", "Sales", "Statistics", "Prints"], "Labels");
        $data->setAbscissa("Labels");
        $SplitChart = new Split();
        $settings = ["TextPos" => TEXT_POS_RIGHT, "TextPadding" => 10, "Spacing" => 20, "Surrounding" => 40];
        $image->setGraphArea(10, 20, 340, 230);
        $SplitChart->drawSplitPath($image, $data, $settings);
        $data2 = new Data();
        $data2->addPoints([30, 20, 15], "Score");
        $data2->addPoints(["UK", "FR", "ES"], "Labels");
        $data2->setAbscissa("Labels");
        $settings = ["TextPadding" => 4, "Spacing" => 30, "Surrounding" => 20];
        $image->setGraphArea(350, 50, 690, 200);
        $SplitChart->drawSplitPath($image, $data2, $settings);

        $filename = $this->tester->getOutputPathForChart('drawSplit.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
