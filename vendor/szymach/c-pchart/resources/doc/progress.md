# Drawing a progress chart

[Reference](http://wiki.pchart.net/doc.chart.drawprogress.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Data;
use CpChart\Image;

$image = new Image(700, 250);

/* Enable shadow support */
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]);

/* Left Red bar */
$progressOptions = ["R" => 209, "G" => 31, "B" => 27, "Surrounding" => 20, "BoxBorderR" => 0,
    "BoxBorderG" => 0, "BoxBorderB" => 0, "BoxBackR" => 255, "BoxBackG" => 255, "BoxBackB" => 255,
    "RFade" => 206, "GFade" => 133, "BFade" => 30, "ShowLabel" => true];
$image->drawProgress(40, 60, 77, $progressOptions);

/* Left Orange bar */
$progressOptions = ["Width" => 165, "R" => 209, "G" => 125, "B" => 27, "Surrounding" => 20,
    "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0, "BoxBackR" => 255, "BoxBackG" => 255,
    "BoxBackB" => 255, "NoAngle" => true, "ShowLabel" => true, "LabelPos" => LABEL_POS_RIGHT];
$image->drawProgress(40, 100, 50, $progressOptions);

/* Left Yellow bar */
$progressOptions = ["Width" => 165, "R" => 209, "G" => 198, "B" => 27, "Surrounding" => 20,
    "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0, "BoxBackR" => 255, "BoxBackG" => 255,
    "BoxBackB" => 255, "ShowLabel" => true, "LabelPos" => LABEL_POS_LEFT];
$image->drawProgress(75, 140, 25, $progressOptions);

/* Left Green bar */
$progressOptions = ["Width" => 400, "R" => 134, "G" => 209, "B" => 27, "Surrounding" => 20,
    "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0, "BoxBackR" => 255, "BoxBackG" => 255,
    "BoxBackB" => 255, "RFade" => 206, "GFade" => 133, "BFade" => 30, "ShowLabel" => true,
    "LabelPos" => LABEL_POS_CENTER];
$image->drawProgress(40, 180, 80, $progressOptions);

/* Right vertical Red bar */
$progressOptions = ["Width" => 20, "Height" => 150, "R" => 209, "G" => 31, "B" => 27,
    "Surrounding" => 20, "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0,
    "BoxBackR" => 255, "BoxBackG" => 255, "BoxBackB" => 255, "RFade" => 206, "GFade" => 133,
    "BFade" => 30, "ShowLabel" => true, "Orientation" => ORIENTATION_VERTICAL, "LabelPos" => LABEL_POS_BOTTOM];
$image->drawProgress(500, 200, 77, $progressOptions);

/* Right vertical Orange bar */
$progressOptions = ["Width" => 20, "Height" => 150, "R" => 209, "G" => 125,
    "B" => 27, "Surrounding" => 20, "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0,
    "BoxBackR" => 255, "BoxBackG" => 255, "BoxBackB" => 255, "NoAngle" => true, "ShowLabel" => true,
    "Orientation" => ORIENTATION_VERTICAL, "LabelPos" => LABEL_POS_TOP];
$image->drawProgress(540, 200, 50, $progressOptions);

/* Right vertical Yellow bar */
$progressOptions = ["Width" => 20, "Height" => 150, "R" => 209, "G" => 198,
    "B" => 27, "Surrounding" => 20, "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0,
    "BoxBackR" => 255, "BoxBackG" => 255, "BoxBackB" => 255, "ShowLabel" => true,
    "Orientation" => ORIENTATION_VERTICAL, "LabelPos" => LABEL_POS_INSIDE];
$image->drawProgress(580, 200, 25, $progressOptions);

/* Right vertical Green bar */
$progressOptions = ["Width" => 20, "Height" => 150, "R" => 134, "G" => 209,
    "B" => 27, "Surrounding" => 20, "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0,
    "BoxBackR" => 255, "BoxBackG" => 255, "BoxBackB" => 255, "RFade" => 206, "GFade" => 133,
    "BFade" => 30, "ShowLabel" => true, "Orientation" => ORIENTATION_VERTICAL, "LabelPos" => LABEL_POS_CENTER];
$image->drawProgress(620, 200, 80, $progressOptions);

/* Render the picture (choose the best way) */
$image->autoOutput("example.drawProgressChart.png");
```
