# Drawing a stock chart

[Reference](http://wiki.pchart.net/doc.stocks.drawstockchart.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Stock;
use CpChart\Data;
use CpChart\Image;

/* Create and populate the Data object */
$data = new Data();
$data->addPoints([34, 55, 15, 62, 38, 42], "Open");
$data->addPoints([42, 25, 40, 38, 49, 36], "Close");
$data->addPoints([27, 14, 12, 25, 32, 32], "Min");
$data->addPoints([45, 59, 47, 65, 64, 48], "Max");
$data->setAxisDisplay(0, AXIS_FORMAT_CURRENCY, "$");
$data->addPoints(["8h", "10h", "12h", "14h", "16h", "18h"], "Time");
$data->setAbscissa("Time");

/* Create the Image object */
$image = new Image(700, 230, $data);

/* Draw the background */
$settings = ["R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107];
$image->drawFilledRectangle(0, 0, 700, 230, $settings);

/* Overlay with a gradient */
$settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50];
$image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);

/* Draw the border */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the title */
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
$image->drawText(60, 45, "Stock price", ["FontSize" => 28, "Align" => TEXT_ALIGN_BOTTOMLEFT]);

/* Draw the 1st scale */
$image->setGraphArea(60, 60, 450, 190);
$image->drawFilledRectangle(60, 60, 450, 190, [
    "R" => 255,
    "G" => 255,
    "B" => 255,
    "Surrounding" => -200,
    "Alpha" => 10
]);
$image->drawScale(["DrawSubTicks" => true, "CycleBackground" => true]);

/* Draw the 1st stock chart */
$mystockChart = new Stock($image, $data);
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 30]);
$mystockChart->drawStockChart();

/* Reset the display mode because of the graph small size */
$data->setAxisDisplay(0, AXIS_FORMAT_DEFAULT);

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
$mystockChart = new Stock($image, $data);
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 30]);
$mystockChart->drawStockChart();

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawStockChart.png");
```
