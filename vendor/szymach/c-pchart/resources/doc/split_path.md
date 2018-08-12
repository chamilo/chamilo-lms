# Drawing a split path chart

[Reference](http://wiki.pchart.net/doc.chart.drawsplitpath.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Data;
use CpChart\Image;
use CpChart\Chart\Split;

/* Create the Image object */
$data = new Image(700, 230);

/* Draw the background */
$settings = [
    "R" => 170,
    "G" => 183,
    "B" => 87,
    "Dash" => 1,
    "DashR" => 190,
    "DashG" => 203,
    "DashB" => 107
];
$data->drawFilledRectangle(0, 0, 700, 230, $settings);

/* Overlay with a gradient */
$settings = ["StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1,
    "EndG" => 138, "EndB" => 68, "Alpha" => 50];
$data->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $settings);
$data->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, ["StartR" => 0, "StartG" => 0,
    "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80]);

/* Add a border to the picture */
$data->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the picture title */
$data->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$data->drawText(10, 13, "pSplit - Draw splitted path charts", ["R" => 255, "G" => 255, "B" => 255]);

/* Set the default font properties */
$data->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80]);

/* Enable shadow computing */
$data->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Create and populate the Data object */
$data = new Data();
$data->addPoints([30, 20, 15, 10, 8, 4], "Score");
$data->addPoints(["End of visit", "Home Page", "Product Page", "Sales", "Statistics", "Prints"], "Labels");
$data->setAbscissa("Labels");

/* Create the pSplit object */
$splitChart = new Split();

/* Draw the split chart */
$settings = ["TextPos" => TEXT_POS_RIGHT, "TextPadding" => 10, "Spacing" => 20, "Surrounding" => 40];
$data->setGraphArea(10, 20, 340, 230);
$splitChart->drawSplitPath($data, $data, $settings);

/* Create and populate the Data object */
$data2 = new Data();
$data2->addPoints([30, 20, 15], "Score");
$data2->addPoints(["UK", "FR", "ES"], "Labels");
$data2->setAbscissa("Labels");

/* Draw the split chart */
$settings = ["TextPadding" => 4, "Spacing" => 30, "Surrounding" => 20];
$data->setGraphArea(350, 50, 690, 200);
$splitChart->drawSplitPath($data, $data2, $settings);

/* Render the picture (choose the best way) */
$data->autoOutput("example.split.png");
```
