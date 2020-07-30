# Drawing a best fit chart

[Reference](http://wiki.pchart.net/doc.chart.drawbestfit.html)

``` php
require '/path/to/your/vendor/autoload.php';

use CpChart\Data;
use CpChart\Image;

/* Create and populate the Data object */
$data = new Data();
for ($i = 0; $i <= 20; $i++) {
    $data->addPoints(rand(10, 30) + $i, "Probe 1");
}
for ($i = 0; $i <= 20; $i++) {
    $data->addPoints(rand(0, 10) + $i, "Probe 2");
}
$data->setAxisName(0, "Temperatures");

/* Create the Image object */
$image = new Image(700, 230, $data);

/* Turn off Antialiasing */
$image->Antialias = false;

/* Add a border to the picture */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the chart title */
$image->setFontProperties(["FontName" => ".Forgotte.ttf", "FontSize" => 11]);
$image->drawText(150, 35, "Average temperature", ["FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]);

/* Set the default font */
$image->setFontProperties(["FontName" => ".pf_arma_five.ttf", "FontSize" => 6]);

/* Define the chart area */
$image->setGraphArea(60, 40, 650, 200);

/* Draw the scale */
$scaleSettings = [
    "XMargin" => 10,
    "YMargin" => 10,
    "Floating" => true,
    "GridR" => 200,
    "GridG" => 200,
    "GridB" => 200,
    "DrawSubTicks" => true,
    "CycleBackground" => true
];
$image->drawScale($scaleSettings);

/* Turn on Antialiasing */
$image->Antialias = true;

/* Draw the line of best fit */
$image->drawBestFit();

/* Turn on shadows */
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Draw the line chart */
$image->drawPlotChart();

/* Write the chart legend */
$image->drawLegend(580, 20, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawBestFit.png");
```
