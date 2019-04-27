# Drawing an area chart

[Reference](http://wiki.pchart.net/doc.chart.drawareachart.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Data;
use CpChart\Image;

$data = new Data();
for ($i = 0; $i <= 30; $i++) {
    $data->addPoints(rand(1, 15), "Probe 1");
}
$data->setSerieTicks("Probe 2", 4);
$data->setAxisName(0, "Temperatures");

// Create the Image object
$image = new Image(700, 230, $data);

/* Turn off Antialiasing */
$image->Antialias = false;

/* Add a border to the picture */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the chart title */
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
$image->drawText(150, 35, "Average temperature", ["FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]);

/* Set the default font */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);

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

/* Write the chart legend */
$image->drawLegend(600, 20, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

/* Turn on Antialiasing */
$image->Antialias = true;

/* Enable shadow computing */
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Draw the area chart */
$Threshold = [];
$Threshold[] = ["Min" => 0, "Max" => 5, "R" => 207, "G" => 240, "B" => 20, "Alpha" => 70];
$Threshold[] = ["Min" => 5, "Max" => 10, "R" => 240, "G" => 232, "B" => 20, "Alpha" => 70];
$Threshold[] = ["Min" => 10, "Max" => 20, "R" => 240, "G" => 191, "B" => 20, "Alpha" => 70];
$image->drawAreaChart(["Threshold" => $Threshold]);

/* Write the thresholds */
$image->drawThreshold(5, ["WriteCaption" => true, "Caption" => "Warn Zone", "Alpha" => 70, "Ticks" => 2, "R" => 0, "G" => 0, "B" => 255]);
$image->drawThreshold(10, ["WriteCaption" => true, "Caption" => "Error Zone", "Alpha" => 70, "Ticks" => 2, "R" => 0, "G" => 0, "B" => 255]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawAreaChart.threshold.png");
```
