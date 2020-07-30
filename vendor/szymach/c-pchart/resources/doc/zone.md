# Drawing a zone chart

[Reference](http://wiki.pchart.net/doc.chart.drawzonechart.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Data;
use CpChart\Image;

/* Create and populate the Data object */
$data = new Data();
for ($i = 0; $i <= 10; $i = $i + .2) {
    $data->addPoints(log($i + 1) * 10, "Bounds 1");
    $data->addPoints(log($i + 3) * 10 + rand(0, 2) - 1, "Probe");
    $data->addPoints(log($i + 6) * 10, "Bounds 2");
    $data->addPoints($i * 10, "Labels");
}
$data->setAxisName(0, "Size (cm)");
$data->setSerieDescription("Labels", "Months");
$data->setAbscissa("Labels");
$data->setAbscissaName("Time (years)");

/* Create the Image object */
$image = new Image(700, 230, $data);

/* Turn off Antialiasing */
$image->Antialias = false;

/* Add a border to the picture */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the chart title */
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
$image->drawText(150, 35, "Size by time generations", ["FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]);

/* Set the default font */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);

/* Define the chart area */
$image->setGraphArea(40, 40, 680, 200);

/* Draw the scale */
$image->drawScale([
    "LabelSkip" => 4,
    "XMargin" => 10,
    "YMargin" => 10,
    "Floating" => true,
    "GridR" => 200,
    "GridG" => 200,
    "GridB" => 200,
    "DrawSubTicks" => true,
    "CycleBackground" => true
]);

/* Turn on Antialiasing */
$image->Antialias = true;

/* Draw the line chart */
$image->drawZoneChart("Bounds 1", "Bounds 2");
$data->setSerieDrawable(["Bounds 1", "Bounds 2"], false);

/* Draw the line chart */
$image->drawStepChart();

/* Write the chart legend */
$image->drawLegend(640, 20, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawZoneChart.png");
```
