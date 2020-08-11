# Drawing a scatter threshold area chart

[Reference](http://wiki.pchart.net/doc.scatter.drawscatterthresholdarea.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Scatter;
use CpChart\Data;
use CpChart\Image;

/* Create the Data object */
$data = new Data();

/* Create the X axis and the binded series */
$data->createFunctionSerie("X", "1/z", ["MinX" => -10, "MaxX" => 10, "XStep" => 1]);
$data->setAxisName(0, "x = 1/z");
$data->setAxisXY(0, AXIS_X);
$data->setAxisPosition(0, AXIS_POSITION_BOTTOM);

/* Create the Y axis */
$data->createFunctionSerie("Y", "z", ["MinX" => -10, "MaxX" => 10, "XStep" => 1]);
$data->setSerieOnAxis("Y", 1);
$data->setAxisName(1, "y = z");
$data->setAxisXY(1, AXIS_Y);
$data->setAxisPosition(1, AXIS_POSITION_RIGHT);

/* Create the Y axis */
$data->createFunctionSerie("Y2", "z*z*z", ["MinX" => -10, "MaxX" => 10, "XStep" => 1]);
$data->setSerieOnAxis("Y2", 2);
$data->setAxisName(2, "y = z*z*z");
$data->setAxisXY(2, AXIS_Y);
$data->setAxisPosition(2, AXIS_POSITION_LEFT);

/* Create the 1st scatter chart binding */
$data->setScatterSerie("X", "Y", 0);
$data->setScatterSerieDescription(0, "Pass A");
$data->setScatterSerieTicks(0, 4);
$data->setScatterSerieColor(0, ["R" => 0, "G" => 0, "B" => 0]);

/* Create the 2nd scatter chart binding */
$data->setScatterSerie("X", "Y2", 1);
$data->setScatterSerieDescription(1, "Pass B");
$data->setScatterSerieTicks(1, 4);
$data->setScatterSerieColor(1, ["R" => 120, "G" => 0, "B" => 255]);

/* Create the Image object */
$image = new Image(400, 400, $data);

/* Draw the background */
$settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
$image->drawFilledRectangle(0, 0, 400, 400, $settings);

/* Overlay with a gradient */
$image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, [
    "StartR" => 219,
    "StartG" => 231,
    "StartB" => 139,
    "EndR" => 1,
    "EndG" => 138,
    "EndB" => 68,
    "Alpha" => 50
]);
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
$image->drawText(10, 13, "createFunctionSerie() - Functions computing", [
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
$myScatter->drawScatterScale(["XMargin" => 10, "YMargin" => 10, "Floating" => true]);

/* Turn on shadow computing */
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Draw the 0/0 lines */
$myScatter->drawScatterThreshold(0, ["AxisID" => 0, "R" => 0, "G" => 0, "B" => 0, "Ticks" => 10]);
$myScatter->drawScatterThreshold(0, ["AxisID" => 1, "R" => 0, "G" => 0, "B" => 0, "Ticks" => 10]);

/* Draw a treshold area */
$myScatter->drawScatterThresholdArea(-0.1, 0.1, ["AreaName" => "Error zone"]);

/* Draw a scatter plot chart */
$myScatter->drawScatterLineChart();
$myScatter->drawScatterPlotChart();

/* Draw the legend */
$myScatter->drawScatterLegend(300, 380, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.createFunctionSerie.scatter.png");

```
