# Drawing a 2D pie chart

[Reference](http://wiki.pchart.net/doc.pie.draw2dpie.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Pie;
use CpChart\Data;
use CpChart\Image;

// Create and populate data
$data = new Data();
$data->addPoints([40, 60, 15, 10, 6, 4], "ScoreA");
$data->setSerieDescription("ScoreA", "Application A");

// Define the absissa serie
$data->addPoints(["<10", "10<>20", "20<>40", "40<>60", "60<>80", ">80"], "Labels");
$data->setAbscissa("Labels");

// Create the image
$image = new Image(700, 230, $data);

// Draw a solid background
$backgroundSettings = [
    "R"     => 173,
    "G"     => 152,
    "B"     => 217,
    "Dash"  => 1,
    "DashR" => 193,
    "DashG" => 172,
    "DashB" => 237
];
$image->drawFilledRectangle(0, 0, 700, 230, $backgroundSettings);

//Draw a gradient overlay
$gradientSettings = [
    "StartR" => 209,
    "StartG" => 150,
    "StartB" => 231,
    "EndR"   => 111,
    "EndG"   => 3,
    "EndB"   => 138,
    "Alpha"  => 50
];
$image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $gradientSettings);
$image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR"   => 50,
    "EndG"   => 50,
    "EndB"   => 50,
    "Alpha"  => 100
]);

// Add a border to the picture
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

// Write the picture title
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "pPie - Draw 2D pie charts", ["R" => 255, "G" => 255, "B" => 255]);

// Set the default font properties
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);

// Enable shadow computing
$image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 150, "G" => 150, "B" => 150, "Alpha" => 100]);
$image->drawText(140, 200, "Single AA pass", ["R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE]);

// Create and draw the chart
$pieChart = new Pie($image, $data);
$pieChart->draw2DPie(140, 125, ["SecondPass" => false]);
$pieChart->draw2DPie(340, 125, ["DrawLabels" => true, "Border" => true]);
$pieChart->draw2DPie(540, 125, [
    "DataGapAngle"  => 10,
    "DataGapRadius" => 6,
    "Border" => true,
    "BorderR" => 255,
    "BorderG" => 255,
    "BorderB" => 255
]);
$image->drawText(540, 200, "Extended AA pass / Splitted", ["R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE]);

$pieChart->pChartObject->autoOutput("example.draw2DPie.png");
```

