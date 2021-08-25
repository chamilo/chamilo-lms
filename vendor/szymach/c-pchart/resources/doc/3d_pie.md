# Drawing a 3D pie chart

[Reference](http://wiki.pchart.net/doc.pie.draw3dpie.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Pie;
use CpChart\Data;
use CpChart\Image;

/* Create and populate the Data object */
$data = new Data();
$data->addPoints([40, 30, 20], "ScoreA");
$data->setSerieDescription("ScoreA", "Application A");

/* Define the absissa serie */
$data->addPoints(["A", "B", "C"], "Labels");
$data->setAbscissa("Labels");

/* Create the Image object */
$image = new Image(700, 230, $data, true);

/* Draw a solid background */
$image->drawFilledRectangle(0, 0, 700, 230, [
    "R" => 173,
    "G" => 152,
    "B" => 217,
    "Dash" => 1,
    "DashR" => 193,
    "DashG" => 172,
    "DashB" => 237
]);

/* Draw a gradient overlay */
$image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, [
    "StartR" => 209,
    "StartG" => 150,
    "StartB" => 231,
    "EndR" => 111,
    "EndG" => 3,
    "EndB" => 138,
    "Alpha" => 50
]);
$image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 100
]);

/* Add a border to the picture */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the picture title */
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "pPie - Draw 3D pie charts", ["R" => 255, "G" => 255,
    "B" => 255]);

/* Set the default font properties */
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10,
    "R" => 80, "G" => 80, "B" => 80]);

/* Create the pPie object */
$pieChart = new Pie($image, $data);

/* Define the slice color */
$pieChart->setSliceColor(0, ["R" => 143, "G" => 197, "B" => 0]);
$pieChart->setSliceColor(1, ["R" => 97, "G" => 77, "B" => 63]);
$pieChart->setSliceColor(2, ["R" => 97, "G" => 113, "B" => 63]);

/* Draw a simple pie chart */
$pieChart->draw3DPie(120, 125, ["SecondPass" => false]);

/* Draw an AA pie chart */
$pieChart->draw3DPie(340, 125, ["DrawLabels" => true, "Border" => true]);

/* Enable shadow computing */
$image->setShadow(true, ["X" => 3, "Y" => 3, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Draw a splitted pie chart */
$pieChart->draw3DPie(560, 125, ["WriteValues" => true, "DataGapAngle" => 10, "DataGapRadius" => 6, "Border" => true]);

/* Write the legend */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]);
$image->drawText(120, 200, "Single AA pass", [
    "DrawBox" => true,
    "BoxRounded" => true,
    "R" => 0,
    "G" => 0,
    "B" => 0,
    "Align" => TEXT_ALIGN_TOPMIDDLE
]);
$image->drawText(440, 200, "Extended AA pass / Splitted", [
    "DrawBox" => true,
    "BoxRounded" => true,
    "R" => 0,
    "G" => 0,
    "B" => 0,
    "Align" => TEXT_ALIGN_TOPMIDDLE
]);

/* Write the legend box */
$image->setFontProperties([
    "FontName" => "Silkscreen.ttf",
    "FontSize" => 6,
    "R" => 255,
    "G" => 255,
    "B" => 255
]);
$pieChart->drawPieLegend(600, 8, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.draw3DPie.png");
```
