# Drawing a 3D ring chart

[Reference](http://wiki.pchart.net/doc.pie.draw3dring.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Pie;
use CpChart\Data;
use CpChart\Image;

/* Create and populate the Data object */
$data = new Data();
$data->addPoints([50, 2, 3, 4, 7, 10, 25, 48, 41, 10], "ScoreA");
$data->setSerieDescription("ScoreA", "Application A");

/* Define the absissa serie */
$data->addPoints(["A0", "B1", "C2", "D3", "E4", "F5", "G6", "H7", "I8", "J9"], "Labels");
$data->setAbscissa("Labels");

/* Create the Image object */
$image = new Image(400, 400, $data);

/* Draw a solid background */
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
    "Alpha" => 100
]);

/* Add a border to the picture */
$image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the picture title */
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "pPie - Draw 3D ring charts", ["R" => 255, "G" => 255, "B" => 255]);

/* Set the default font properties */
$image->setFontProperties([
    "FontName" => "Forgotte.ttf",
    "FontSize" => 10,
    "R" => 80,
    "G" => 80,
    "B" => 80
]);

/* Enable shadow computing */
$image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 50]);

/* Create the pPie object */
$pieChart = new Pie($image, $data);

/* Draw an AA pie chart */
$pieChart->draw3DRing(200, 200, ["DrawLabels" => true, "LabelStacked" => true, "Border" => true]);

/* Write the legend box */
$pieChart->drawPieLegend(80, 360, ["Mode" => LEGEND_HORIZONTAL, "Style" => LEGEND_NOBORDER, "Alpha" => 20]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.draw3DRing.png");
```
