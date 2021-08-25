# Drawing a spline chart

[Reference](http://wiki.pchart.net/doc.chart.drawsplinechart.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Data;
use CpChart\Image;

// Create and populate data
$data = new Data();
$data->addPoints([], "Serie1");

// Create the image and set the data
$image = new Image(700, 230, $data);
$image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]);

// 1st spline drawn in white with control points visible
$firstCoordinates = [[40, 80], [280, 60], [340, 166], [590, 120]];
$fistSplineSettings = ["R" => 255, "G" => 255, "B" => 255, "ShowControl" => true];
$image->drawSpline($firstCoordinates, $fistSplineSettings);

// 2nd spline dashed drawn in white with control points visible
$secondCoordinates = [[250, 50], [250, 180], [350, 180], [350, 50]];
$secondSplineSettings = [
    "R" => 255,
    "G" => 255,
    "B"=> 255,
    "ShowControl" => true,
    "Ticks" => 4
];
$image->drawSpline($secondCoordinates, $secondSplineSettings);

// Render the picture (choose the best way)
$image->autoOutput("example.drawSpline.png");
```
