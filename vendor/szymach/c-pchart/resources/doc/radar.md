# Drawing a radar chart

[Reference](http://wiki.pchart.net/doc.draw.radar.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Radar;
use CpChart\Data;
use CpChart\Image;

/* Prepare some nice data & axis config */
$data = new Data();
$data->addPoints([40, 20, 15, 10, 8, 4], "ScoreA");
$data->addPoints([8, 10, 12, 20, 30, 15], "ScoreB");
$data->setSerieDescription("ScoreA", "Application A");
$data->setSerieDescription("ScoreB", "Application B");

/* Create the X serie */
$data->addPoints(["Size", "Speed", "Reliability", "Functionalities", "Ease of use", "Weight"], "Labels");
$data->setAbscissa("Labels");

/* Create the Image object */
$image = new Image(700, 230, $data);

/* Draw a solid background */
$settings = ["R" => 179, "G" => 217, "B" => 91, "Dash" => 1, "DashR" => 199, "DashG" => 237, "DashB" => 111];
$image->drawFilledRectangle(0, 0, 700, 230, $settings);

/* Overlay some gradient areas */
$settings = ["StartR" => 194, "StartG" => 231, "StartB" => 44, "EndR" => 43, "EndG" => 107, "EndB" => 58, "Alpha" => 50];
$image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
$image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 100
]);

/* Draw the border */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the title */
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "pRadar - Draw radar charts", ["R" => 255, "G" => 255,
    "B" => 255]);

/* Define general drawing parameters */
$image->setFontProperties([
    "FontName" => "Forgotte.ttf",
    "FontSize" => 10,
    "R" => 80,
    "G" => 80,
    "B" => 80
]);
$image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Create the radar object */
$radarChart = new Radar();

/* Draw the 1st radar chart */
$image->setGraphArea(10, 25, 340, 225);
$Options = ["Layout" => RADAR_LAYOUT_STAR, "BackgroundGradient" => [
    "StartR" => 255,
    "StartG" => 255,
    "StartB" => 255,
    "StartAlpha" => 100,
    "EndR" => 207,
    "EndG" => 227,
    "EndB" => 125,
    "EndAlpha" => 50
]];
$radarChart->drawRadar($image, $data, $Options);

/* Draw the 2nd radar chart */
$image->setGraphArea(350, 25, 690, 225);
$Options = ["Layout" => RADAR_LAYOUT_CIRCLE, "LabelPos" => RADAR_LABELS_HORIZONTAL, "BackgroundGradient" => [
    "StartR" => 255,
    "StartG" => 255,
    "StartB" => 255,
    "StartAlpha" => 50,
    "EndR" => 32,
    "EndG" => 109,
    "EndB" => 174,
    "EndAlpha" => 30
]];
$radarChart->drawRadar($image, $data, $Options);

/* Write down the legend */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
$image->drawLegend(270, 205, ["Style" => LEGEND_BOX, "Mode" => LEGEND_HORIZONTAL]);

/* Render the picture */
$image->render("drawRadar.png");
```
