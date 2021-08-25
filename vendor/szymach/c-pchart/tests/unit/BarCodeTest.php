<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Barcode\Barcode128;
use CpChart\Barcode\Barcode39;
use CpChart\Image;
use UnitTester;

class BarCodeTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function test39Code()
    {
        $image = new Image(700,230);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 700, 230, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "Barcode 39 - Add barcode to your pictures", ["R" => 255, "G" => 255, "B" => 255]);
        $barcode = new Barcode39();
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $settings = ["ShowLegend" => true, "DrawArea" => true];
        $barcode->draw($image, "pChart Rocks!", 50, 50, $settings);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 12]);
        $settings = ["ShowLegend" => true, "DrawArea" => true, "Angle" => 90];
        $barcode->draw($image, "Turn me on", 650, 50, $settings);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 12]);
        $settings = ["R" => 255, "G" => 255, "B" => 255, "AreaR" => 150, "AreaG" => 30, "AreaB" => 27, "ShowLegend" => true, "DrawArea" => true, "Angle" => 350, "AreaBorderR" => 70, "AreaBorderG" => 20, "AreaBorderB" => 20];
        $barcode->draw($image, "Do what you want !", 290, 140, $settings);

        $filename = $this->tester->getOutputPathForChart('drawBarcode39.png');
        $barcode->pChartObject->render($filename);
        $barcode->pChartObject->stroke();

        $this->tester->seeFileFound($filename);
    }

    public function test128Code()
    {
        $image = new Image(700, 230);
        $settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
        $image->drawFilledRectangle(0, 0, 700, 230, $settings);
        $settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
        $image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100]);
        $image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "Barcode 128 - Add barcode to your pictures", ["R" => 255, "G" => 255, "B" => 255]);
        $barcode = new Barcode128();
        $image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
        $settings = ["ShowLegend" => true, "DrawArea" => true];
        $barcode->draw($image, "pChart Rocks!", 50, 50, $settings);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 12]);
        $settings = ["ShowLegend" => true, "DrawArea" => true, "Angle" => 90];
        $barcode->draw($image, "Turn me on", 650, 50, $settings);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 12]);
        $settings = ["R" => 255, "G" => 255, "B" => 255, "AreaR" => 150, "AreaG" => 30, "AreaB" => 27, "ShowLegend" => true, "DrawArea" => true, "Angle" => 350, "AreaBorderR" => 70, "AreaBorderG" => 20, "AreaBorderB" => 20];
        $barcode->draw($image, "Do what you want !", 290, 140, $settings);

        $filename = $this->tester->getOutputPathForChart('drawBarcode128.png');
        $barcode->pChartObject->render($filename);
        $barcode->pChartObject->stroke();

        $this->tester->seeFileFound($filename);
    }
}
