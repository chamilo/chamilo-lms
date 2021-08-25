<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Image;
use CpChart\Chart\Spring;
use UnitTester;

class SpringTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $image = new Image(300, 300);
        $image->drawGradientArea(0, 0, 300, 300, DIRECTION_HORIZONTAL, ["StartR" => 217, "StartG" => 250, "StartB" => 116, "EndR" => 181, "EndG" => 209, "EndB" => 27, "Alpha" => 100]);
        $image->drawGradientArea(0, 0, 300, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100]);
        $image->drawRectangle(0, 0, 299, 299, ["R" => 0, "G" => 0, "B" => 0]);
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "pSpring - Draw spring charts", ["R" => 255, "G" => 255, "B" => 255]);
        $image->setGraphArea(20, 20, 280, 280);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 9, "R" => 80, "G" => 80, "B" => 80]);
        $image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);
        $SpringChart = new Spring();
        $SpringChart->addNode(0, ["Shape" => NODE_SHAPE_SQUARE, "FreeZone" => 60, "Size" => 20, "NodeType" => NODE_TYPE_CENTRAL]);
        $SpringChart->addNode(1, ["Connections" => "0"]);
        $SpringChart->addNode(2, ["Connections" => "0"]);
        $SpringChart->addNode(3, ["Shape" => NODE_SHAPE_TRIANGLE, "Connections" => "1"]);
        $SpringChart->addNode(4, ["Shape" => NODE_SHAPE_TRIANGLE, "Connections" => "1"]);
        $SpringChart->addNode(5, ["Shape" => NODE_SHAPE_TRIANGLE, "Connections" => "1"]);
        $SpringChart->addNode(6, ["Connections" => "2"]);
        $SpringChart->addNode(7, ["Connections" => "2"]);
        $SpringChart->addNode(8, ["Connections" => "2"]);
        $SpringChart->setNodesColor(0, ["R" => 215, "G" => 163, "B" => 121, "BorderR" => 166, "BorderG" => 115, "BorderB" => 74]);
        $SpringChart->setNodesColor([1, 2], ["R" => 150, "G" => 215, "B" => 121, "Surrounding" => -30]);
        $SpringChart->setNodesColor([3, 4, 5], ["R" => 216, "G" => 166, "B" => 14, "Surrounding" => -30]);
        $SpringChart->setNodesColor([6, 7, 8], ["R" => 179, "G" => 121, "B" => 215, "Surrounding" => -30]);
        $SpringChart->linkProperties(0, 1, ["R" => 255, "G" => 0, "B" => 0, "Ticks" => 2]);
        $SpringChart->linkProperties(0, 2, ["R" => 255, "G" => 0, "B" => 0, "Ticks" => 2]);
        $SpringChart->drawSpring($image);

        $filename = $this->tester->getOutputPathForChart('drawSpring.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
