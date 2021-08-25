# Drawing a bar chart

[Reference](http://wiki.pchart.net/doc.chart.drawbarchart.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Data;
use CpChart\Image;

/* Create and populate the Data object */
$data = new Data();
$data->addPoints([13251, 4118, 3087, 1460, 1248, 156, 26, 9, 8], "Hits");
$data->setAxisName(0, "Hits");
$data->addPoints(["Firefox", "Chrome", "Internet Explorer", "Opera", "Safari", "Mozilla", "SeaMonkey", "Camino", "Lunascape"], "Browsers");
$data->setSerieDescription("Browsers", "Browsers");
$data->setAbscissa("Browsers");

/* Create the Image object */
$image = new Image(500, 500, $data);
$image->drawGradientArea(0, 0, 500, 500, DIRECTION_VERTICAL, [
    "StartR" => 240,
    "StartG" => 240,
    "StartB" => 240,
    "EndR" => 180,
    "EndG" => 180,
    "EndB" => 180,
    "Alpha" => 100
]);
$image->drawGradientArea(0, 0, 500, 500, DIRECTION_HORIZONTAL, [
    "StartR" => 240,
    "StartG" => 240,
    "StartB" => 240,
    "EndR" => 180,
    "EndG" => 180,
    "EndB" => 180,
    "Alpha" => 20
]);
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);

/* Draw the chart scale */
$image->setGraphArea(100, 30, 480, 480);
$image->drawScale([
    "CycleBackground" => true,
    "DrawSubTicks" => true,
    "GridR" => 0,
    "GridG" => 0,
    "GridB" => 0,
    "GridAlpha" => 10,
    "Pos" => SCALE_POS_TOPBOTTOM
]);

/* Turn on shadow computing */
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Draw the chart */
$image->drawBarChart(["DisplayPos" => LABEL_POS_INSIDE, "DisplayValues" => true, "Rounded" => true, "Surrounding" => 30]);

/* Write the legend */
$image->drawLegend(570, 215, ["Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawBarChart.vertical.png");
```
