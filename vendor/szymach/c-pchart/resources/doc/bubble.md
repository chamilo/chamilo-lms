# Drawing a linear bubble chart

[Reference](http://wiki.pchart.net/doc.bubble.drawbubblechart.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Bubble;
use CpChart\Data;
use CpChart\Image;

/* Create and populate the Data object */
$data = new Data();
$data->addPoints([34, 55, 15, 62, 38, 42], "Probe1");
$data->addPoints([5, 10, 8, 9, 15, 10], "Probe1Weight");
$data->addPoints([5, 10, -5, -1, 0, -10], "Probe2");
$data->addPoints([6, 10, 14, 10, 14, 6], "Probe2Weight");
$data->setSerieDescription("Probe1", "This year");
$data->setSerieDescription("Probe2", "Last year");
$data->setAxisName(0, "Current stock");
$data->addPoints(["Apple", "Banana", "Orange", "Lemon", "Peach", "Strawberry"], "Product");
$data->setAbscissa("Product");

/* Create the Image object */
$image = new Image(700, 230, $data);

/* Draw the background */
$settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
$image->drawFilledRectangle(0, 0, 700, 230, $settings);

/* Overlay with a gradient */
$settings = [
    "StartR" => 219,
    "StartG" => 231,
    "StartB" => 139,
    "EndR" => 1,
    "EndG" => 138,
    "EndB" => 68,
    "Alpha" => 50
];
$image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
$image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 80
]);

/* Add a border to the picture */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the picture title */
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "drawBubbleChart() - draw a linear bubble chart", [
    "R" => 255,
    "G" => 255,
    "B" => 255
]);

/* Write the title */
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
$image->drawText(40, 55, "Current Stock / Needs chart", ["FontSize" => 14, "Align" => TEXT_ALIGN_BOTTOMLEFT]);

/* Change the default font */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);

/* Create the Bubble chart object and scale up */
$bubbleChart = new Bubble($image, $data);

/* Scale up for the bubble chart */
$bubbleDataSeries = ["Probe1", "Probe2"];
$bubbleWeightSeries = ["Probe1Weight", "Probe2Weight"];
$bubbleChart->bubbleScale($bubbleDataSeries, $bubbleWeightSeries);

/* Draw the 1st chart */
$image->setGraphArea(40, 60, 430, 190);
$image->drawFilledRectangle(40, 60, 430, 190, ["R" => 255, "G" => 255, "B" => 255,
    "Surrounding" => -200, "Alpha" => 10]);
$image->drawScale(["DrawSubTicks" => true, "CycleBackground" => true]);
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 30]);
$bubbleChart->drawBubbleChart($bubbleDataSeries, $bubbleWeightSeries);

/* Draw the 2nd scale */
$image->setShadow(false);
$image->setGraphArea(500, 60, 670, 190);
$image->drawFilledRectangle(500, 60, 670, 190, [
    "R" => 255,
    "G" => 255,
    "B" => 255,
    "Surrounding" => -200,
    "Alpha" => 10
]);
$image->drawScale(["Pos" => SCALE_POS_TOPBOTTOM, "DrawSubTicks" => true]);

/* Draw the 2nd stock chart */
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 30]);
$bubbleChart->drawbubbleChart($bubbleDataSeries, $bubbleWeightSeries);

/* Write the chart legend */
$image->drawLegend(550, 215, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawBubbleChart.png");
```
