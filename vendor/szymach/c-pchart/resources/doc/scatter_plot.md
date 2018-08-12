# Drawing a scatter plot chart

[Reference](http://wiki.pchart.net/doc.scatter.drawscatterplotChart.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Scatter;
use CpChart\Data;
use CpChart\Image;

/* Create the Data object */
$data = new Data();

/* Create the X axis and the binded series */
for ($i = 0; $i <= 360; $i = $i + 10) {
    $data->addPoints(cos(deg2rad($i)) * 20, "Probe 1");
}
for ($i = 0; $i <= 360; $i = $i + 10) {
    $data->addPoints(sin(deg2rad($i)) * 20, "Probe 2");
}
$data->setAxisName(0, "Index");
$data->setAxisXY(0, AXIS_X);
$data->setAxisPosition(0, AXIS_POSITION_BOTTOM);

/* Create the Y axis and the binded series */
for ($i = 0; $i <= 360; $i = $i + 10) {
    $data->addPoints($i, "Probe 3");
}
$data->setSerieOnAxis("Probe 3", 1);
$data->setAxisName(1, "Degree");
$data->setAxisXY(1, AXIS_Y);
$data->setAxisUnit(1, "°");
$data->setAxisPosition(1, AXIS_POSITION_RIGHT);

/* Create the 1st scatter chart binding */
$data->setScatterSerie("Probe 1", "Probe 3", 0);
$data->setScatterSerieDescription(0, "This year");
$data->setScatterSerieColor(0, ["R" => 0, "G" => 0, "B" => 0]);

/* Create the 2nd scatter chart binding */
$data->setScatterSerie("Probe 2", "Probe 3", 1);
$data->setScatterSerieDescription(1, "Last Year");

/* Create the Image object */
$image = new Image(400, 400, $data);

/* Draw the background */
$settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
$image->drawFilledRectangle(0, 0, 400, 400, $settings);

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
$image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, $settings);
$image->drawGradientArea(0, 0, 400, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 80
]);

/* Write the picture title */
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "drawScatterPlotChart() - Draw a scatter plot chart", [
    "R" => 255,
    "G" => 255,
    "B" => 255
]);

/* Add a border to the picture */
$image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);

/* Set the default font */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);

/* Set the graph area */
$image->setGraphArea(50, 50, 350, 350);

/* Create the Scatter chart object */
$myScatter = new Scatter($image, $data);

/* Draw the scale */
$myScatter->drawScatterScale();

/* Turn on shadow computing */
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Draw a scatter plot chart */
$myScatter->drawScatterPlotChart();

/* Draw the legend */
$myScatter->drawScatterLegend(260, 375, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawScatterPlotChart.png");
```
